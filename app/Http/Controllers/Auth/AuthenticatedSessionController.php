<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\PostLoginRedirect;
use App\Services\Auth\AppLoginGate;
use App\Services\Turnstile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(Request $request, Turnstile $turnstile): View
    {
        $adminContact = \App\Models\User::query()
            ->where('is_admin', true)
            ->where('is_active', true)
            ->orderBy('id')
            ->value('email');

        return view('auth.login', [
            'redirectTo' => $request->string('redirect')->toString() ?: null,
            'adminContact' => $adminContact,
            'turnstileEnabled' => $turnstile->isEnabled(),
            'turnstileSiteKey' => $turnstile->siteKey(),
        ]);
    }

    public function store(Request $request, AppLoginGate $loginGate, Turnstile $turnstile): RedirectResponse
    {
        $turnstile->validateOrFail(
            $request->input('cf-turnstile-response'),
            $request->ip()
        );

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('These credentials do not match our records.'),
            ]);
        }

        if (! $loginGate->userMayAuthenticate(Auth::user())) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Your account has been deactivated. Contact your administrator.',
            ]);
        }

        $request->session()->regenerate();

        if ($request->filled('redirect')) {
            return redirect()->to($request->string('redirect')->toString());
        }

        return PostLoginRedirect::to();
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
