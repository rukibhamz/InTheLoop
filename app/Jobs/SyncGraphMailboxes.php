<?php

namespace App\Jobs;

use App\Services\Graph\GraphSettings;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncGraphMailboxes implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 30;

    public int $tries = 1;

    public int $uniqueFor = 120;

    public function uniqueId(): string
    {
        return 'graph-mail-sync-coordinator';
    }

    /**
     * Start the recurring mailbox poll loop on the queue worker (every 3 minutes).
     */
    public static function startPollingLoop(int $delaySeconds = 0): void
    {
        $settings = app(GraphSettings::class);

        if (! $settings->isConfigured()) {
            return;
        }

        if (! Cache::add('graph_mail_sync_loop_active', true, now()->addMinutes(4))) {
            return;
        }

        self::dispatch()->delay(now()->addSeconds($delaySeconds));
    }

    public function handle(GraphSettings $settings): void
    {
        if (! $settings->isConfigured()) {
            return;
        }

        $mailboxes = $settings->allMonitoredMailboxes();

        if ($mailboxes === []) {
            Log::info('SyncGraphMailboxes: no monitored mailboxes configured.');

            return;
        }

        foreach ($mailboxes as $mailbox) {
            SyncGraphMailbox::dispatch($mailbox);
        }

        Log::info('SyncGraphMailboxes: queued poll for '.count($mailboxes).' mailbox(es).');

        Cache::put('graph_mail_sync_loop_active', true, now()->addMinutes(4));
        self::dispatch()->delay(now()->addMinutes(3));
    }

    public function failed(?\Throwable $exception): void
    {
        Log::warning('SyncGraphMailboxes coordinator failed; rescheduling poll loop.', [
            'error' => $exception?->getMessage(),
        ]);

        Cache::forget('graph_mail_sync_loop_active');
        self::dispatch()->delay(now()->addMinutes(3));
    }
}
