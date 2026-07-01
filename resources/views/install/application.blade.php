@extends('install.layout')

@section('title', 'Application')

@section('content')
    <h1>Application settings</h1>
    <p class="subtitle">Step 3 of 4 — Basic site details, saved to <code>.env</code> on the final step.</p>

    <div class="steps">
        <span class="step">Requirements</span>
        <span class="step">Database</span>
        <span class="step active">Application</span>
        <span class="step">Administrator</span>
    </div>

    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('install.application.store') }}">
        @csrf

        <label for="app_name">Application name</label>
        <input id="app_name" name="app_name" value="{{ $defaults['app_name'] }}" required>

        <label for="app_url">Application URL</label>
        <input id="app_url" name="app_url" value="{{ $defaults['app_url'] }}" required>
        <p class="help">Use the public URL staff will visit (no trailing slash).</p>

        <div class="actions">
            <a href="{{ route('install.database') }}" class="btn secondary">Back</a>
            <button type="submit" class="btn">Continue</button>
        </div>
    </form>
@endsection
