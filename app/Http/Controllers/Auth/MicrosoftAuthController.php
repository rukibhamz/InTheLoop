<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PostLoginRedirect;
use App\Services\Auth\AppLoginGate;
use App\Services\Graph\GraphUserProfileResolver;
use App\Services\MicrosoftSettings;
use App\Services\MicrosoftSocialiteConfigurator;
use App\Services\Turnstile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class MicrosoftAuthController extends Controller
{
    public function redirect(
        Request $request,
        MicrosoftSettings $settings,
        MicrosoftSocialiteConfigurator $configurator,
        Turnstile $turnstile
    ): RedirectResponse {
        if (! $settings->isSsoEnabled()) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Microsoft sign-in is not enabled. Use email and password or contact your administrator.']);
        }

        if ($turnstile->isEnabled()) {
            if (! $request->isMethod('post')) {
                return redirect()->route('login');
            }

            $turnstile->validateOrFail(
                $request->input('cf-turnstile-response'),
                $request->ip()
            );
        }

        $configurator->apply();

        return Socialite::driver('microsoft')
            ->scopes($settings->scopes())
            ->redirect();
    }

    public function callback(
        Request $request,
        MicrosoftSettings $settings,
        MicrosoftSocialiteConfigurator $configurator,
        AppLoginGate $loginGate,
        GraphUserProfileResolver $profileResolver
    ): RedirectResponse {
        if (! $settings->isSsoEnabled()) {
            return redirect()->route('login');
        }

        $configurator->apply();

        $microsoftUser = Socialite::driver('microsoft')->user();
        $azureObjectId = $microsoftUser->getId();
        $loginEmail = $microsoftUser->getEmail();
        $primaryMail = $profileResolver->primaryMail($azureObjectId, $loginEmail);

        $user = User::query()->where('azure_object_id', $azureObjectId)->first();

        if (! $user && filled($loginEmail)) {
            $email = strtolower($loginEmail);
            $user = User::query()
                ->whereRaw('lower(email) = ?', [$email])
                ->orWhereRaw('lower(shared_mailbox_email) = ?', [$email])
                ->first();
        }

        if ($user) {
            if (! $loginGate->userMayAuthenticate($user)) {
                throw ValidationException::withMessages([
                    'email' => 'Your account has been deactivated. Contact your administrator.',
                ]);
            }

            $user->forceFill([
                'azure_object_id' => $azureObjectId,
                'auth_method' => 'sso',
                'name' => $microsoftUser->getName() ?: $user->name,
            ]);

            if (filled($primaryMail)) {
                $user->syncSharedMailboxFromAzure($primaryMail);
            }

            $user->save();
        } else {
            if (! filled($loginEmail)) {
                throw ValidationException::withMessages([
                    'email' => 'Microsoft sign-in did not return an email address.',
                ]);
            }

            $user = User::query()->create([
                'name' => $microsoftUser->getName() ?: 'Microsoft User',
                'email' => $loginEmail,
                'shared_mailbox_email' => $primaryMail ?: $loginEmail,
                'azure_object_id' => $azureObjectId,
                'auth_method' => 'sso',
                'password' => null,
            ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return PostLoginRedirect::to();
    }
}
