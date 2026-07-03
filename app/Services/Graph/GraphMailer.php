<?php

namespace App\Services\Graph;

use App\Enums\MessageDirection;
use App\Models\Report;
use App\Models\ReportMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GraphMailer
{
    public function __construct(
        private readonly GraphSettings $settings,
        private readonly GraphTokenService $tokens
    ) {}

    public function isConfigured(): bool
    {
        return $this->settings->isConfigured() && filled($this->settings->defaultSenderMailbox());
    }

    public function resolveSenderMailbox(Report $report): ?string
    {
        return $report->user?->shared_mailbox_email
            ?: $report->user?->email
            ?: $this->settings->defaultSenderMailbox();
    }

    /**
     * Mailbox used for Graph API send operations.
     * Prefers the submitter's shared mailbox; falls back to the default sender.
     */
    public function resolveApiMailbox(?Report $report = null): string
    {
        $preferred = $report ? $this->resolveSenderMailbox($report) : null;

        if ($preferred) {
            return $preferred;
        }

        $default = $this->settings->defaultSenderMailbox();

        if (! $default) {
            throw new RuntimeException('No default sender mailbox configured.');
        }

        return $default;
    }

    /**
     * @return array{mailbox: string, graph_message_id: ?string, conversation_id: ?string, internet_message_id: ?string}
     */
    public function sendReport(Report $report, string $htmlBody): array
    {
        $apiMailbox = $this->resolveApiMailbox($report);

        $to = $report->participants()
            ->where('type', 'to')
            ->get()
            ->map(fn ($participant) => ['email' => $participant->email, 'name' => $participant->name])
            ->all();

        $cc = $report->participants()
            ->where('type', 'cc')
            ->get()
            ->map(fn ($participant) => ['email' => $participant->email, 'name' => $participant->name])
            ->all();

        if ($to === []) {
            throw new RuntimeException('Report has no To recipients.');
        }

        // CC the sending mailbox so a copy lands in Inbox for reply sync when Exchange
        // does not save app-sent mail to the shared mailbox Sent Items folder.
        $cc = $this->withMailboxCopy($apiMailbox, $report->user?->name, $to, $cc);

        $token = $this->tokens->getAppToken();
        $userPath = GraphUserPath::for($apiMailbox);

        $payload = [
            'subject' => $report->subject,
            'body' => ['contentType' => 'HTML', 'content' => $htmlBody],
            'toRecipients' => $this->mapAddresses($to),
            'ccRecipients' => $this->mapAddresses($cc),
            'from' => [
                'emailAddress' => [
                    'address' => $apiMailbox,
                    'name' => $report->user?->name,
                ],
            ],
        ];

        $this->sendMailMessage($token, $userPath, $payload, saveToSentItems: true);

        $sentMeta = $this->fetchLatestSentMessage($apiMailbox, $report->subject);

        Log::info('GraphMailer: report email sent', [
            'report_id' => $report->id,
            'mailbox' => $apiMailbox,
            'graph_message_id' => $sentMeta['graph_message_id'] ?? null,
            'conversation_id' => $sentMeta['conversation_id'] ?? null,
        ]);

        return [
            'mailbox' => $apiMailbox,
            'from_email' => $apiMailbox,
            'graph_message_id' => $sentMeta['graph_message_id'] ?? null,
            'conversation_id' => $sentMeta['conversation_id'] ?? null,
            'internet_message_id' => $sentMeta['internet_message_id'] ?? null,
        ];
    }

    public function sendSimpleNotification(string $subject, string $htmlBody, string $toEmail, ?string $toName = null): void
    {
        $mailbox = $this->resolveApiMailbox();

        $token = $this->tokens->getAppToken();
        $userPath = GraphUserPath::for($mailbox);

        $recipient = ['emailAddress' => ['address' => $toEmail]];

        if ($toName) {
            $recipient['emailAddress']['name'] = $toName;
        }

        $this->sendMailMessage($token, $userPath, [
            'subject' => $subject,
            'body' => ['contentType' => 'HTML', 'content' => $htmlBody],
            'toRecipients' => [$recipient],
            'from' => ['emailAddress' => ['address' => $mailbox]],
        ]);
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function sendMailMessage(
        string $token,
        string $userPath,
        array $message,
        bool $saveToSentItems = true
    ): void {
        Http::withToken($token)
            ->post(config('graph.base_url')."/users/{$userPath}/sendMail", [
                'message' => $message,
                'saveToSentItems' => $saveToSentItems,
            ])
            ->throw();
    }

    /**
     * @return array{graph_message_id: ?string, conversation_id: ?string, internet_message_id: ?string}
     */
    private function fetchLatestSentMessage(string $mailbox, string $expectedSubject): array
    {
        try {
            $token = $this->tokens->getAppToken();
            $userPath = GraphUserPath::for($mailbox);
            $url = config('graph.base_url')."/users/{$userPath}/mailFolders/sentitems/messages"
                .'?$top=5&$orderby='.urlencode('sentDateTime desc')
                .'&$select='.urlencode('id,conversationId,internetMessageId,subject,sentDateTime');

            $messages = Http::withToken($token)
                ->timeout(30)
                ->get($url)
                ->throw()
                ->json('value') ?? [];

            foreach ($messages as $message) {
                if (($message['subject'] ?? '') === $expectedSubject) {
                    return [
                        'graph_message_id' => $message['id'] ?? null,
                        'conversation_id' => $message['conversationId'] ?? null,
                        'internet_message_id' => $message['internetMessageId'] ?? null,
                    ];
                }
            }

            $latest = $messages[0] ?? null;

            if ($latest) {
                return [
                    'graph_message_id' => $latest['id'] ?? null,
                    'conversation_id' => $latest['conversationId'] ?? null,
                    'internet_message_id' => $latest['internetMessageId'] ?? null,
                ];
            }
        } catch (\Throwable $exception) {
            Log::warning('GraphMailer: could not fetch sent message metadata', [
                'mailbox' => $mailbox,
                'subject' => $expectedSubject,
                'error' => $exception->getMessage(),
            ]);
        }

        return [
            'graph_message_id' => null,
            'conversation_id' => null,
            'internet_message_id' => null,
        ];
    }

    /**
     * @param  list<array{email: string, name: ?string}>  $to
     * @param  list<array{email: string, name: ?string}>  $cc
     * @return list<array{email: string, name: ?string}>
     */
    private function withMailboxCopy(string $mailbox, ?string $senderName, array $to, array $cc): array
    {
        $mailboxLower = strtolower($mailbox);

        foreach (array_merge($to, $cc) as $address) {
            if (strtolower($address['email']) === $mailboxLower) {
                return $cc;
            }
        }

        return array_merge($cc, [['email' => $mailbox, 'name' => $senderName]]);
    }

    /**
     * @param  list<array{email: string, name: ?string}>  $addresses
     * @return list<array<string, mixed>>
     */
    private function mapAddresses(array $addresses): array
    {
        return array_map(function (array $address) {
            $entry = ['emailAddress' => ['address' => $address['email']]];

            if (filled($address['name'] ?? null)) {
                $entry['emailAddress']['name'] = $address['name'];
            }

            return $entry;
        }, $addresses);
    }

    public function recordOutboundMessage(
        Report $report,
        string $mailbox,
        array $sendMeta,
        ?string $htmlBody = null,
        bool $showInThread = true
    ): ReportMessage {
        $to = $report->participants()->where('type', 'to')->pluck('email')->all();
        $cc = $report->participants()->where('type', 'cc')->pluck('email')->all();

        return ReportMessage::query()->create([
            'report_id' => $report->id,
            'direction' => MessageDirection::Outbound,
            'mailbox' => $mailbox,
            'from_email' => $sendMeta['from_email'] ?? $mailbox,
            'to_emails' => $to,
            'cc_emails' => $cc,
            'subject' => $report->subject,
            'body_html' => $showInThread ? $htmlBody : null,
            'body_text' => $showInThread && $htmlBody ? strip_tags($htmlBody) : null,
            'graph_message_id' => $sendMeta['graph_message_id'] ?? null,
            'conversation_id' => $sendMeta['conversation_id'] ?? null,
            'internet_message_id' => $sendMeta['internet_message_id'] ?? null,
            'show_in_thread' => $showInThread,
        ]);
    }
}
