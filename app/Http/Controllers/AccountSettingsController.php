<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAccountSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountSettingsController extends Controller
{
    public function edit(): View
    {
        return view('settings.account', [
            'user' => auth()->user(),
        ]);
    }

    public function update(UpdateAccountSettingsRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->fill($request->only(['name', 'email', 'department', 'bio']));

        if ($request->has('notification_preferences')) {
            $user->notification_preferences = $request->input('notification_preferences');
        }

        if ($request->has('two_factor_enabled')) {
            $user->two_factor_enabled = $request->boolean('two_factor_enabled');
        }

        $user->save();

        return back()->with('success', 'Account settings saved.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->update(['is_active' => false]);

        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Your account has been deactivated.');
    }
}
