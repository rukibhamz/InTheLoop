@extends('layouts.guest')

@section('title', 'Forgot Password')

@section('content')
    <main class="login-enter z-10 w-full max-w-[440px]">
        <div class="mb-8 text-center">
            <h1 class="text-2xl font-bold text-on-surface">Forgot password?</h1>
            <p class="mt-1 text-sm text-on-surface-variant">Enter your email and we'll send a reset link.</p>
        </div>

        <section class="login-card">
            @if (session('success'))
                <div class="mb-4 rounded-lg border border-success-500/20 bg-success-50 px-4 py-3 text-sm text-success-600">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="form-label">Email Address</label>
                    <input id="email" name="email" type="email" class="form-input" value="{{ old('email') }}" required autofocus>
                    @error('email')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="btn-sign-in w-full">Send Reset Link</button>
            </form>
        </section>

        <p class="mt-6 text-center text-sm">
            <a href="{{ route('login') }}" class="font-semibold text-primary-500 hover:underline">Back to sign in</a>
        </p>
    </main>
@endsection
