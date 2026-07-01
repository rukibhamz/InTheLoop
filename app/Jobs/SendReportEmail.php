<?php

namespace App\Jobs;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\ReportEvent;
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

class SendReportEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public Report $report
    ) {}

    public function handle(GraphMailer $mailer): void
    {
        $this->report->loadMissing(['user', 'category', 'participants']);

        $html = view('emails.report-submitted', [
            'report' => $this->report,
        ])->render();

        if ($mailer->isConfigured()) {
            $sendMeta = $mailer->sendReport($this->report, $html);
            $mailbox = $sendMeta['mailbox'];
            $mailer->recordOutboundMessage($this->report, $mailbox, $sendMeta, null, false);

            $this->report->update([
                'status' => ReportStatus::Sent,
                'sent_at' => now(),
                'conversation_id' => $sendMeta['conversation_id'] ?? null,
            ]);

            ReportEvent::query()->create([
                'report_id' => $this->report->id,
                'type' => 'sent',
                'meta' => [
                    'mailbox' => $mailbox,
                    'conversation_id' => $sendMeta['conversation_id'] ?? null,
                    'graph_message_id' => $sendMeta['graph_message_id'] ?? null,
                ],
            ]);

            SyncGraphMailboxes::startPollingLoop(45);

            foreach (app(\App\Services\Graph\GraphSettings::class)->mailboxesForReport($this->report) as $mailbox) {
                SyncGraphMailbox::dispatch($mailbox)->delay(now()->addSeconds(60));
            }

            return;
        }

        Log::info('SendReportEmail mock dispatch', [
            'report_id' => $this->report->id,
            'subject' => $this->report->subject,
        ]);

        $this->report->update([
            'status' => ReportStatus::Sent,
            'sent_at' => now(),
            'conversation_id' => 'mock-'.$this->report->id,
        ]);

        ReportEvent::query()->create([
            'report_id' => $this->report->id,
            'type' => 'sent',
            'meta' => ['mock' => true],
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $this->report->update(['status' => ReportStatus::Failed]);

        ReportEvent::query()->create([
            'report_id' => $this->report->id,
            'type' => 'failed',
            'meta' => ['message' => $exception?->getMessage()],
        ]);
    }
}
