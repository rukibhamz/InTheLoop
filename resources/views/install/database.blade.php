@extends('install.layout')

@section('title', 'Database')

@section('content')
    <h1>Database connection</h1>
    <p class="subtitle">Step 2 of 4 — Enter database credentials. Settings are validated now and saved to <code>.env</code> when you finish installation.</p>

    <div class="steps">
        <span class="step">Requirements</span>
        <span class="step active">Database</span>
        <span class="step">Application</span>
        <span class="step">Administrator</span>
    </div>

    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('install.database.store') }}">
        @csrf

        <label for="driver">Database type</label>
        <select id="driver" name="driver" data-driver>
            <option value="mysql" @selected($defaults['driver'] === 'mysql')>MySQL</option>
            <option value="pgsql" @selected($defaults['driver'] === 'pgsql')>PostgreSQL</option>
            <option value="sqlite" @selected($defaults['driver'] === 'sqlite')>SQLite (local dev)</option>
        </select>

        <div class="grid" data-sqlite-hide>
            <div class="field">
                <label for="host">Host</label>
                <input id="host" name="host" value="{{ $defaults['host'] }}">
            </div>
            <div class="field">
                <label for="port">Port</label>
                <input id="port" name="port" value="{{ $defaults['port'] }}">
            </div>
        </div>

        <label for="database">Database name</label>
        <input id="database" name="database" value="{{ $defaults['database'] }}" required>
        <p class="help">For SQLite, use the full path (e.g. <code>{{ database_path('database.sqlite') }}</code>).</p>

        <div class="grid" data-sqlite-hide>
            <div class="field">
                <label for="username">Username</label>
                <input id="username" name="username" value="{{ $defaults['username'] }}">
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password">
            </div>
        </div>

        <div class="actions">
            <a href="{{ route('install.requirements') }}" class="btn secondary">Back</a>
            <button type="submit" class="btn">Test connection &amp; continue</button>
        </div>
    </form>
@endsection
