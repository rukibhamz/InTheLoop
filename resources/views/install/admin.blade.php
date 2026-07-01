@extends('install.layout')

@section('title', 'Administrator')

@section('content')
    <h1>Administrator account</h1>
    <p class="subtitle">Step 4 of 4 — Create the first admin user. Microsoft SSO and other integrations can be configured later in admin settings.</p>

    <div class="steps">
        <span class="step">Requirements</span>
        <span class="step">Database</span>
        <span class="step">Application</span>
        <span class="step active">Administrator</span>
    </div>

    @if ($errors->any())
        <div class="error">{{ $errors->first('install') ?? $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('install.finish') }}">
        @csrf

        <label for="name">Full name</label>
        <input id="name" name="name" value="{{ old('name') }}" required>

        <label for="email">Email address</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required>

        <label for="password">Password</label>
        <input id="password" type="password" name="password" required>

        <label for="password_confirmation">Confirm password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required>

        <div class="actions">
            <a href="{{ route('install.application') }}" class="btn secondary">Back</a>
            <button type="submit" class="btn">Install InTheLoop</button>
        </div>
    </form>
@endsection
