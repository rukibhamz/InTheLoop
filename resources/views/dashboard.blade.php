<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard — {{ config('app.name') }}</title>
    <style>
        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, sans-serif;
            background: #f4f6f8;
            color: #1f2937;
        }
        header {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        main { max-width: 960px; margin: 2rem auto; padding: 0 1.5rem; }
        .card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.5rem;
        }
        .btn {
            display: inline-block;
            padding: 0.55rem 0.9rem;
            border-radius: 8px;
            background: #e5e7eb;
            color: #1f2937;
            text-decoration: none;
            border: 0;
            cursor: pointer;
            font: inherit;
        }
        form { display: inline; }
    </style>
</head>
<body>
    <header>
        <strong>{{ config('app.name') }}</strong>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn">Sign out</button>
        </form>
    </header>

    <main>
        <div class="card">
            <h1>Welcome, {{ $user->name }}</h1>
            <p>You are signed in as <strong>{{ $user->email }}</strong>.</p>
            <p>Admin settings for Microsoft Graph, SSO, and branding will be available here in the next phase.</p>
        </div>
    </main>
</body>
</html>
