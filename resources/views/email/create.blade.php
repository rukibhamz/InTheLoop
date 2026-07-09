@extends('layouts.app')

@section('title', 'New Email')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <h1 class="page-title">New Email</h1>
            <p class="mt-1 text-sm text-on-surface-variant">Compose and send an internal email for review and approval.</p>
        </div>

        <form method="POST" action="{{ route('emails.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="form-card space-y-5">
                <x-directory-picker mode="single" name-prefix="to" label="To" :required="true" placeholder="Search directory contacts..." />

                <x-directory-picker mode="multiple" name-prefix="cc" label="CC" placeholder="Add CC..." />

                <div>
                    <label for="subject" class="form-label">
                        Subject <span class="text-danger-500" aria-hidden="true">*</span>
                    </label>
                    <input id="subject" name="subject" type="text" class="form-input" value="{{ old('subject') }}" placeholder="Enter a descriptive title" required>
                    @error('subject')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="body" class="form-label">
                        Description <span class="text-danger-500" aria-hidden="true">*</span>
                    </label>
                    <textarea id="body" name="body" class="form-textarea min-h-[160px]" placeholder="Write your message..." required>{{ old('body') }}</textarea>
                    @error('body')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="attachments" class="form-label">Attachments</label>
                    <div class="upload-zone">
                        <input id="attachments" name="attachments[]" type="file" class="sr-only" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                        <label for="attachments" class="cursor-pointer">
                            <span class="material-symbols-outlined mb-3 text-[40px] text-primary-500">cloud_upload</span>
                            <p class="text-sm font-semibold text-on-surface">Click or drag files to upload</p>
                            <p class="mt-1 text-xs text-on-surface-variant">PDF, XLSX, or DOCX (Max 25MB)</p>
                        </label>
                    </div>
                    @error('attachments')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                    @error('attachments.*')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-6 sm:flex-row sm:justify-end">
                    <a href="{{ route('emails.index') }}" class="btn-ghost justify-center sm:justify-start">Cancel</a>
                    <button type="submit" class="btn-primary gap-2">
                        <span class="material-symbols-outlined text-[18px]">send</span>
                        Send Email
                    </button>
                </div>
            </div>
        </form>

        <div class="info-banner mt-6">
            <span class="material-symbols-outlined shrink-0 text-primary-500">info</span>
            <p>Emails are automatically logged and shared with the selected recipients. You can track replies and approval progress in your <strong>Email</strong> inbox.</p>
        </div>
    </div>
@endsection
