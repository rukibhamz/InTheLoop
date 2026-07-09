<?php

namespace App\Console\Commands;

use App\Jobs\SyncGraphMailboxes;
use App\Services\Graph\GraphMailSync;
use App\Services\Graph\GraphSettings;
use Illuminate\Console\Command;

class SyncGraphMail extends Command
{
    protected $signature = 'graph:sync-mail';

    protected $description = 'Poll monitored shared mailboxes for inbound email replies';

    public function handle(GraphSettings $settings, GraphMailSync $sync): int
    {
        if (! $settings->isConfigured()) {
            $this->warn('Microsoft Graph is not configured. Skipping mail sync.');

            return self::SUCCESS;
        }

        $mailboxes = $settings->allMonitoredMailboxes();

        if ($mailboxes === []) {
            $this->warn('No monitored mailboxes found. Configure GRAPH_MONITORED_MAILBOXES or assign shared mailboxes to users.');

            return self::SUCCESS;
        }

        $total = 0;

        foreach ($mailboxes as $mailbox) {
            try {
                $imported = $sync->pollMailbox($mailbox);
                $total += $imported;
                $this->line("{$mailbox}: imported {$imported} message(s)");
            } catch (\Throwable $exception) {
                $this->warn("{$mailbox}: sync failed — {$exception->getMessage()}");
            }
        }

        $this->info("Imported {$total} inbound message(s) total.");

        SyncGraphMailboxes::startPollingLoop();

        $this->comment('Background mailbox polling started on the queue (every 3 min). Requires php artisan queue:work.');

        return self::SUCCESS;
    }
}
