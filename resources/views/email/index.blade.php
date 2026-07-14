@extends('layouts.app')

@section('title', 'Email')

@section('content')
    @php
        $statusQuery = request()->only('status');
        $inboxUrl = route('emails.index', array_filter(['folder' => 'inbox'] + $statusQuery));
        $sentUrl = route('emails.index', array_filter(['folder' => 'sent'] + $statusQuery));
        $allUrl = route('emails.index', $statusQuery);
        $listTitle = match ($folder) {
            'inbox' => 'Inbox',
            'sent' => 'Sent',
            default => 'Recent Emails',
        };
    @endphp

    <div class="mx-auto max-w-7xl">
        <div class="page-header">
            <div class="min-w-0">
                <h1 class="page-title">Email</h1>
                <p class="mt-1 text-sm text-on-surface-variant">Track outbound mail and conversations you're part of.</p>
            </div>
        </div>

        <div class="mb-8 grid gap-4 sm:grid-cols-2">
            <a
                href="{{ $folder === 'inbox' ? $allUrl : $inboxUrl }}"
                class="stat-card block transition hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 {{ $folder === 'inbox' ? 'stat-card-active' : '' }}"
                aria-pressed="{{ $folder === 'inbox' ? 'true' : 'false' }}"
            >
                <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-info-50 text-info-500">
                    <span class="material-symbols-outlined">inbox</span>
                </div>
                <p class="text-xs font-semibold tracking-wide text-on-surface-variant uppercase">Inbox</p>
                <p class="mt-1 text-3xl font-bold text-on-surface">{{ number_format($stats['inbox']) }}</p>
                <p class="mt-1 text-sm text-on-surface-variant">Mail sent to you</p>
            </a>

            <a
                href="{{ $folder === 'sent' ? $allUrl : $sentUrl }}"
                class="stat-card block transition hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 {{ $folder === 'sent' ? 'stat-card-active' : '' }}"
                aria-pressed="{{ $folder === 'sent' ? 'true' : 'false' }}"
            >
                <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                    <span class="material-symbols-outlined">send</span>
                </div>
                <p class="text-xs font-semibold tracking-wide text-on-surface-variant uppercase">Sent</p>
                <p class="mt-1 text-3xl font-bold text-on-surface">{{ number_format($stats['sent']) }}</p>
                <p class="mt-1 text-sm text-on-surface-variant">Mail you sent</p>
            </a>
        </div>

        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-lg font-semibold text-on-surface">{{ $listTitle }}</h2>
            <form method="GET" action="{{ route('emails.index') }}" class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                @if ($folder)
                    <input type="hidden" name="folder" value="{{ $folder }}">
                @endif
                <select name="status" class="form-select w-full py-2 text-sm sm:w-auto" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach (\App\Enums\EmailStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
                @if (request('status') || $folder)
                    <a href="{{ route('emails.index') }}" class="btn-ghost py-2 text-sm">Clear</a>
                @endif
            </form>
        </div>

        @if ($emails->isEmpty())
            <div class="stat-card flex flex-col items-center py-16 text-center">
                <span class="material-symbols-outlined mb-4 text-[64px] text-gray-300">{{ $folder === 'sent' ? 'send' : 'inbox' }}</span>
                <h3 class="text-xl font-semibold text-gray-700">
                    @if ($folder === 'inbox')
                        Inbox is empty
                    @elseif ($folder === 'sent')
                        No sent emails yet
                    @else
                        No emails yet
                    @endif
                </h3>
                <p class="mt-2 max-w-md text-sm text-gray-500">
                    @if ($folder === 'inbox')
                        When someone includes you on an email, it will appear here.
                    @elseif ($folder === 'sent')
                        Emails you compose will appear here after you send them.
                    @else
                        When you send or are added to an email thread, it will appear here.
                    @endif
                </p>
                @if ($folder !== 'inbox')
                    <a href="{{ route('emails.create') }}" class="btn-primary mt-6 gap-2">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        Compose First Email
                    </a>
                @endif
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($emails as $email)
                    <a href="{{ route('emails.show', $email) }}" class="report-grid-card group block">
                        <div class="mb-3">
                            <x-status-badge :status="$email->status" />
                        </div>

                        <h3 class="mb-2 line-clamp-2 text-base font-semibold text-on-surface group-hover:text-primary-600">
                            {{ $email->subject }}
                        </h3>

                        <p class="mb-4 line-clamp-2 flex-1 text-sm text-on-surface-variant">
                            {{ Str::limit(strip_tags($email->body), 140) }}
                        </p>

                        <div class="mt-auto flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-gray-100 pt-4 text-xs text-on-surface-variant">
                            <span class="category-pill">
                                <span class="material-symbols-outlined text-[14px]">folder</span>
                                {{ $email->category->name }}
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">person</span>
                                {{ $email->user->name }}
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">chat_bubble</span>
                                {{ $email->messages_count }}
                            </span>
                            <time class="w-full text-on-surface-variant/80 sm:ml-auto sm:w-auto" datetime="{{ $email->created_at->toIso8601String() }}">
                                {{ $email->created_at->diffForHumans() }}
                            </time>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="pagination-wrap mt-6">
                {{ $emails->links() }}
            </div>
        @endif
    </div>
@endsection
