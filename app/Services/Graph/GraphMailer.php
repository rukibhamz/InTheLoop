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
     * @return array{mailbox: string, graph_message_id: ?string, conversation_id: ?string, internet_message_id: ?string}
     */
    public function sendReport(Report $report, string $htmlBody): array
    {
        $mailbox = $this->resolveSenderMailbox($report);

        if (! $mailbox) {
            throw new RuntimeException('No shared mailbox configured for outbound delivery.');
        }

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
        $cc = $this->withMailboxCopy($mailbox, $report->user?->name, $to, $cc);

        $token = $this->tokens->getAppToken();
        $userPath = GraphUserPath::for($mailbox);

        $draft = Http::withToken($token)
            ->post(config('graph.base_url')."/users/{$userPath}/messages", [
                'subject' => $report->subject,
                'body' => ['contentType' => 'HTML', 'content' => $htmlBody],
                'toRecipients' => $this->mapAddresses($to),
                'ccRecipients' => $this->mapAddresses($cc),
                'from' => [
                    'emailAddress' => [
                        'address' => $mailbox,
                        'name' => $report->user?->name,
                    ],
                ],
            ])
            ->throw()
            ->json();

        $messageId = $draft['id'] ?? null;

        if (! $messageId) {
            throw new RuntimeException('Graph did not return a message id when creating the draft.');
        }

        Http::withToken($token)
            ->post(config('graph.base_url')."/users/{$userPath}/messages/{$messageId}/send")
            ->throw();

        Log::info('GraphMailer: report email sent', [
            'report_id' => $report->id,
            'mailbox' => $mailbox,
            'graph_message_id' => $messageId,
            'conversation_id' => $draft['conversationId'] ?? null,
        ]);

        return [
            'mailbox' => $mailbox,
            'graph_message_id' => $messageId,
            'conversation_id' => $draft['conversationId'] ?? null,
            'internet_message_id' => $draft['internetMessageId'] ?? null,
        ];
    }

    public function sendSimpleNotification(string $subject, string $htmlBody, string $toEmail, ?string $toName = null): void
    {
        $mailbox = $this->settings->defaultSenderMailbox();

        if (! $mailbox) {
            throw new RuntimeException('No default sender mailbox configured.');
        }

        $token = $this->tokens->getAppToken();
        $userPath = GraphUserPath::for($mailbox);

        $recipient = ['emailAddress' => ['address' => $toEmail]];

        if ($toName) {
            $recipient['emailAddress']['name'] = $toName;
        }

        $draft = Http::withToken($token)
            ->post(config('graph.base_url')."/users/{$userPath}/messages", [
                'subject' => $subject,
                'body' => ['contentType' => 'HTML', 'content' => $htmlBody],
                'toRecipients' => [$recipient],
                'from' => ['emailAddress' => ['address' => $mailbox]],
            ])
            ->throw()
            ->json();

        $messageId = $draft['id'] ?? throw new RuntimeException('Graph did not return a message id.');

        Http::withToken($token)
            ->post(config('graph.base_url')."/users/{$userPath}/messages/{$messageId}/send")
            ->throw();
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
            'from_email' => $mailbox,
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
