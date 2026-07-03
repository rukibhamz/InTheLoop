<?php

namespace App\Jobs;

use App\Services\Graph\GraphMailSync;
use App\Services\Graph\GraphSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncGraphMailbox implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;

    public int $tries = 1;

    public function __construct(
        public string $mailbox
    ) {
        $this->onQueue('sync');
    }

    public function handle(GraphSettings $settings, GraphMailSync $sync): void
    {
        if (! $settings->isConfigured()) {
            return;
        }

        try {
            $imported = $sync->pollMailbox($this->mailbox);

            if ($imported > 0) {
                Log::info("SyncGraphMailbox: imported {$imported} message(s) from {$this->mailbox}.");
            }
        } catch (\Throwable $exception) {
            Log::warning('SyncGraphMailbox: poll failed', [
                'mailbox' => $this->mailbox,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
