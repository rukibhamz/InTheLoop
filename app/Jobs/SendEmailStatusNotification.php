<?php

namespace App\Jobs;

use App\Models\Email;
use App\Models\User;
use App\Services\Branding;
use App\Services\Graph\GraphMailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendEmailStatusNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Email $email,
        public string $statusLabel,
        public ?int $actorUserId = null
    ) {}

    public function handle(GraphMailer $mailer, Branding $branding): void
    {
        $this->email->loadMissing(['user', 'participants', 'category']);

        $actor = $this->actorUserId
            ? User::query()->find($this->actorUserId)
            : null;

        $recipients = $this->email->participants
            ->pluck('email')
            ->push($this->email->user?->email)
            ->filter()
            ->unique()
            ->values();

        $appUsers = User::query()
            ->whereIn('email', $recipients)
            ->where('is_active', true)
            ->get()
            ->keyBy('email');

        $emailsToNotify = $recipients->filter(function (string $address) use ($appUsers) {
            $user = $appUsers->get($address);

            return ! $user || $user->notificationEnabled('status_changes', 'email');
        });

        if ($emailsToNotify->isEmpty()) {
            return;
        }

        $html = view('emails.email-status-changed', [
            'email' => $this->email,
            'statusLabel' => $this->statusLabel,
            'actorName' => $actor?->name,
            'orgName' => $branding->orgName(),
            'logoUrl' => $branding->logoUrl(),
            'emailUrl' => route('emails.show', $this->email),
        ])->render();

        if (! $mailer->isConfigured()) {
            Log::info('Email status notification (mock)', [
                'email_id' => $this->email->id,
                'status' => $this->statusLabel,
                'recipients' => $emailsToNotify->all(),
            ]);

            return;
        }

        foreach ($emailsToNotify as $address) {
            $mailer->sendSimpleNotification(
                subject: "Email {$this->statusLabel}: {$this->email->subject}",
                htmlBody: $html,
                toEmail: $address
            );
        }
    }
}
