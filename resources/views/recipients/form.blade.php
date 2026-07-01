@extends('layouts.app')

@section('title', $recipient->exists ? 'Edit Recipient' : 'Add Recipient')

@section('content')
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('recipients.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-medium text-primary-600 hover:underline">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            Back to recipients
        </a>

        <h1 class="page-title mb-6">{{ $recipient->exists ? 'Edit Recipient' : 'Add Recipient' }}</h1>

        <form method="POST" action="{{ $recipient->exists ? route('recipients.update', $recipient) : route('recipients.store') }}" class="form-card space-y-5">
            @csrf
            @if ($recipient->exists) @method('PUT') @endif

            <div>
                <label for="name" class="form-label">Name</label>
                <input id="name" name="name" type="text" class="form-input" value="{{ old('name', $recipient->name) }}" required>
                @error('name')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="shared_mailbox_email" class="form-label">Shared Mailbox Email</label>
                <input id="shared_mailbox_email" name="shared_mailbox_email" type="email" class="form-input" value="{{ old('shared_mailbox_email', $recipient->shared_mailbox_email) }}" required>
                @error('shared_mailbox_email')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="department" class="form-label">Department</label>
                    <input id="department" name="department" type="text" class="form-input" value="{{ old('department', $recipient->department) }}">
                </div>
                <div>
                    <label for="role" class="form-label">Role</label>
                    <input id="role" name="role" type="text" class="form-input" value="{{ old('role', $recipient->role) }}">
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-100 pt-6">
                <a href="{{ route('recipients.index') }}" class="btn-ghost">Cancel</a>
                <button type="submit" class="btn-primary">Save Recipient</button>
            </div>
        </form>
    </div>
@endsection
