@extends('layouts.app')

@section('title', $report->subject)

@section('content')
    @php
        $isAuthor = auth()->id() === $report->user_id;
        $canApprove = auth()->user()->can('approve', $report)
            && ! in_array($report->status, [\App\Enums\ReportStatus::Approved, \App\Enums\ReportStatus::Rejected, \App\Enums\ReportStatus::Resolved]);
        $graphConfigured = app(\App\Services\Graph\GraphSettings::class)->isConfigured();
        $pendingEmailReplies = $report->threadMessages->where('email_pending', true);
        $myEmails = array_map('strtolower', array_filter([
            auth()->user()->email,
            auth()->user()->shared_mailbox_email,
        ]));
        $toRecipients = $directory->formattedParticipants($report->participants->where('type', 'to'));
        $ccRecipients = $directory->formattedParticipants($report->participants->where('type', 'cc'));
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

        <div class="flex-1 pb-6" role="log" aria-live="polite">
            <div class="email-thread">
                {{-- Original submission --}}
                <article @class(['email-message', 'email-message-mine' => $isAuthor])>
                    <div class="flex items-start gap-3">
                        <div class="email-avatar" aria-hidden="true">
                            {{ strtoupper(substr($report->user->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                                <p class="text-sm font-semibold text-gray-900">
                                    {{ $isAuthor ? 'You' : $report->user->name }}
                                    @if ($isAuthor && auth()->user()->isAdmin())
                                        <span class="font-normal text-gray-500">(Admin)</span>
                                    @endif
                                </p>
                                <time class="shrink-0 text-xs text-gray-500" datetime="{{ $report->created_at->toIso8601String() }}">
                                    {{ $report->created_at->format('D, M j, Y g:i A') }}
                                </time>
                            </div>
                            <p class="email-meta">
                                <span class="email-meta-label">From:</span>
                                {{ $report->user->name }} &lt;{{ $report->user->email }}&gt;
                            </p>
                            @if ($toRecipients !== '')
                                <p class="email-meta">
                                    <span class="email-meta-label">To:</span>
                                    {{ $toRecipients }}
                                </p>
                            @endif
                            @if ($ccRecipients !== '')
                                <p class="email-meta">
                                    <span class="email-meta-label">Cc:</span>
                                    {{ $ccRecipients }}
                                </p>
                            @endif
                            <p class="email-meta">
                                <span class="email-meta-label">Subject:</span>
                                {{ $report->subject }}
                            </p>
                        </div>
                    </div>

                    <div class="email-message-body">
                        {!! nl2br(e($report->body)) !!}
                    </div>

                    @if ($report->attachments->isNotEmpty())
                        <div class="mt-4 flex flex-wrap gap-2 border-t border-gray-100 pt-4">
                            @foreach ($report->attachments as $attachment)
                                <a href="{{ route('attachments.download', $attachment) }}" class="attachment-chip hover:bg-gray-100">
                                    <span class="material-symbols-outlined text-[16px]">attach_file</span>
                                    {{ $attachment->original_filename }}
                                    <span class="opacity-70">({{ number_format($attachment->size / 1024 / 1024, 1) }} MB)</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </article>

                @foreach ($report->events->whereIn('type', ['sent'])->sortBy('created_at') as $event)
                    <div class="email-thread-activity">
                        <span class="material-symbols-outlined mr-1 align-middle text-[14px]">send</span>
                        Report emailed to recipients · {{ $event->created_at->diffForHumans() }}
                    </div>
                @endforeach

                {{-- Thread messages --}}
                @foreach ($report->threadMessages as $message)
                    @php
                        $isMine = in_array(strtolower($message->from_email), $myEmails, true)
                            || ($message->direction === \App\Enums\MessageDirection::Outbound && $message->mailbox === auth()->user()->shared_mailbox_email);
                        $senderName = $directory->name($message->from_email);
                        $messageTo = $directory->formattedList($message->to_emails);
                        $messageCc = $directory->formattedList($message->cc_emails);
                    @endphp
                    <article @class(['email-message', 'email-message-mine' => $isMine])>
                        <div class="flex items-start gap-3">
                            <div class="email-avatar" aria-hidden="true">
                                {{ $directory->initial($message->from_email) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ $isMine ? 'You' : $senderName }}
                                    </p>
                                    <time class="shrink-0 text-xs text-gray-500" datetime="{{ $message->created_at->toIso8601String() }}">
                                        {{ $message->created_at->format('D, M j, Y g:i A') }}
                                    </time>
                                </div>
                                <p class="email-meta">
                                    <span class="email-meta-label">From:</span>
                                    {{ $directory->formatted($message->from_email) }}
                                </p>
                                @if ($messageTo !== '')
                                    <p class="email-meta">
                                        <span class="email-meta-label">To:</span>
                                        {{ $messageTo }}
                                    </p>
                                @endif
                                @if ($messageCc !== '')
                                    <p class="email-meta">
                                        <span class="email-meta-label">Cc:</span>
                                        {{ $messageCc }}
                                    </p>
                                @endif
                                @if ($message->subject)
                                    <p class="email-meta">
                                        <span class="email-meta-label">Subject:</span>
                                        {{ $message->subject }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        <div class="email-message-body">
                            {!! nl2br(e($message->displayBody())) !!}
                        </div>

                        @if ($message->attachments->isNotEmpty())
                            <div class="mt-4 flex flex-wrap gap-2 border-t border-gray-100 pt-4">
                                @foreach ($message->attachments as $attachment)
                                    <a href="{{ route('attachments.download', $attachment) }}" class="attachment-chip hover:bg-gray-100">
                                        <span class="material-symbols-outlined text-[16px]">attach_file</span>
                                        {{ $attachment->original_filename }}
                                        <span class="opacity-70">({{ number_format($attachment->size / 1024 / 1024, 1) }} MB)</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </article>
                @endforeach

                @foreach ($report->events->whereIn('type', ['approved', 'rejected', 'replied'])->sortBy('created_at') as $event)
                    <div class="email-thread-activity">
                        <span class="material-symbols-outlined mr-1 align-middle text-[14px]">
                            {{ $event->type === 'approved' ? 'check_circle' : ($event->type === 'rejected' ? 'cancel' : 'reply') }}
                        </span>
                        @switch($event->type)
                            @case('approved')
                                Report status updated to <strong>Approved</strong>
                                @break
                            @case('rejected')
                                Report marked as <strong>Revision Needed</strong>
                                @break
                            @case('replied')
                                New reply added to the conversation
                                @break
                        @endswitch
                        · {{ $event->created_at->diffForHumans() }}
                    </div>
                @endforeach
            </div>
        </div>

        @can('reply', $report)
        <div class="email-composer mt-auto">
            <div class="email-composer-header">
                <div class="flex items-center gap-2 text-sm font-medium text-gray-800">
                    <span class="material-symbols-outlined text-[18px] text-gray-500">reply</span>
                    Reply
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    <span class="font-medium text-gray-600">Subject:</span> Re: {{ $report->subject }}
                </p>
            </div>
            <form method="POST" action="{{ route('reports.reply', $report) }}">
                @csrf
                <div class="p-4 sm:p-6">
                    <textarea
                        name="body"
                        class="form-textarea min-h-[120px] w-full resize-y border border-gray-200 bg-white"
                        placeholder="Type your reply..."
                        required
                        aria-label="Reply to conversation"
                    >{{ old('body') }}</textarea>
                    @error('body')<p class="form-error mt-2">{{ $message }}</p>@enderror

                    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <span class="inline-flex items-center gap-1 text-xs text-gray-500">
                            <span class="material-symbols-outlined text-[16px]">group</span>
                            All thread participants will receive this reply
                        </span>
                        <button type="submit" class="btn-primary gap-2">
                            <span class="material-symbols-outlined text-[18px]">send</span>
                            Send Reply
                        </button>
                    </div>
                    <p class="mt-3 text-xs text-gray-400">
                        @if ($graphConfigured && auth()->user()->shared_mailbox_email)
                            Replies are sent via Microsoft Graph to the email thread.
                        @elseif ($graphConfigured)
                            Replies are saved in-app. Assign a shared mailbox to your profile to send email replies.
                        @else
                            Replies are saved in-app. Configure Graph in App Settings to enable email delivery.
                        @endif
                    </p>
                </div>
            </form>
        </div>
        @else
        <div class="email-composer mt-auto p-4 text-center text-sm text-gray-500">
            You need access to this report to reply.
        </div>
        @endcan
    </div>
@endsection
