@extends('layouts.app')

@section('title', $report->subject)

@section('content')
    @php
        $isAuthor = auth()->id() === $report->user_id;
        $canApprove = auth()->user()->can('approve', $report)
            && ! in_array($report->status, [\App\Enums\ReportStatus::Approved, \App\Enums\ReportStatus::Rejected, \App\Enums\ReportStatus::Resolved]);
        $graphConfigured = app(\App\Services\Graph\GraphSettings::class)->isConfigured();
        $pendingEmailReplies = $report->threadMessages->where('email_pending', true);
    @endphp

    <div class="mx-auto flex max-w-5xl flex-col lg:min-h-[calc(100vh-8rem)]" x-data="{ shareCopied: false }">
        <div class="mb-6 flex flex-col gap-4 border-b border-gray-200 pb-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0 flex-1">
                <a href="{{ route('reports.index') }}" class="mb-3 inline-flex items-center gap-1 text-sm font-medium text-primary-600 hover:underline">
                    <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                    Back to reports
                </a>

                <h1 class="break-words text-xl font-bold text-on-surface sm:text-2xl lg:text-3xl">{{ $report->subject }}</h1>

                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span class="category-pill">
                        <span class="material-symbols-outlined text-[14px]">corporate_fare</span>
                        {{ $report->category->name }}
                    </span>
                    <x-status-badge :status="$report->status" />
                </div>
            </div>

            <div class="mobile-action-row">
                <button
                    type="button"
                    class="btn-secondary gap-2 py-2"
                    @click="navigator.clipboard.writeText('{{ route('reports.show', $report) }}'); shareCopied = true; setTimeout(() => shareCopied = false, 2000)"
                >
                    <span class="material-symbols-outlined text-[18px]">share</span>
                    <span x-text="shareCopied ? 'Link copied!' : 'Share'"></span>
                </button>

                @if ($canApprove)
                    <form method="POST" action="{{ route('reports.reject', $report) }}" onsubmit="return confirm('Reject this report?')">
                        @csrf
                        <button type="submit" class="btn-secondary gap-2 py-2 text-danger-600">Reject</button>
                    </form>
                    <form method="POST" action="{{ route('reports.approve', $report) }}">
                        @csrf
                        <button type="submit" class="btn-primary gap-2">
                            <span class="material-symbols-outlined text-[18px]">check_circle</span>
                            Approve Report
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if ($pendingEmailReplies->isNotEmpty())
            <div class="info-banner mb-4">
                <span class="material-symbols-outlined shrink-0 text-warning-600">sync</span>
                <p class="text-sm text-on-surface-variant">
                    {{ $pendingEmailReplies->count() }} {{ Str::plural('reply', $pendingEmailReplies->count()) }}
                    waiting for your shared mailbox copy to sync before email delivery completes.
                </p>
            </div>
        @endif

        @can('updateStatus', $report)
            <form method="POST" action="{{ route('reports.status.update', $report) }}" class="mb-6 flex flex-col gap-3 rounded-xl border border-gray-200 bg-gray-50/80 p-4 sm:flex-row sm:flex-wrap sm:items-end">
                @csrf
                @method('PUT')
                <div class="w-full min-w-0 sm:flex-1 sm:max-w-xs">
                    <label for="status" class="form-label">Admin: override status</label>
                    <select id="status" name="status" class="form-select w-full">
                        @foreach (\App\Enums\ReportStatus::cases() as $statusOption)
                            <option value="{{ $statusOption->value }}" @selected($report->status === $statusOption)>{{ $statusOption->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn-secondary w-full py-2.5 sm:w-auto">Update Status</button>
            </form>
        @endcan

        <div class="flex-1 space-y-6 pb-6" role="log" aria-live="polite">
            {{-- Original submission --}}
            <div @class(['flex', 'justify-end' => $isAuthor, 'justify-start' => ! $isAuthor])>
                <article @class([
                    'thread-bubble-outbound' => $isAuthor,
                    'thread-bubble-inbound' => ! $isAuthor,
                ])>
                    <header class="mb-3 flex items-center gap-3">
                        <div @class([
                            'flex h-9 w-9 items-center justify-center rounded-full text-xs font-semibold',
                            'bg-white/20 text-white' => $isAuthor,
                            'bg-primary-100 text-primary-700' => ! $isAuthor,
                        ])>
                            {{ strtoupper(substr($report->user->name, 0, 1)) }}
                        </div>
                        <div>
                            <p @class(['text-sm font-semibold', 'text-white' => $isAuthor, 'text-gray-800' => ! $isAuthor])>
                                {{ $isAuthor ? 'You' : $report->user->name }}
                                @if ($isAuthor && auth()->user()->isAdmin())
                                    <span class="font-normal opacity-80">(Admin)</span>
                                @endif
                            </p>
                            <time @class(['text-xs', 'text-white/70' => $isAuthor, 'text-gray-500' => ! $isAuthor]) datetime="{{ $report->created_at->toIso8601String() }}">
                                {{ $report->created_at->format('g:i A') }}
                            </time>
                        </div>
                    </header>

                    <div @class(['text-sm leading-relaxed', 'text-white/95' => $isAuthor, 'text-gray-700' => ! $isAuthor])>
                        {!! nl2br(e($report->body)) !!}
                    </div>

                    @if ($report->attachments->isNotEmpty())
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($report->attachments as $attachment)
                                <a href="{{ route('attachments.download', $attachment) }}" @class([
                                    'attachment-chip hover:bg-gray-100',
                                    'border-white/20 bg-white/10 text-white hover:bg-white/20' => $isAuthor,
                                ])>
                                    <span class="material-symbols-outlined text-[16px]">attach_file</span>
                                    {{ $attachment->original_filename }}
                                    <span class="opacity-70">({{ number_format($attachment->size / 1024 / 1024, 1) }} MB)</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </article>
            </div>

            {{-- Thread messages --}}
            @foreach ($report->threadMessages as $message)
                @php
                    $isMine = in_array(strtolower($message->from_email), array_filter([
                        strtolower(auth()->user()->email),
                        strtolower(auth()->user()->shared_mailbox_email ?? ''),
                    ]), true)
                        || ($message->direction === \App\Enums\MessageDirection::Outbound && $message->mailbox === auth()->user()->shared_mailbox_email);
                @endphp
                <div @class(['flex', 'justify-end' => $isMine, 'justify-start' => ! $isMine])>
                    <article @class([
                        'thread-bubble-outbound' => $isMine,
                        'thread-bubble-inbound' => ! $isMine,
                    ])>
                        <header class="mb-2 flex items-center gap-3">
                            @unless ($isMine)
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 text-xs font-semibold text-primary-700">
                                    {{ strtoupper(substr($message->from_email, 0, 1)) }}
                                </div>
                            @endunless
                            <div @class(['text-right' => $isMine])>
                                <p @class(['text-sm font-semibold', 'text-white' => $isMine, 'text-gray-800' => ! $isMine])>
                                    {{ $isMine ? 'You' : $message->from_email }}
                                </p>
                                <time @class(['text-xs', 'text-white/70' => $isMine, 'text-gray-500' => ! $isMine]) datetime="{{ $message->created_at->toIso8601String() }}">
                                    {{ $message->created_at->format('g:i A') }}
                                </time>
                            </div>
                        </header>
                        <div @class(['text-sm leading-relaxed', 'text-white/95' => $isMine, 'text-gray-700' => ! $isMine])>
                            {!! nl2br(e($message->displayBody())) !!}
                        </div>
                        @if ($message->attachments->isNotEmpty())
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($message->attachments as $attachment)
                                    <a href="{{ route('attachments.download', $attachment) }}" @class([
                                        'attachment-chip hover:bg-gray-100',
                                        'border-white/30 text-white hover:bg-white/10' => $isMine,
                                    ])>
                                        <span class="material-symbols-outlined text-[16px]">attach_file</span>
                                        {{ $attachment->original_filename }}
                                        <span class="opacity-70">({{ number_format($attachment->size / 1024 / 1024, 1) }} MB)</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </article>
                </div>
            @endforeach

            {{-- System events --}}
            @foreach ($report->events->whereIn('type', ['approved', 'rejected', 'sent', 'replied']) as $event)
                <div class="thread-bubble-system">
                    <span class="material-symbols-outlined mr-1 align-middle text-[16px]">
                        {{ $event->type === 'approved' ? 'check_circle' : ($event->type === 'rejected' ? 'cancel' : 'send') }}
                    </span>
                    @switch($event->type)
                        @case('approved')
                            Report status updated to <strong>Approved</strong>
                            @break
                        @case('rejected')
                            Report marked as <strong>Revision Needed</strong>
                            @break
                        @case('sent')
                            Report emailed to recipients
                            @break
                        @case('replied')
                            New reply added to the conversation
                            @break
                    @endswitch
                    <span class="text-gray-400">· {{ $event->created_at->diffForHumans() }}</span>
                </div>
            @endforeach
        </div>

        @can('reply', $report)
        <div class="thread-composer mt-auto">
            <form method="POST" action="{{ route('reports.reply', $report) }}">
                @csrf
                <div class="p-4">
                    <textarea
                        name="body"
                        class="form-textarea min-h-[100px] resize-none border border-gray-200"
                        placeholder="Write a reply to this conversation..."
                        required
                        aria-label="Reply to conversation"
                    >{{ old('body') }}</textarea>
                    @error('body')<p class="form-error mt-2">{{ $message }}</p>@enderror

                    <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <span class="inline-flex items-center gap-1 rounded-lg bg-primary-50 px-3 py-1.5 text-xs font-medium text-primary-600">
                            <span class="material-symbols-outlined text-[16px]">alternate_email</span>
                            Visible to all thread participants
                        </span>
                        <button type="submit" class="btn-primary gap-2">
                            <span class="material-symbols-outlined text-[18px]">send</span>
                            Send Reply
                        </button>
                    </div>
                    <p class="mt-3 text-xs text-gray-400">
                        @if ($graphConfigured && auth()->user()->shared_mailbox_email)
                            Replies post in-app and send via Microsoft Graph to the email thread.
                        @elseif ($graphConfigured)
                            Replies post in-app. Assign a shared mailbox to your profile to send email replies.
                        @else
                            Replies are saved in-app. Configure Graph in App Settings to enable email delivery.
                        @endif
                    </p>
                </div>
            </form>
        </div>
        @else
        <div class="thread-composer mt-auto p-4 text-center text-sm text-gray-500">
            You need access to this report to reply.
        </div>
        @endcan
    </div>
@endsection
