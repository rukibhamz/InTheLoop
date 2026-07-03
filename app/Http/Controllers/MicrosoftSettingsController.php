<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateMicrosoftSettingsRequest;
use App\Jobs\SyncDirectoryContacts;
use App\Models\AppSetting;
use App\Services\Branding;
use App\Services\Graph\GraphSettings;
use App\Services\Graph\GraphTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MicrosoftSettingsController extends Controller
{
    public function edit(Branding $branding): View
    {
        return view('settings.microsoft', [
            'settings' => $branding->settings(),
            'redirectUri' => config('microsoft.redirect'),
        ]);
    }

    public function update(
        UpdateMicrosoftSettingsRequest $request,
        Branding $branding,
        GraphSettings $graphSettings,
        GraphTokenService $graphTokens
    ): RedirectResponse {
        $settings = AppSetting::query()->firstOrCreate(['id' => 1]);

        $settings->fill([
            'graph_tenant_id' => $request->string('graph_tenant_id')->toString() ?: null,
            'graph_client_id' => $request->string('graph_client_id')->toString() ?: null,
            'graph_default_sender_mailbox' => $request->string('graph_default_sender_mailbox')->toString() ?: null,
            'graph_monitored_mailboxes' => $request->string('graph_monitored_mailboxes')->toString() ?: null,
            'microsoft_tenant_id' => $request->string('microsoft_tenant_id')->toString() ?: null,
            'microsoft_client_id' => $request->string('microsoft_client_id')->toString() ?: null,
            'sso_enabled' => $request->boolean('sso_enabled'),
            'updated_at' => now(),
        ]);

        if ($request->filled('graph_client_secret')) {
            $settings->graph_client_secret = $request->string('graph_client_secret')->toString();
        }

        if ($request->filled('microsoft_client_secret')) {
            $settings->microsoft_client_secret = $request->string('microsoft_client_secret')->toString();
        }

        $settings->save();
        $branding->clearCache();
        $graphSettings->clearCache();
        $graphTokens->forgetCachedToken();

        if ($graphSettings->isConfigured()) {
            SyncDirectoryContacts::dispatch();
        }

        return back()->with('success', 'Microsoft settings saved.');
    }
}
