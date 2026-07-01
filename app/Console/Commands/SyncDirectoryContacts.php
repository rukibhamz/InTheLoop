<?php

namespace App\Console\Commands;

use App\Services\Graph\GraphDirectorySync;
use App\Services\Graph\GraphSettings;
use Illuminate\Console\Command;

class SyncDirectoryContacts extends Command
{
    protected $signature = 'directory:sync';

    protected $description = 'Sync Azure AD profiles into directory_contacts';

    public function handle(GraphSettings $settings, GraphDirectorySync $sync): int
    {
        if (! $settings->isConfigured()) {
            $this->warn('Microsoft Graph is not configured. Skipping directory sync.');

            return self::SUCCESS;
        }

        $count = $sync->sync();
        $this->info("Synced {$count} directory contacts.");

        return self::SUCCESS;
    }
}
