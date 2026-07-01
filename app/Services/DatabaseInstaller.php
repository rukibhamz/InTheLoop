<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PDO;
use RuntimeException;
use Throwable;

class DatabaseInstaller
{
    public function __construct(
        private readonly EnvWriter $envWriter
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public function testConnection(array $config): void
    {
        $driver = $config['driver'];

        if ($driver === 'sqlite') {
            $database = str_replace('\\', '/', $config['database'] ?? database_path('database.sqlite'));

            if (! file_exists($database)) {
                touch($database);
            }

            new PDO('sqlite:'.$database);

            return;
        }

        $dsn = match ($driver) {
            'mysql' => sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $config['host'],
                $config['port'],
                $config['database']
            ),
            'pgsql' => sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $config['host'],
                $config['port'],
                $config['database']
            ),
            default => throw new RuntimeException("Unsupported database driver [{$driver}]."),
        };

        new PDO($dsn, (string) $config['username'], (string) $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function writeEnvironment(array $config): void
    {
        $values = [
            'DB_CONNECTION' => $config['driver'],
            'SESSION_DRIVER' => 'file',
            'CACHE_STORE' => 'file',
            'QUEUE_CONNECTION' => 'database',
        ];

        if ($config['driver'] === 'sqlite') {
            $database = str_replace('\\', '/', $config['database'] ?? database_path('database.sqlite'));
            $values['DB_DATABASE'] = $database;
        } else {
            $values['DB_HOST'] = (string) $config['host'];
            $values['DB_PORT'] = (string) $config['port'];
            $values['DB_DATABASE'] = (string) $config['database'];
            $values['DB_USERNAME'] = (string) $config['username'];
            $values['DB_PASSWORD'] = (string) ($config['password'] ?? '');
        }

        $this->envWriter->setMany($values);
    }

    public function ensureApplicationKey(): void
    {
        if (filled($this->envWriter->get('APP_KEY'))) {
            return;
        }

        Artisan::call('key:generate', ['--force' => true]);
    }

    public function migrate(): void
    {
        $this->refreshDatabaseConfig();

        Artisan::call('migrate', ['--force' => true]);
    }

    public function verifyConnection(): void
    {
        $this->refreshDatabaseConfig();

        try {
            DB::connection()->getPdo();
        } catch (Throwable $exception) {
            throw new RuntimeException('Database connection failed after writing .env: '.$exception->getMessage(), previous: $exception);
        }
    }

    private function refreshDatabaseConfig(): void
    {
        $connection = $this->envWriter->get('DB_CONNECTION') ?? 'mysql';

        putenv('DB_CONNECTION='.$connection);
        $_ENV['DB_CONNECTION'] = $connection;
        $_SERVER['DB_CONNECTION'] = $connection;

        if ($connection === 'sqlite') {
            $database = $this->envWriter->get('DB_DATABASE') ?? database_path('database.sqlite');
            app('config')->set('database.connections.sqlite.database', $database);
        } else {
            foreach (['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'] as $key) {
                $value = $this->envWriter->get($key) ?? '';
                $configKey = strtolower(str_replace('DB_', '', $key));
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
                app('config')->set("database.connections.{$connection}.{$configKey}", $value);
            }
        }

        app('config')->set('database.default', $connection);
        DB::purge($connection);
        DB::reconnect($connection);
    }
}
