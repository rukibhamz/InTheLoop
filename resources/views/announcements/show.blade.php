@extends('layouts.app')

@section('title', $announcement->subject ?: 'Announcement')

@section('content')
    <div class="mx-auto max-w-5xl">
        <div class="mb-6 border-b border-gray-200 pb-6">
            <a href="{{ route('announcements.index') }}" class="mb-3 inline-flex items-center gap-1 text-sm font-medium text-primary-600 hover:underline">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                Back to announcements
            </a>

            <h1 class="break-words text-xl font-bold text-on-surface sm:text-2xl">
                {{ $thread->first()->subject ?: '(No subject)' }}
            </h1>

            <p class="mt-2 text-sm text-on-surface-variant">
                <span class="material-symbols-outlined mr-1 align-middle text-[16px]">mail</span>
                {{ $announcement->mailbox }}
            </p>
        </div>

        <div class="email-thread">
            @foreach ($thread as $message)
                @php
                    $senderName = $directory->name($message->from_email);
                    if (filled($message->from_name)) {
                        $senderName = $message->from_name;
                    }
                    $messageTo = $directory->formattedList($message->to_emails);
                    $messageCc = $directory->formattedList($message->cc_emails);
                @endphp
                <article class="email-message">
                    <div class="flex items-start gap-3">
                        <div class="email-avatar" aria-hidden="true">
                            {{ $directory->initial($message->from_email) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                                <p class="text-sm font-semibold text-gray-900">{{ $senderName }}</p>
                                <time class="shrink-0 text-xs text-gray-500" datetime="{{ ($message->received_at ?? $message->created_at)->toIso8601String() }}">
                                    {{ ($message->received_at ?? $message->created_at)->format('D, M j, Y g:i A') }}
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
        </div>
    </div>
@endsection
