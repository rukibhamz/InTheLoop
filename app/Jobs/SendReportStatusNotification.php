<?php

namespace App\Jobs;

use App\Models\Report;
use App\Models\User;
use App\Services\Branding;
use App\Services\Graph\GraphMailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendReportStatusNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Report $report,
        public string $statusLabel,
        public ?int $actorUserId = null
    ) {}

    public function handle(GraphMailer $mailer, Branding $branding): void
    {
        $this->report->loadMissing(['user', 'participants', 'category']);

        $actor = $this->actorUserId
            ? User::query()->find($this->actorUserId)
            : null;

        $recipients = $this->report->participants
            ->pluck('email')
            ->push($this->report->user?->email)
            ->filter()
            ->unique()
            ->values();

        $appUsers = User::query()
            ->whereIn('email', $recipients)
            ->where('is_active', true)
            ->get()
            ->keyBy('email');

        $emailsToNotify = $recipients->filter(function (string $email) use ($appUsers) {
            $user = $appUsers->get($email);

            return ! $user || $user->notificationEnabled('status_changes', 'email');
        });

        if ($emailsToNotify->isEmpty()) {
            return;
        }

        $html = view('emails.report-status-changed', [
            'report' => $this->report,
            'statusLabel' => $this->statusLabel,
            'actorName' => $actor?->name,
            'orgName' => $branding->orgName(),
            'logoUrl' => $branding->logoUrl(),
            'reportUrl' => route('reports.show', $this->report),
        ])->render();

        if (! $mailer->isConfigured()) {
            Log::info('Report status notification (mock)', [
                'report_id' => $this->report->id,
                'status' => $this->statusLabel,
                'recipients' => $emailsToNotify->all(),
            ]);

            return;
        }

        foreach ($emailsToNotify as $email) {
            $mailer->sendSimpleNotification(
                subject: "Report {$this->statusLabel}: {$this->report->subject}",
                htmlBody: $html,
                toEmail: $email
            );
        }
    }
}
