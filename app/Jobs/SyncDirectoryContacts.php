<?php

namespace App\Jobs;

use App\Services\Graph\GraphDirectorySync;
use App\Services\Graph\GraphSettings;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncDirectoryContacts implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 3600;

    public function __construct()
    {
        $this->onQueue('sync');
    }

    public function uniqueId(): string
    {
        return 'sync-directory-contacts';
    }

    public function handle(GraphSettings $settings, GraphDirectorySync $sync): void
    {
        if (! $settings->isConfigured()) {
            return;
        }

        try {
            $sync->sync();
        } catch (\Throwable $exception) {
            Log::warning('Directory sync job failed', ['message' => $exception->getMessage()]);
        }
    }
}
