<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Spawns a short-lived queue worker so mail/sync jobs run without a permanent
 * `queue:work` terminal (useful on XAMPP / shared hosting).
 */
class QueueWorkerKick
{
    /**
     * Process ready jobs on the mail/sync queues in a detached PHP process.
     */
    public static function afterMail(int $maxTimeSeconds = 120): void
    {
        if (config('queue.default') === 'sync') {
            return;
        }

        // Avoid launching many overlapping workers from rapid clicks.
        if (! Cache::add('queue_worker_kick_lock', true, now()->addSeconds(20))) {
            return;
        }

        $php = PHP_BINARY ?: 'php';
        $artisan = base_path('artisan');
        $queues = 'mail,default,sync';
        $maxTime = max(30, $maxTimeSeconds);

        try {
            if (PHP_OS_FAMILY === 'Windows') {
                // `start` treats the first quoted arg as the window title — keep it empty.
                $command = 'start /B "" '
                    .escapeshellarg($php).' '
                    .escapeshellarg($artisan)
                    .' queue:work --queue='.$queues
                    .' --stop-when-empty --max-time='.$maxTime
                    .' --sleep=1 --tries=3';

                pclose(popen($command, 'r'));
            } else {
                $command = escapeshellarg($php).' '
                    .escapeshellarg($artisan)
                    .' queue:work --queue='.$queues
                    .' --stop-when-empty --max-time='.$maxTime
                    .' --sleep=1 --tries=3 > /dev/null 2>&1 &';

                exec($command);
            }

            Log::info('QueueWorkerKick: started short-lived worker', [
                'queues' => $queues,
                'max_time' => $maxTime,
            ]);
        } catch (\Throwable $exception) {
            Cache::forget('queue_worker_kick_lock');
            Log::warning('QueueWorkerKick: failed to start worker', [
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
