<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\User;
use App\Services\DatabaseInstaller;
use App\Services\EnvWriter;
use App\Services\InstallState;
use App\Services\RequirementsChecker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Throwable;

class InstallController extends Controller
{
    public function requirements(RequirementsChecker $checker): View|RedirectResponse
    {
        if ($checker->passes()) {
            return view('install.requirements', [
                'requirements' => $checker->check(),
                'passed' => true,
            ]);
        }

        return view('install.requirements', [
            'requirements' => $checker->check(),
            'passed' => false,
        ]);
    }

    public function database(): View
    {
        return view('install.database', [
            'defaults' => [
                'driver' => old('driver', 'mysql'),
                'host' => old('host', '127.0.0.1'),
                'port' => old('port', '3306'),
                'database' => old('database', 'intheloop'),
                'username' => old('username', 'root'),
            ],
        ]);
    }

    public function storeDatabase(Request $request, DatabaseInstaller $databaseInstaller): RedirectResponse
    {
        $validated = $request->validate([
            'driver' => ['required', 'in:mysql,pgsql,sqlite'],
            'host' => ['required_unless:driver,sqlite', 'nullable', 'string', 'max:255'],
            'port' => ['required_unless:driver,sqlite', 'nullable', 'string', 'max:10'],
            'database' => ['required', 'string', 'max:255'],
            'username' => ['required_unless:driver,sqlite', 'nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $databaseInstaller->testConnection($validated);
        } catch (Throwable $exception) {
            return back()
                ->withInput()
                ->withErrors(['database' => $exception->getMessage()]);
        }

        $request->session()->put('install.database', $validated);

        return redirect()->route('install.application');
    }

    public function application(): View|RedirectResponse
    {
        if (! session()->has('install.database')) {
            return redirect()->route('install.database');
        }

        return view('install.application', [
            'defaults' => [
                'app_name' => old('app_name', 'InTheLoop'),
                'app_url' => old('app_url', url('/')),
            ],
        ]);
    }

    public function storeApplication(Request $request): RedirectResponse
    {
        if (! session()->has('install.database')) {
            return redirect()->route('install.database');
        }

        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:255'],
            'app_url' => ['required', 'url', 'max:255'],
        ]);

        $request->session()->put('install.application', $validated);

        return redirect()->route('install.admin');
    }

    public function admin(): View|RedirectResponse
    {
        if (! session()->has('install.application')) {
            return redirect()->route('install.application');
        }

        return view('install.admin');
    }

    public function finish(
        Request $request,
        DatabaseInstaller $databaseInstaller,
        EnvWriter $envWriter
    ): RedirectResponse {
        if (! session()->has('install.application')) {
            return redirect()->route('install.application');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $databaseConfig = $request->session()->get('install.database');
            $applicationConfig = $request->session()->get('install.application');

            $databaseInstaller->writeEnvironment($databaseConfig);
            $databaseInstaller->ensureApplicationKey();

            $envWriter->setMany([
                'APP_NAME' => $applicationConfig['app_name'],
                'APP_URL' => rtrim($applicationConfig['app_url'], '/'),
                'VITE_APP_NAME' => $applicationConfig['app_name'],
                'INSTALLED' => 'true',
                'SESSION_DRIVER' => 'database',
                'CACHE_STORE' => 'database',
            ]);

            $databaseInstaller->migrate();
            $databaseInstaller->verifyConnection();

            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'auth_method' => 'local',
                'is_admin' => true,
                'is_approver' => true,
            ]);

            AppSetting::query()->updateOrCreate(
                ['id' => 1],
                [
                    'org_name' => $applicationConfig['app_name'],
                    'updated_at' => now(),
                ]
            );

            InstallState::markInstalled();

            $request->session()->forget(['install.database', 'install.application']);
            $request->session()->regenerate();
        } catch (Throwable $exception) {
            return back()
                ->withInput()
                ->withErrors(['install' => $exception->getMessage()]);
        }

        auth()->login($user);

        return redirect()->route('install.complete');
    }

    public function complete(): View|RedirectResponse
    {
        if (! InstallState::isInstalled()) {
            return redirect()->route('install.requirements');
        }

        return view('install.complete');
    }
}
