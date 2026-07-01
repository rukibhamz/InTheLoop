@extends('install.layout')

@section('title', 'Requirements')

@section('content')
    <h1>InTheLoop Setup</h1>
    <p class="subtitle">Step 1 of 4 — Check that this server can run the application.</p>

    <div class="steps">
        <span class="step active">Requirements</span>
        <span class="step">Database</span>
        <span class="step">Application</span>
        <span class="step">Administrator</span>
    </div>

    <ul class="list">
        @foreach ($requirements as $requirement)
            <li>
                <span>{{ $requirement['name'] }}</span>
                <span class="badge {{ $requirement['passed'] ? 'ok' : 'fail' }}">
                    {{ $requirement['passed'] ? 'OK' : 'Failed' }}
                </span>
            </li>
            @unless ($requirement['passed'])
                <li style="border: 0; padding-top: 0; color: var(--muted); font-size: 0.875rem;">
                    {{ $requirement['message'] }}
                </li>
            @endunless
        @endforeach
    </ul>

    <div class="actions">
        @if ($passed)
            <a href="{{ route('install.database') }}" class="btn">Continue</a>
        @else
            <button class="btn" disabled>Fix requirements to continue</button>
        @endif
    </div>
@endsection
