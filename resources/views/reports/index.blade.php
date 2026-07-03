@extends('layouts.app')

@section('title', 'Reports Dashboard')

@section('content')
    <div class="mx-auto max-w-7xl">
        <div class="page-header">
            <div class="min-w-0">
                <h1 class="page-title">Reports Dashboard</h1>
                <p class="mt-1 text-sm text-on-surface-variant">Track submissions and conversations you're part of.</p>
            </div>
        </div>

        <div class="mb-8 grid gap-4 sm:grid-cols-2">
            <div class="stat-card">
                <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                    <span class="material-symbols-outlined">send</span>
                </div>
                <p class="text-xs font-semibold tracking-wide text-on-surface-variant uppercase">Sent</p>
                <p class="mt-1 text-3xl font-bold text-on-surface">{{ number_format($stats['sent']) }}</p>
                <p class="mt-1 text-sm text-on-surface-variant">Outbound emails</p>
            </div>

            <div class="stat-card">
                <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-info-50 text-info-500">
                    <span class="material-symbols-outlined">reply</span>
                </div>
                <p class="text-xs font-semibold tracking-wide text-on-surface-variant uppercase">Replied</p>
                <p class="mt-1 text-3xl font-bold text-on-surface">{{ number_format($stats['replied']) }}</p>
                <p class="mt-1 text-sm text-on-surface-variant">Inbound replies</p>
            </div>
        </div>

        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-lg font-semibold text-on-surface">Recent Submissions</h2>
            <form method="GET" action="{{ route('reports.index') }}" class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                <select name="status" class="form-select w-full py-2 text-sm sm:w-auto" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach (\App\Enums\ReportStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
                @if (request('status'))
                    <a href="{{ route('reports.index') }}" class="btn-ghost py-2 text-sm">Clear</a>
                @endif
            </form>
        </div>

        @if ($reports->isEmpty())
            <div class="stat-card flex flex-col items-center py-16 text-center">
                <span class="material-symbols-outlined mb-4 text-[64px] text-gray-300">description</span>
                <h3 class="text-xl font-semibold text-gray-700">No reports yet</h3>
                <p class="mt-2 max-w-md text-sm text-gray-500">When you create or are added to a report, it will appear here.</p>
                <a href="{{ route('reports.create') }}" class="btn-primary mt-6 gap-2">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    Create First Report
                </a>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($reports as $report)
                    <a href="{{ route('reports.show', $report) }}" class="report-grid-card group block">
                        <div class="mb-3">
                            <x-status-badge :status="$report->status" />
                        </div>

                        <h3 class="mb-2 line-clamp-2 text-base font-semibold text-on-surface group-hover:text-primary-600">
                            {{ $report->subject }}
                        </h3>

                        <p class="mb-4 line-clamp-2 flex-1 text-sm text-on-surface-variant">
                            {{ Str::limit(strip_tags($report->body), 140) }}
                        </p>

                        <div class="mt-auto flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-gray-100 pt-4 text-xs text-on-surface-variant">
                            <span class="category-pill">
                                <span class="material-symbols-outlined text-[14px]">folder</span>
                                {{ $report->category->name }}
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">person</span>
                                {{ $report->user->name }}
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">chat_bubble</span>
                                {{ $report->messages_count }}
                            </span>
                            <time class="w-full text-on-surface-variant/80 sm:ml-auto sm:w-auto" datetime="{{ $report->created_at->toIso8601String() }}">
                                {{ $report->created_at->diffForHumans() }}
                            </time>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="pagination-wrap mt-6">
                {{ $reports->links() }}
            </div>
        @endif
    </div>
@endsection
