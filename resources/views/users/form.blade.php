@extends('layouts.app')

@section('title', $user->exists ? 'Edit User' : 'Add User')

@section('content')
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('users.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-medium text-primary-600 hover:underline">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            Back to users
        </a>

        <h1 class="page-title mb-6">{{ $user->exists ? 'Edit User' : 'Add User' }}</h1>

        <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="form-card space-y-5">
            @csrf
            @if ($user->exists) @method('PUT') @endif

            <div>
                <label for="name" class="form-label">Full Name</label>
                <input id="name" name="name" type="text" class="form-input" value="{{ old('name', $user->name) }}" required>
                @error('name')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="email" class="form-label">Email</label>
                <input id="email" name="email" type="email" class="form-input" value="{{ old('email', $user->email) }}" required>
                @error('email')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="employee_id" class="form-label">Employee ID</label>
                    <input id="employee_id" name="employee_id" type="text" class="form-input" value="{{ old('employee_id', $user->employee_id) }}">
                </div>
                <div>
                    <label for="department" class="form-label">Department</label>
                    <input id="department" name="department" type="text" class="form-input" value="{{ old('department', $user->department) }}">
                </div>
            </div>

            <div>
                <label for="shared_mailbox_email" class="form-label">Shared Mailbox Email</label>
                <input id="shared_mailbox_email" name="shared_mailbox_email" type="email" class="form-input" value="{{ old('shared_mailbox_email', $user->shared_mailbox_email) }}" placeholder="{{ $user->email ?: 'Defaults to login email' }}">
                <p class="mt-1 text-xs text-on-surface-variant">Leave blank to use the login email. Azure directory sync and Microsoft sign-in can update this automatically.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="password" class="form-label">Password @if ($user->exists)<span class="font-normal text-gray-500">(leave blank to keep)</span>@endif</label>
                    <input id="password" name="password" type="password" class="form-input" {{ $user->exists ? '' : 'required' }}>
                    @error('password')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="form-input">
                </div>
            </div>

            <div class="space-y-3 rounded-lg border border-gray-100 bg-gray-50 p-4">
                <label class="flex items-center gap-2">
                    <input type="hidden" name="is_admin" value="0">
                    <input type="checkbox" name="is_admin" value="1" class="rounded border-gray-300 text-primary-500" @checked(old('is_admin', $user->is_admin))>
                    <span class="text-sm font-medium">Administrator</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="hidden" name="is_approver" value="0">
                    <input type="checkbox" name="is_approver" value="1" class="rounded border-gray-300 text-primary-500" @checked(old('is_approver', $user->is_approver))>
                    <span class="text-sm font-medium">Approver</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-primary-500" @checked(old('is_active', $user->exists ? $user->is_active : true))>
                    <span class="text-sm font-medium">Active account</span>
                </label>
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-100 pt-6">
                <a href="{{ route('users.index') }}" class="btn-ghost">Cancel</a>
                <button type="submit" class="btn-primary">{{ $user->exists ? 'Save Changes' : 'Create User' }}</button>
            </div>
        </form>

        @if ($user->exists && $user->id !== auth()->id())
            <form method="POST" action="{{ route('users.destroy', $user) }}" class="mt-6" onsubmit="return confirm('Delete this user permanently?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-secondary border-danger-500 text-danger-600">Delete User</button>
            </form>
        @endif
    </div>
@endsection
