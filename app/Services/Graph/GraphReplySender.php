<?php

namespace App\Services\Graph;

use App\Models\Email;
use App\Models\EmailMessage;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GraphReplySender
{
    public function __construct(
        private readonly GraphSettings $settings,
        private readonly GraphTokenService $tokens
    ) {}

    public function isConfigured(): bool
    {
        return $this->settings->isConfigured();
    }

    /**
     * @return array{sent: bool, reason: ?string, graph_message_id: ?string}
     */
    public function replyAll(Email $email, User $user, string $bodyText): array
    {
        $mailbox = $user->shared_mailbox_email ?: $user->email;

        if (! filled($mailbox)) {
            return ['sent' => false, 'reason' => 'no_mailbox', 'graph_message_id' => null];
        }

        $anchor = $this->findAnchorMessage($email, $mailbox)
            ?? $this->findAnchorMessage($email, $this->settings->defaultSenderMailbox() ?? '');

        if (! $anchor) {
            return ['sent' => false, 'reason' => 'copy_not_synced', 'graph_message_id' => null];
        }

        $replyMailbox = $anchor->mailbox;

        $token = $this->tokens->getAppToken();
        $userPath = GraphUserPath::for($replyMailbox);

        Http::withToken($token)
            ->post(
                config('graph.base_url')."/users/{$userPath}/messages/{$anchor->graph_message_id}/replyAll",
                ['comment' => $bodyText]
            )
            ->throw();

        return ['sent' => true, 'reason' => null, 'graph_message_id' => $anchor->graph_message_id];
    }

    private function findAnchorMessage(Email $email, string $mailbox): ?EmailMessage
    {
        $inMailbox = EmailMessage::query()
            ->where('email_id', $email->id)
            ->where('mailbox', $mailbox)
            ->whereNotNull('graph_message_id')
            ->latest()
            ->first();

        if ($inMailbox) {
            return $inMailbox;
        }

        return EmailMessage::query()
            ->where('email_id', $email->id)
            ->whereNotNull('graph_message_id')
            ->latest()
            ->first();
    }
}
