<?php

/**
 * Ensure APP_KEY exists before Laravel boots encryption.
 * Critical for first deploys on cPanel where .env may be copied without a key,
 * or config may have been cached with an empty key.
 */
function ensure_install_app_key(): void
{
    $basePath = dirname(__DIR__);
    $envPath = $basePath.'/.env';
    $examplePath = $basePath.'/.env.example';
    $cachedConfig = $basePath.'/bootstrap/cache/config.php';

    if (! file_exists($envPath) && file_exists($examplePath)) {
        @copy($examplePath, $envPath);
    }

    if (! file_exists($envPath)) {
        return;
    }

    $contents = file_get_contents($envPath) ?: '';
    $hasKey = (bool) preg_match('/^APP_KEY=base64:.+/m', $contents);

    if ($hasKey) {
        // Cached config built with an empty key still breaks production — drop it.
        if (file_exists($cachedConfig)) {
            $cached = @file_get_contents($cachedConfig) ?: '';
            if (str_contains($cached, "'key' => ''") || str_contains($cached, '"key" => ""')) {
                @unlink($cachedConfig);
            }
        }

        return;
    }

    $key = 'base64:'.base64_encode(random_bytes(32));

    if (preg_match('/^APP_KEY=.*$/m', $contents)) {
        $contents = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY='.$key, $contents, 1);
    } else {
        $contents = rtrim($contents).PHP_EOL.'APP_KEY='.$key.PHP_EOL;
    }

    $written = @file_put_contents($envPath, $contents);

    // Always apply for this request even if .env is not writable.
    putenv('APP_KEY='.$key);
    $_ENV['APP_KEY'] = $key;
    $_SERVER['APP_KEY'] = $key;

    // Stale config cache with empty key causes MissingAppKeyException.
    if (file_exists($cachedConfig)) {
        @unlink($cachedConfig);
    }

    if ($written === false) {
        error_log('InTheLoop: could not write APP_KEY to .env — check file permissions on '.$envPath);
    }
}
