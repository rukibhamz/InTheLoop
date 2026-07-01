<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\InstallState;
use Tests\TestCase;

class InstallWizardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (InstallState::isInstalled()) {
            $lock = storage_path('app/'.InstallState::LOCK_FILE);
            if (file_exists($lock)) {
                unlink($lock);
            }
        }

        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        $_SERVER['INSTALLED'] = 'false';
    }

    public function test_root_redirects_to_install_when_not_installed(): void
    {
        $this->get('/')
            ->assertRedirect(route('install.requirements'));
    }

    public function test_requirements_page_renders(): void
    {
        $this->get(route('install.requirements'))
            ->assertOk()
            ->assertSee('InTheLoop Setup');
    }

    public function test_sqlite_install_flow_completes(): void
    {
        $envPath = base_path('.env');
        $envBackup = file_get_contents($envPath);

        try {
            $sqlite = database_path('testing-install.sqlite');

            if (file_exists($sqlite)) {
                unlink($sqlite);
            }

            $this->post(route('install.database.store'), [
                'driver' => 'sqlite',
                'database' => $sqlite,
            ])->assertRedirect(route('install.application'));

            $this->post(route('install.application.store'), [
                'app_name' => 'InTheLoop Test',
                'app_url' => 'http://localhost',
            ])->assertRedirect(route('install.admin'));

            $this->post(route('install.finish'), [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])->assertRedirect(route('install.complete'));

            $this->assertTrue(InstallState::isInstalled());
            $this->assertDatabaseHas('users', [
                'email' => 'admin@example.com',
                'is_admin' => true,
            ]);

            $user = User::query()->where('email', 'admin@example.com')->first();
            $this->assertNotNull($user);

            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSee('Admin User');
        } finally {
            file_put_contents($envPath, $envBackup);

            $lock = storage_path('app/'.InstallState::LOCK_FILE);
            if (file_exists($lock)) {
                unlink($lock);
            }

            $sqlite = database_path('testing-install.sqlite');
            if (file_exists($sqlite)) {
                unlink($sqlite);
            }

            putenv('INSTALLED=false');
            $_ENV['INSTALLED'] = 'false';
            $_SERVER['INSTALLED'] = 'false';
        }
    }
}
