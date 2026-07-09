<?php

namespace App\Jobs;

use App\Models\Email;
use App\Models\EmailMessage;
use App\Models\User;
use App\Services\Graph\GraphReplySender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendGraphReply implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function backoff(): array
    {
        return [30, 60, 120, 300, 600];
    }

    public function __construct(
        public EmailMessage $message,
        public User $user
    ) {
        $this->onQueue('mail');
    }

    public function handle(GraphReplySender $sender): void
    {
        if (! $sender->isConfigured()) {
            return;
        }

        $email = $this->message->email;

        $result = $sender->replyAll(
            $email,
            $this->user,
            $this->message->body_text ?? strip_tags($this->message->body_html ?? '')
        );

        if ($result['sent']) {
            $this->message->update(['email_pending' => false]);

            return;
        }

        if ($result['reason'] === 'no_mailbox') {
            $this->message->update(['email_pending' => false]);

            return;
        }

        if ($result['reason'] === 'copy_not_synced' && $this->attempts() < $this->tries) {
            Log::info('Graph reply waiting for mailbox copy sync', [
                'email_id' => $email->id,
                'message_id' => $this->message->id,
                'attempt' => $this->attempts(),
            ]);

            $this->release($this->backoff()[$this->attempts() - 1] ?? 300);

            return;
        }

        $this->message->update(['email_pending' => $result['reason'] === 'copy_not_synced']);
    }
}
