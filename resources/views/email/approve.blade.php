@extends('layouts.app')

@section('title', 'Approve email')

@section('content')
    <div class="mx-auto max-w-xl">
        <div class="card">
            <h1 class="mb-2 text-2xl font-bold text-gray-800">Approve this email?</h1>
            <p class="mb-6 text-sm text-gray-500">{{ $email->subject }}</p>

            <div class="mb-6 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">
                {!! nl2br(e($email->body)) !!}
            </div>

            <div class="mobile-action-row">
                <form method="POST" action="{{ route('emails.approve', $email) }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <button type="submit" class="btn-primary">Approve</button>
                </form>

                <form method="POST" action="{{ route('emails.reject', $email) }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <button type="submit" class="btn-secondary">Reject</button>
                </form>

                <a href="{{ route('emails.show', $email) }}" class="btn-ghost">View full thread</a>
            </div>
        </div>
    </div>
@endsection
