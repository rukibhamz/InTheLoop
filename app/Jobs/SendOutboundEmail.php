<?php

namespace App\Jobs;

use App\Enums\EmailStatus;
use App\Models\Email;
use App\Models\EmailEvent;
use App\Jobs\SyncGraphMailboxes;
use App\Jobs\SyncGraphMailbox;
use App\Services\Graph\GraphMailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendOutboundEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public Email $email
    ) {
        $this->onQueue('mail');
    }

    public function handle(GraphMailer $mailer): void
    {
        $this->email->loadMissing(['user', 'category', 'participants', 'attachments']);

        $html = view('emails.email-submitted', [
            'email' => $this->email,
        ])->render();

        if ($mailer->isConfigured()) {
            $sendMeta = $mailer->sendEmail($this->email, $html);
            $mailbox = $sendMeta['mailbox'];
            $mailer->recordOutboundMessage($this->email, $mailbox, $sendMeta, null, false);

            $this->email->update([
                'status' => EmailStatus::Sent,
                'sent_at' => now(),
                'conversation_id' => $sendMeta['conversation_id'] ?? null,
            ]);

            EmailEvent::query()->create([
                'email_id' => $this->email->id,
                'type' => 'sent',
                'meta' => [
                    'mailbox' => $mailbox,
                    'conversation_id' => $sendMeta['conversation_id'] ?? null,
                    'graph_message_id' => $sendMeta['graph_message_id'] ?? null,
                ],
            ]);

            SyncGraphMailboxes::startPollingLoop(45);

            foreach (app(\App\Services\Graph\GraphSettings::class)->mailboxesForEmail($this->email) as $mailbox) {
                SyncGraphMailbox::dispatch($mailbox)->delay(now()->addSeconds(60));
            }

            return;
        }

        Log::info('SendOutboundEmail mock dispatch', [
            'email_id' => $this->email->id,
            'subject' => $this->email->subject,
        ]);

        $this->email->update([
            'status' => EmailStatus::Sent,
            'sent_at' => now(),
            'conversation_id' => 'mock-'.$this->email->id,
        ]);

        EmailEvent::query()->create([
            'email_id' => $this->email->id,
            'type' => 'sent',
            'meta' => ['mock' => true],
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $this->email->update(['status' => EmailStatus::Failed]);

        EmailEvent::query()->create([
            'email_id' => $this->email->id,
            'type' => 'failed',
            'meta' => ['message' => $exception?->getMessage()],
        ]);
    }
}
