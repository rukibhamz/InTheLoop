@extends('layouts.app')

@section('title', 'Announcements')

@section('content')
    <div class="mx-auto max-w-7xl">
        <div class="page-header">
            <div class="min-w-0">
                <h1 class="page-title">Announcements</h1>
                <p class="mt-1 text-sm text-on-surface-variant">Group and distribution-list mail captured separately from reports.</p>
            </div>
        </div>

        <section class="form-card mb-6 overflow-hidden p-0">
            <form method="GET" action="{{ route('announcements.index') }}" class="flex flex-col gap-3 border-b border-gray-100 p-3 sm:flex-row sm:items-center sm:p-4">
                <div class="relative min-w-0 flex-1">
                    <span class="material-symbols-outlined pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-[20px] text-gray-400">search</span>
                    <input type="search" name="q" value="{{ request('q') }}" class="form-input-icon" placeholder="Search subject, sender, or body...">
                </div>
                @if ($mailboxes->isNotEmpty())
                    <select name="mailbox" class="form-select w-full sm:max-w-[240px]" onchange="this.form.submit()">
                        <option value="">All mailboxes</option>
                        @foreach ($mailboxes as $mailbox)
                            <option value="{{ $mailbox }}" @selected(request('mailbox') === $mailbox)>{{ $mailbox }}</option>
                        @endforeach
                    </select>
                @endif
                <button type="submit" class="btn-secondary w-full py-2.5 sm:w-auto">Search</button>
            </form>
        </section>

        @if ($announcements->isEmpty())
            <div class="stat-card flex flex-col items-center py-16 text-center">
                <span class="material-symbols-outlined mb-4 text-[64px] text-gray-300">campaign</span>
                <h3 class="text-xl font-semibold text-gray-700">No announcements yet</h3>
                <p class="mt-2 max-w-md text-sm text-gray-500">
                    Configure announcement mailboxes under Settings → Microsoft. Inbound group mail that is not part of a report thread will appear here.
                </p>
            </div>
        @else
            <div class="grid gap-4">
                @foreach ($announcements as $announcement)
                    <a href="{{ route('announcements.show', $announcement) }}" class="report-grid-card group block">
                        <div class="mb-2 flex flex-wrap items-center gap-2">
                            <span class="category-pill">
                                <span class="material-symbols-outlined text-[14px]">mail</span>
                                {{ $announcement->mailbox }}
                            </span>
                        </div>

                        <h3 class="mb-2 line-clamp-2 text-base font-semibold text-on-surface group-hover:text-primary-600">
                            {{ $announcement->subject ?: '(No subject)' }}
                        </h3>

                        <p class="mb-4 line-clamp-2 text-sm text-on-surface-variant">
                            {{ Str::limit($announcement->displayBody(), 180) }}
                        </p>

                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-gray-100 pt-4 text-xs text-on-surface-variant">
                            <span class="inline-flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">person</span>
                                {{ $announcement->from_name ?: $announcement->from_email }}
                            </span>
                            <time datetime="{{ ($announcement->received_at ?? $announcement->created_at)->toIso8601String() }}">
                                {{ ($announcement->received_at ?? $announcement->created_at)->diffForHumans() }}
                            </time>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="pagination-wrap mt-6">
                {{ $announcements->links() }}
            </div>
        @endif
    </div>
@endsection
