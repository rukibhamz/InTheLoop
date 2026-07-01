<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MicrosoftSettings;
use App\Services\MicrosoftSocialiteConfigurator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use RuntimeException;

class MicrosoftAuthController extends Controller
{
    public function redirect(
        MicrosoftSettings $settings,
        MicrosoftSocialiteConfigurator $configurator
    ): RedirectResponse {
        if (! $settings->isSsoEnabled()) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Microsoft sign-in is not enabled. Use email and password or contact your administrator.']);
        }

        $configurator->apply();

        return Socialite::driver('microsoft')
            ->scopes($settings->scopes())
            ->redirect();
    }

    public function callback(
        Request $request,
        MicrosoftSettings $settings,
        MicrosoftSocialiteConfigurator $configurator
    ): RedirectResponse {
        if (! $settings->isSsoEnabled()) {
            return redirect()->route('login');
        }

        $configurator->apply();

        $microsoftUser = Socialite::driver('microsoft')->user();

        $user = User::query()->where('azure_object_id', $microsoftUser->getId())->first();

        if (! $user && $microsoftUser->getEmail()) {
            $user = User::query()->where('email', $microsoftUser->getEmail())->first();
        }

        if ($user) {
            if (! $user->is_active) {
                throw ValidationException::withMessages([
                    'email' => 'Your account has been deactivated. Contact your administrator.',
                ]);
            }

            $user->forceFill([
                'azure_object_id' => $microsoftUser->getId(),
                'auth_method' => 'sso',
                'name' => $microsoftUser->getName() ?: $user->name,
            ]);

            if ($microsoftUser->getEmail()) {
                $user->syncSharedMailboxFromAzure($microsoftUser->getEmail());
            }

            $user->save();
        } else {
            $email = $microsoftUser->getEmail() ?: throw new RuntimeException('Microsoft account did not return an email address.');

            $user = User::query()->create([
                'name' => $microsoftUser->getName() ?: 'Microsoft User',
                'email' => $email,
                'shared_mailbox_email' => $email,
                'azure_object_id' => $microsoftUser->getId(),
                'auth_method' => 'sso',
                'password' => null,
            ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
