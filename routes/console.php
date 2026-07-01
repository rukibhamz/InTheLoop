<?php

use App\Jobs\SyncGraphMailboxes;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('directory:sync')->hourly();
Schedule::job(new SyncGraphMailboxes)->everyThreeMinutes();
