@extends('layouts.app')

@section('title', 'Approve report')

@section('content')
    <div class="mx-auto max-w-xl">
        <div class="card">
            <h1 class="mb-2 text-2xl font-bold text-gray-800">Approve this report?</h1>
            <p class="mb-6 text-sm text-gray-500">{{ $report->subject }}</p>

            <div class="mb-6 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">
                {!! nl2br(e($report->body)) !!}
            </div>

            <div class="mobile-action-row">
                <form method="POST" action="{{ route('reports.approve', $report) }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <button type="submit" class="btn-primary">Approve</button>
                </form>

                <form method="POST" action="{{ route('reports.reject', $report) }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <button type="submit" class="btn-secondary">Reject</button>
                </form>

                <a href="{{ route('reports.show', $report) }}" class="btn-ghost">View full thread</a>
            </div>
        </div>
    </div>
@endsection
