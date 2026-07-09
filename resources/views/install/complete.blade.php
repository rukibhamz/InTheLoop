@extends('install.layout')

@section('title', 'Complete')

@section('content')
    <h1>Installation complete</h1>
    <p class="subtitle">InTheLoop is ready. You are signed in as the administrator.</p>

    <p>Next steps:</p>
    <ul>
        <li>Configure branding in <a href="{{ route('settings.app') }}"><strong>App Settings</strong></a> and Microsoft Graph + SSO in <a href="{{ route('settings.microsoft') }}"><strong>Microsoft Integration</strong></a>.</li>
        <li>Set up your queue worker and scheduler for email sync jobs.</li>
        <li>Add <a href="{{ route('recipients.index') }}">recipients</a> and <a href="{{ route('categories.index') }}">categories</a> for your team.</li>
        <li>Invite staff via <a href="{{ route('users.index') }}">User Management</a>.</li>
    </ul>

    <div class="actions">
        <a href="{{ route('dashboard') }}" class="btn">Go to dashboard</a>
    </div>
@endsection
