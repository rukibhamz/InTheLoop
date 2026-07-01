<?php

namespace App\Console\Commands;

use App\Models\AppSetting;
use App\Models\User;
use App\Services\DatabaseInstaller;
use App\Services\EnvWriter;
use App\Services\InstallState;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class InstallSmokeTest extends Command
{
    protected $signature = 'intheloop:install-smoke-test';

    protected $description = 'Run a local SQLite install smoke test (development only)';

    public function handle(DatabaseInstaller $databaseInstaller, EnvWriter $envWriter): int
    {
        if (InstallState::isInstalled()) {
            $this->error('Application is already installed.');

            return self::FAILURE;
        }

        $sqlite = database_path('install_smoke.sqlite');
        $config = ['driver' => 'sqlite', 'database' => $sqlite];

        $databaseInstaller->testConnection($config);
        $databaseInstaller->writeEnvironment($config);
        $databaseInstaller->ensureApplicationKey();

        $envWriter->setMany([
            'APP_NAME' => 'InTheLoop',
            'APP_URL' => 'http://127.0.0.1:8765',
            'INSTALLED' => 'true',
            'SESSION_DRIVER' => 'database',
            'CACHE_STORE' => 'database',
        ]);

        $databaseInstaller->migrate();

        User::query()->create([
            'name' => 'Smoke Admin',
            'email' => 'smoke@intheloop.test',
            'password' => Hash::make('password123'),
            'auth_method' => 'local',
            'is_admin' => true,
            'is_approver' => true,
        ]);

        AppSetting::query()->updateOrCreate(
            ['id' => 1],
            ['org_name' => 'InTheLoop', 'updated_at' => now()]
        );

        InstallState::markInstalled();

        $this->info('Install smoke test completed successfully.');

        return self::SUCCESS;
    }
}
