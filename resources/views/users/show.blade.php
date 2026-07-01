@extends('layouts.app')

@section('title', $user->name)

@section('content')
    <div class="mx-auto max-w-xl">
        <div class="form-card text-center">
            <div class="user-avatar mx-auto mb-4 h-24 w-24 text-2xl">{{ strtoupper(substr($user->name, 0, 2)) }}</div>
            <h1 class="text-2xl font-bold text-on-surface">{{ $user->name }}</h1>
            <p class="text-on-surface-variant">{{ $user->email }}</p>
            @if ($user->department)
                <p class="mt-1 text-sm text-on-surface-variant">{{ $user->department }}</p>
            @endif
            @if ($user->bio)
                <p class="mt-4 text-sm text-gray-700">{{ $user->bio }}</p>
            @endif
            <p class="mt-4 text-xs text-on-surface-variant">Member since {{ $user->created_at->format('M Y') }}</p>

            @if (auth()->user()->isAdmin())
                <a href="{{ route('users.edit', $user) }}" class="btn-primary mt-6 inline-flex">Edit User</a>
            @endif
        </div>
    </div>
@endsection
