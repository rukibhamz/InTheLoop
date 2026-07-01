<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAppSettingsRequest;
use App\Models\AppSetting;
use App\Services\Branding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AppSettingsController extends Controller
{
    public function edit(Branding $branding): View
    {
        return view('settings.app', [
            'settings' => $branding->settings(),
        ]);
    }

    public function update(UpdateAppSettingsRequest $request, Branding $branding): RedirectResponse
    {
        $settings = AppSetting::query()->firstOrCreate(['id' => 1]);

        $settings->fill([
            'org_name' => $request->string('org_name')->toString(),
            'accent_color' => $request->string('accent_color')->toString(),
            'updated_at' => now(),
        ]);

        if ($request->boolean('remove_logo') && $settings->logo_path) {
            Storage::disk('local')->delete($settings->logo_path);
            $settings->logo_path = null;
        }

        if ($request->hasFile('logo')) {
            if ($settings->logo_path) {
                Storage::disk('local')->delete($settings->logo_path);
            }

            $settings->logo_path = $request->file('logo')->store('branding', 'local');
        }

        $settings->save();
        $branding->clearCache();

        return back()->with('success', 'App settings saved.');
    }
}
