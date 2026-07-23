@extends('layouts.guest')

@section('title', 'Login')

@section('content')
    <main
        class="login-enter z-10 w-full max-w-[440px]"
        x-data="loginGate(@js($turnstileEnabled), @js($turnstileSiteKey))"
    >
        {{-- Branding --}}
        <div class="mb-8 flex flex-col items-center text-center">
            @if ($branding->logoUrl())
                <img src="{{ $branding->logoUrl() }}" alt="{{ $branding->orgName() }}" class="mb-4 h-12 w-auto max-w-[200px] object-contain">
            @else
                <div class="mb-4 rounded-xl bg-primary-500 p-2">
                    <span class="material-symbols-outlined material-symbols-filled text-[32px] text-white">sync_alt</span>
                </div>
            @endif

            <h1 class="text-3xl font-bold tracking-tight text-primary-500 sm:text-4xl">{{ $branding->orgName() }}</h1>
            <p class="mt-1 text-sm text-on-surface-variant">Professional Internal Email &amp; Approvals</p>
        </div>

        {{-- Login card --}}
        <section class="login-card">
            @if (session('success'))
                <div class="alert-error mb-6 border-success-500/20 bg-success-50 text-success-600" role="alert">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert-error mb-6" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="space-y-6">
                @if ($turnstileEnabled)
                    <div class="flex justify-center">
                        <div x-ref="turnstile"></div>
                    </div>
                @endif

                {{-- Microsoft SSO --}}
                @if ($microsoftSettings->isSsoEnabled())
                    <form method="POST" action="{{ route('auth.microsoft.redirect') }}">
                        @csrf
                        <input type="hidden" name="cf-turnstile-response" :value="token">
                        <button
                            type="submit"
                            class="btn-microsoft"
                            :disabled="!canSubmit"
                            :aria-disabled="(!canSubmit).toString()"
                            title="{{ $turnstileEnabled ? 'Verifying browser…' : '' }}"
                        >
                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 21 21" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <rect x="1" y="1" width="9" height="9" fill="#f25022"/>
                                <rect x="11" y="1" width="9" height="9" fill="#7fba00"/>
                                <rect x="1" y="11" width="9" height="9" fill="#00a4ef"/>
                                <rect x="11" y="11" width="9" height="9" fill="#ffb900"/>
                            </svg>
                            Sign in with Microsoft
                        </button>
                    </form>
                @else
                    <button
                        type="button"
                        class="btn-microsoft"
                        disabled
                        title="Microsoft SSO will be configured in admin settings"
                    >
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 21 21" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <rect x="1" y="1" width="9" height="9" fill="#f25022"/>
                            <rect x="11" y="1" width="9" height="9" fill="#7fba00"/>
                            <rect x="1" y="11" width="9" height="9" fill="#00a4ef"/>
                            <rect x="11" y="11" width="9" height="9" fill="#ffb900"/>
                        </svg>
                        Sign in with Microsoft
                    </button>
                @endif

                {{-- Divider --}}
                <div class="relative flex items-center py-2">
                    <div class="flex-grow border-t border-outline-variant"></div>
                    <span class="mx-4 shrink-0 text-xs font-medium text-on-surface-variant">or</span>
                    <div class="flex-grow border-t border-outline-variant"></div>
                </div>

                {{-- Local login --}}
                <form
                    method="POST"
                    action="{{ route('login') }}"
                    class="space-y-4"
                    @submit="if (!canSubmit) { $event.preventDefault(); return; } submitting = true"
                >
                    @csrf
                    @if (! empty($redirectTo))
                        <input type="hidden" name="redirect" value="{{ $redirectTo }}">
                    @endif
                    <input type="hidden" name="cf-turnstile-response" :value="token">

                    <div class="space-y-1">
                        <label for="email" class="text-xs font-semibold text-on-surface">Email Address</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute top-1/2 left-4 -translate-y-1/2 text-[20px] text-outline" aria-hidden="true">mail</span>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                class="login-input"
                                value="{{ old('email') }}"
                                placeholder="name@company.com"
                                required
                                autofocus
                                autocomplete="username"
                            >
                        </div>
                    </div>

                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <label for="password" class="text-xs font-semibold text-on-surface">Password</label>
                            <a href="{{ route('password.request') }}" class="text-xs font-medium text-primary-500 hover:underline">Forgot password?</a>
                        </div>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute top-1/2 left-4 -translate-y-1/2 text-[20px] text-outline" aria-hidden="true">lock</span>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                class="login-input"
                                placeholder="••••••••"
                                required
                                autocomplete="current-password"
                            >
                        </div>
                    </div>

                    <input type="hidden" name="remember" value="1">

                    <button
                        type="submit"
                        class="btn-sign-in mt-2"
                        :disabled="!canSubmit || submitting"
                        :aria-disabled="(!canSubmit || submitting).toString()"
                        title="{{ $turnstileEnabled ? 'Verifying browser…' : '' }}"
                    >
                        <span x-show="!submitting">Sign In</span>
                        <span x-show="submitting" x-cloak>Signing in...</span>
                    </button>
                </form>
            </div>
        </section>

        {{-- Footer --}}
        <footer class="mt-8 space-y-4 text-center">
            <p class="text-sm text-on-surface-variant">
                New to {{ $branding->orgName() }}?
                @if (! empty($adminContact))
                    <a href="mailto:{{ $adminContact }}?subject={{ rawurlencode('InTheLoop access request') }}" class="font-semibold text-primary-500 hover:underline">Contact Administrator</a>
                @else
                    <span class="font-semibold text-on-surface">Contact your IT administrator for access.</span>
                @endif
            </p>
            <div class="flex justify-center gap-6">
                <a href="{{ route('password.request') }}" class="text-xs font-medium text-on-surface-variant transition hover:text-primary-500">Reset Password</a>
                @if (! empty($adminContact))
                    <a href="mailto:{{ $adminContact }}?subject={{ rawurlencode('InTheLoop support') }}" class="text-xs font-medium text-on-surface-variant transition hover:text-primary-500">Help Center</a>
                @endif
            </div>
        </footer>
    </main>
@endsection

@if ($turnstileEnabled)
    @push('head')
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" async defer></script>
    @endpush
@endif
