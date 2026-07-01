<?php

function ensure_install_app_key(): void
{
    $envPath = dirname(__DIR__).'/.env';

    if (! file_exists($envPath)) {
        return;
    }

    $contents = file_get_contents($envPath);

    if (preg_match('/^APP_KEY=base64:.+/m', $contents)) {
        return;
    }

    $key = 'base64:'.base64_encode(random_bytes(32));

    if (preg_match('/^APP_KEY=.*$/m', $contents)) {
        $contents = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY='.$key, $contents);
    } else {
        $contents = rtrim($contents).PHP_EOL.'APP_KEY='.$key.PHP_EOL;
    }

    file_put_contents($envPath, $contents);

    putenv('APP_KEY='.$key);
    $_ENV['APP_KEY'] = $key;
    $_SERVER['APP_KEY'] = $key;
}
