@extends('layouts.guest')

@section('title', 'Reset Password')

@section('content')
    <main class="login-enter z-10 w-full max-w-[440px]">
        <div class="mb-8 text-center">
            <h1 class="text-2xl font-bold text-on-surface">Reset password</h1>
            <p class="mt-1 text-sm text-on-surface-variant">Choose a new password for your account.</p>
        </div>

        <section class="login-card">
            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="email" class="form-label">Email</label>
                    <input id="email" name="email" type="email" class="form-input" value="{{ old('email', $email) }}" required>
                    @error('email')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password" class="form-label">New Password</label>
                    <input id="password" name="password" type="password" class="form-input" required>
                    @error('password')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="form-input" required>
                </div>

                <button type="submit" class="btn-sign-in w-full">Reset Password</button>
            </form>
        </section>
    </main>
@endsection
