<?php

namespace App\Services;

class InstallState
{
    public const LOCK_FILE = 'installed';

    public static function isInstalled(): bool
    {
        if (filter_var(env('INSTALLED', false), FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }

        return file_exists(storage_path('app/'.self::LOCK_FILE));
    }

    public static function markInstalled(): void
    {
        $path = storage_path('app/'.self::LOCK_FILE);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, now()->toIso8601String());
    }
}
