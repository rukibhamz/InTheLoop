<?php

namespace App\Services\Graph;

use App\Enums\MessageDirection;
use App\Enums\EmailStatus;
use App\Models\Announcement;
use App\Models\Email;
use App\Models\EmailEvent;
use App\Models\EmailMessage;
use App\Support\EmailReplyStripper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class GraphMailSync
{
    private const MAX_ATTACHMENT_BYTES = 26214400;

    /** @var list<string> */
    private static array $invalidMailboxes = [];

    public function __construct(
        private readonly GraphSettings $settings,
        private readonly GraphTokenService $tokens
    ) {}

    public function pollMailbox(string $mailbox): int
    {
        if (! $this->settings->isConfigured()) {
            throw new RuntimeException('Microsoft Graph is not configured.');
        }

        $mailboxKey = strtolower($mailbox);

        if (in_array($mailboxKey, self::$invalidMailboxes, true)) {
            return 0;
        }

        $inbox = 0;
        $sent = 0;

        try {
            $inbox = $this->pollFolder($mailbox, 'inbox');
        } catch (\Throwable $exception) {
            if ($this->isInvalidMailboxError($exception, $mailboxKey)) {
                return 0;
            }

            Log::warning('GraphMailSync: inbox poll failed', [
                'mailbox' => $mailbox,
                'error' => $exception->getMessage(),
            ]);
        }

        try {
            $sent = $this->pollFolder($mailbox, 'sentitems');
        } catch (\Throwable $exception) {
            if ($this->isInvalidMailboxError($exception, $mailboxKey)) {
                return 0;
            }

            Log::warning('GraphMailSync: sentitems poll failed', [
                'mailbox' => $mailbox,
                'error' => $exception->getMessage(),
            ]);
        }

        return $inbox + $sent;
    }

    private function isInvalidMailboxError(\Throwable $exception, string $mailboxKey): bool
    {
        if (! str_contains($exception->getMessage(), 'ErrorInvalidUser')) {
            return false;
        }

        self::$invalidMailboxes[] = $mailboxKey;

        Log::info('GraphMailSync: skipping invalid Graph mailbox', ['mailbox' => $mailboxKey]);

        return true;
    }

    private function pollFolder(string $mailbox, string $folder): int
    {
        $token = $this->tokens->getAppToken();
        $dateField = $folder === 'sentitems' ? 'sentDateTime' : 'receivedDateTime';
        $select = implode(',', [
            'conversationId',
            'from',
            'subject',
            'bodyPreview',
            'receivedDateTime',
            'sentDateTime',
            'toRecipients',
            'ccRecipients',
            'internetMessageId',
            'hasAttachments',
        ]);

        $url = config('graph.base_url').'/users/'.GraphUserPath::for($mailbox)."/mailFolders/{$folder}/messages"
            .'?$top=40'
            .'&$orderby='.urlencode("{$dateField} desc")
            .'&$select='.urlencode($select);

        $response = Http::withToken($token)
            ->timeout(30)
            ->get($url)
            ->throw()
            ->json();

        $imported = 0;

        foreach ($response['value'] ?? [] as $message) {
            if ($this->importMessage($mailbox, $folder, $message)) {
                $imported++;
            }
        }

        return $imported;
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function importMessage(string $mailbox, string $folder, array $message): bool
    {
        $conversationId = $message['conversationId'] ?? null;
        $graphMessageId = $message['id'] ?? null;
        $internetMessageId = $message['internetMessageId'] ?? null;

        if (! $conversationId || ! $graphMessageId) {
            return false;
        }

        if (EmailMessage::query()->where('graph_message_id', $graphMessageId)->exists()) {
            return false;
        }

        if ($internetMessageId && EmailMessage::query()->where('internet_message_id', $internetMessageId)->exists()) {
            return false;
        }

        if (Announcement::query()->where('graph_message_id', $graphMessageId)->exists()) {
            return false;
        }

        if ($internetMessageId && Announcement::query()->where('internet_message_id', $internetMessageId)->exists()) {
            return false;
        }

        $email = $this->findEmailForMessage($message);

        if (! $email) {
            return $this->importAnnouncement($mailbox, $folder, $message);
        }

        if ($this->shouldSkipMessage($email, $message, $mailbox, $folder)) {
            return false;
        }

        $message = $this->loadMessageBody($mailbox, $message);

        $from = $message['from']['emailAddress']['address'] ?? 'unknown@local';
        $bodyText = $this->extractReplyText($message);

        if ($existing = $this->findExistingInAppOutbound($email, $from, $folder, $bodyText)) {
            $existing->update([
                'graph_message_id' => $graphMessageId,
                'internet_message_id' => $internetMessageId,
                'conversation_id' => $conversationId,
                'mailbox' => $mailbox,
                'email_pending' => false,
                'subject' => $message['subject'] ?? $existing->subject,
                'body_html' => $message['body']['content'] ?? $existing->body_html,
                'body_text' => $bodyText ?? $existing->body_text,
            ]);

            $this->importAttachments(
                $mailbox,
                $graphMessageId,
                $existing,
                (bool) ($message['hasAttachments'] ?? false)
            );

            return false;
        }

        $direction = $this->isSubmitterAddress($email, $from)
            ? MessageDirection::Outbound
            : MessageDirection::Inbound;

        $showInThread = ! $this->isSenderMailboxCopy($email, $message, $from);

        $emailMessage = EmailMessage::query()->create([
            'email_id' => $email->id,
            'direction' => $direction,
            'mailbox' => $mailbox,
            'from_email' => $from,
            'to_emails' => collect($message['toRecipients'] ?? [])
                ->pluck('emailAddress.address')
                ->filter()
                ->values()
                ->all(),
            'cc_emails' => collect($message['ccRecipients'] ?? [])
                ->pluck('emailAddress.address')
                ->filter()
                ->values()
                ->all(),
            'subject' => $message['subject'] ?? null,
            'body_html' => $message['body']['content'] ?? null,
            'body_text' => $bodyText,
            'graph_message_id' => $graphMessageId,
            'internet_message_id' => $internetMessageId,
            'conversation_id' => $conversationId,
            'show_in_thread' => $showInThread,
        ]);

        $this->importAttachments(
            $mailbox,
            $graphMessageId,
            $emailMessage,
            (bool) ($message['hasAttachments'] ?? false)
        );

        if (! $email->conversation_id) {
            $email->update(['conversation_id' => $conversationId]);
        }

        EmailEvent::query()->create([
            'email_id' => $email->id,
            'type' => 'replied',
            'meta' => [
                'source' => 'graph_sync',
                'folder' => $folder,
                'mailbox' => $mailbox,
                'from_email' => $from,
                'graph_message_id' => $graphMessageId,
            ],
        ]);

        if ($email->status === EmailStatus::Sent) {
            $email->update(['status' => EmailStatus::InReview]);

            EmailEvent::query()->create([
                'email_id' => $email->id,
                'type' => 'status_changed',
                'meta' => ['from' => EmailStatus::Sent->value, 'to' => EmailStatus::InReview->value],
            ]);
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function importAnnouncement(string $mailbox, string $folder, array $message): bool
    {
        if (! $this->settings->isAnnouncementMailbox($mailbox)) {
            return false;
        }

        if ($folder !== 'inbox') {
            return false;
        }

        $graphMessageId = $message['id'] ?? null;

        if (! $graphMessageId) {
            return false;
        }

        $message = $this->loadMessageBody($mailbox, $message);

        $fromAddress = $message['from']['emailAddress'] ?? [];
        $from = $fromAddress['address'] ?? 'unknown@local';
        $fromName = $fromAddress['name'] ?? null;
        $bodyText = $this->extractReplyText($message);
        $receivedAt = $message['receivedDateTime'] ?? $message['sentDateTime'] ?? now()->toIso8601String();

        $announcement = Announcement::query()->create([
            'mailbox' => $mailbox,
            'from_email' => $from,
            'from_name' => $fromName,
            'to_emails' => collect($message['toRecipients'] ?? [])
                ->pluck('emailAddress.address')
                ->filter()
                ->values()
                ->all(),
            'cc_emails' => collect($message['ccRecipients'] ?? [])
                ->pluck('emailAddress.address')
                ->filter()
                ->values()
                ->all(),
            'subject' => $message['subject'] ?? null,
            'body_html' => $message['body']['content'] ?? null,
            'body_text' => $bodyText,
            'graph_message_id' => $graphMessageId,
            'internet_message_id' => $message['internetMessageId'] ?? null,
            'conversation_id' => $message['conversationId'] ?? null,
            'folder' => $folder,
            'received_at' => $receivedAt,
        ]);

        $this->importAttachments(
            $mailbox,
            $graphMessageId,
            $announcement,
            (bool) ($message['hasAttachments'] ?? false)
        );

        return true;
    }

    /**
     * @param  array<string, mixed>  $message
     */
    public function debugMatchEmail(array $message): ?Email
    {
        return $this->findEmailForMessage($message);
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function findEmailForMessage(array $message): ?Email
    {
        $subject = $message['subject'] ?? '';
        $normalizedSubject = $this->normalizeSubject($subject);
        $from = strtolower($message['from']['emailAddress']['address'] ?? '');
        $conversationId = $message['conversationId'] ?? null;

        if ($conversationId) {
            $email = Email::query()->where('conversation_id', $conversationId)->first();

            if ($email) {
                return $email;
            }

            $emailId = EmailMessage::query()
                ->where('conversation_id', $conversationId)
                ->value('email_id');

            if ($emailId) {
                return Email::query()->find($emailId);
            }
        }

        if (! filled($normalizedSubject)) {
            return null;
        }

        $baseQuery = Email::query()
            ->whereNotNull('sent_at')
            ->where('subject', $normalizedSubject);

        if (filled($from) && $from !== 'unknown@local') {
            $participantMatch = (clone $baseQuery)
                ->where(function ($query) use ($from) {
                    $query->whereHas('participants', function ($participant) use ($from) {
                        $participant->whereRaw('lower(email) = ?', [$from]);
                    })->orWhereHas('user', function ($user) use ($from) {
                        $user->whereRaw('lower(email) = ?', [$from])
                            ->orWhereRaw('lower(shared_mailbox_email) = ?', [$from]);
                    });
                })
                ->latest('sent_at')
                ->first();

            if ($participantMatch) {
                return $participantMatch;
            }

            $threadMatch = Email::query()
                ->whereNotNull('sent_at')
                ->whereHas('messages', function ($messages) use ($conversationId, $from) {
                    if ($conversationId) {
                        $messages->where('conversation_id', $conversationId);
                    }

                    $messages->whereRaw('lower(from_email) = ?', [$from]);
                })
                ->whereHas('participants', function ($participant) use ($from) {
                    $participant->whereRaw('lower(email) = ?', [$from]);
                })
                ->latest('sent_at')
                ->first();

            if ($threadMatch) {
                return $threadMatch;
            }
        }

        return null;
    }

    private function isReplySubject(string $subject): bool
    {
        return (bool) preg_match('/^(re|fw|fwd)\s*:/i', trim($subject));
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function isSenderMailboxCopy(Email $email, array $message, string $from): bool
    {
        if ($this->isReplySubject($message['subject'] ?? '')) {
            return false;
        }

        return $this->isSubmitterAddress($email, $from)
            && ($message['subject'] ?? '') === $email->subject;
    }

    private function findEmailBySubject(?string $subject): ?Email
    {
        if (! filled($subject)) {
            return null;
        }

        $normalized = $this->normalizeSubject($subject);

        return Email::query()
            ->whereIn('status', [
                EmailStatus::Sent,
                EmailStatus::InReview,
                EmailStatus::Pending,
                EmailStatus::Approved,
                EmailStatus::Rejected,
                EmailStatus::Failed,
            ])
            ->where(function ($query) use ($subject, $normalized) {
                $query->where('subject', $subject)
                    ->orWhere('subject', $normalized);
            })
            ->whereNotNull('sent_at')
            ->latest('sent_at')
            ->first();
    }

    private function normalizeSubject(string $subject): string
    {
        $subject = trim($subject);

        while (preg_match('/^(re|fw|fwd):\s*/i', $subject)) {
            $stripped = preg_replace('/^(re|fw|fwd):\s*/i', '', $subject);

            if (! is_string($stripped) || $stripped === $subject) {
                break;
            }

            $subject = trim($stripped);
        }

        return $subject;
    }

    private function findExistingInAppOutbound(
        Email $email,
        string $from,
        string $folder,
        ?string $bodyText
    ): ?EmailMessage {
        if ($folder !== 'sentitems' || ! $this->isSubmitterAddress($email, $from) || ! filled($bodyText)) {
            return null;
        }

        $needle = strtolower(trim($bodyText));

        return EmailMessage::query()
            ->where('email_id', $email->id)
            ->where('direction', MessageDirection::Outbound)
            ->whereNull('graph_message_id')
            ->where('created_at', '>=', now()->subHours(2))
            ->whereRaw('lower(from_email) = ?', [strtolower($from)])
            ->orderByDesc('created_at')
            ->get()
            ->first(function (EmailMessage $message) use ($needle) {
                $candidate = strtolower(trim($message->body_text ?? strip_tags($message->body_html ?? '')));

                return $candidate === $needle;
            });
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function shouldSkipMessage(Email $email, array $message, string $mailbox, string $folder): bool
    {
        if ($folder === 'sentitems') {
            return false;
        }

        $from = $message['from']['emailAddress']['address'] ?? '';

        if (! $this->isSubmitterAddress($email, $from)) {
            return false;
        }

        if (($message['subject'] ?? '') !== $email->subject) {
            return false;
        }

        // Keep the sender's own inbox copy (CC) for Graph reply threading.
        if (in_array(strtolower($mailbox), $this->submitterAddresses($email), true)) {
            return false;
        }

        return true;
    }

    private function isSubmitterAddress(Email $email, string $address): bool
    {
        $address = strtolower($address);

        return in_array($address, $this->submitterAddresses($email), true);
    }

    /**
     * @return list<string>
     */
    private function submitterAddresses(Email $email): array
    {
        return array_values(array_filter(array_map(
            'strtolower',
            [
                $email->user?->email,
                $email->user?->shared_mailbox_email,
            ]
        )));
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function extractReplyText(array $message): ?string
    {
        $html = $message['body']['content'] ?? '';
        $contentType = strtolower($message['body']['contentType'] ?? '');

        if ($contentType === 'html' && filled($html)) {
            $text = EmailReplyStripper::stripHtml($html);

            if ($text !== '') {
                return $text;
            }
        }

        if (filled($html) && $contentType !== 'html') {
            $text = EmailReplyStripper::strip($html);

            if ($text !== '') {
                return $text;
            }
        }

        $preview = EmailReplyStripper::strip(trim($message['bodyPreview'] ?? ''));

        return $preview !== '' ? $preview : null;
    }

    /**
     * @param  array<string, mixed>  $message
     * @return array<string, mixed>
     */
    private function loadMessageBody(string $mailbox, array $message): array
    {
        if (filled($message['body']['content'] ?? null)) {
            return $message;
        }

        $graphMessageId = $message['id'] ?? null;

        if (! $graphMessageId) {
            return $message;
        }

        try {
            $token = $this->tokens->getAppToken();
            $userPath = GraphUserPath::for($mailbox);
            $url = config('graph.base_url')."/users/{$userPath}/messages/{$graphMessageId}"
                .'?$select='.urlencode('body,bodyPreview');

            $full = Http::withToken($token)
                ->timeout(20)
                ->get($url)
                ->throw()
                ->json();

            return array_merge($message, $full);
        } catch (\Throwable $exception) {
            Log::warning('GraphMailSync: could not load message body', [
                'mailbox' => $mailbox,
                'graph_message_id' => $graphMessageId,
                'error' => $exception->getMessage(),
            ]);

            return $message;
        }
    }

    private function importAttachments(
        string $mailbox,
        string $graphMessageId,
        EmailMessage|Announcement $message,
        bool $hasAttachments
    ): void {
        if (! $hasAttachments || $message->attachments()->exists()) {
            return;
        }

        $token = $this->tokens->getAppToken();
        $userPath = GraphUserPath::for($mailbox);
        $url = config('graph.base_url')."/users/{$userPath}/messages/{$graphMessageId}/attachments";

        try {
            $attachments = Http::withToken($token)
                ->timeout(30)
                ->get($url)
                ->throw()
                ->json('value') ?? [];
        } catch (\Throwable $exception) {
            Log::warning('GraphMailSync: attachment list failed', [
                'mailbox' => $mailbox,
                'graph_message_id' => $graphMessageId,
                'error' => $exception->getMessage(),
            ]);

            return;
        }

        foreach ($attachments as $attachment) {
            if (($attachment['@odata.type'] ?? '') !== '#microsoft.graph.fileAttachment') {
                continue;
            }

            if ($attachment['isInline'] ?? false) {
                continue;
            }

            $filename = basename($attachment['name'] ?? 'attachment');
            $size = (int) ($attachment['size'] ?? 0);

            if ($size > self::MAX_ATTACHMENT_BYTES) {
                Log::info('GraphMailSync: skipping oversized attachment', [
                    'filename' => $filename,
                    'size' => $size,
                ]);

                continue;
            }

            $content = $attachment['contentBytes'] ?? null;

            if ($content) {
                $binary = base64_decode($content, true);
            } else {
                $attachmentId = $attachment['id'] ?? null;

                if (! $attachmentId) {
                    continue;
                }

                try {
                    $binary = Http::withToken($token)
                        ->timeout(60)
                        ->get(config('graph.base_url')."/users/{$userPath}/messages/{$graphMessageId}/attachments/{$attachmentId}/\$value")
                        ->throw()
                        ->body();
                } catch (\Throwable $exception) {
                    Log::warning('GraphMailSync: attachment download failed', [
                        'filename' => $filename,
                        'error' => $exception->getMessage(),
                    ]);

                    continue;
                }
            }

            if (! is_string($binary) || $binary === '') {
                continue;
            }

            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $stem = Str::slug(pathinfo($filename, PATHINFO_FILENAME)) ?: 'attachment';
            $storedName = $stem.($extension ? '.'.$extension : '');
            $path = 'attachments/messages/'.$message->id.'/'.$storedName;

            Storage::disk('local')->put($path, $binary);

            $message->attachments()->create([
                'path' => $path,
                'original_filename' => $filename,
                'mime_type' => $attachment['contentType'] ?? 'application/octet-stream',
                'size' => strlen($binary),
            ]);
        }
    }
}
