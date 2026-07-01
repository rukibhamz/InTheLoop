@extends('layouts.app')

@section('title', 'Account Settings')

@section('content')
    @php
        $prefs = $user->notificationPreferences();
        $initials = strtoupper(substr($user->name, 0, 2));
    @endphp

    <div class="mx-auto max-w-6xl">
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <h1 class="page-title">Account Settings</h1>
                <p class="mt-1 text-sm text-on-surface-variant">Manage your profile, security, and notification preferences.</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('users.show', $user) }}" class="btn-secondary py-2">View Public Profile</a>
                <button type="submit" form="account-settings-form" class="btn-primary">Save Changes</button>
            </div>
        </div>

        <form id="account-settings-form" method="POST" action="{{ route('settings.account.update') }}">
            @csrf
            @method('PUT')

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="space-y-6 lg:col-span-2">
                    <section class="form-card">
                        <h2 class="mb-5 flex items-center gap-2 text-lg font-semibold text-on-surface">
                            <span class="material-symbols-outlined text-primary-500">person</span>
                            Personal Information
                        </h2>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2 sm:grid sm:grid-cols-2 sm:gap-4">
                                <div>
                                    <label for="name" class="form-label">Full Name</label>
                                    <input id="name" name="name" type="text" class="form-input" value="{{ old('name', $user->name) }}" required>
                                    @error('name')<p class="form-error">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="email" class="form-label">Email Address</label>
                                    <input id="email" name="email" type="email" class="form-input" value="{{ old('email', $user->email) }}" required>
                                    @error('email')<p class="form-error">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div>
                                <label for="department" class="form-label">Department</label>
                                <input id="department" name="department" type="text" class="form-input" value="{{ old('department', $user->department) }}" placeholder="e.g. Communications & Strategy">
                            </div>

                            <div>
                                <label for="employee_id" class="form-label">Employee ID</label>
                                <input id="employee_id" type="text" class="form-input bg-primary-50/50" value="{{ $user->employee_id ?: '—' }}" disabled>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="bio" class="form-label">Short Bio</label>
                                <textarea id="bio" name="bio" class="form-textarea min-h-[100px]" placeholder="Tell colleagues a little about your role...">{{ old('bio', $user->bio) }}</textarea>
                            </div>
                        </div>
                    </section>

                    <section class="form-card">
                        <h2 class="mb-5 flex items-center gap-2 text-lg font-semibold text-on-surface">
                            <span class="material-symbols-outlined text-primary-500">notifications</span>
                            Notification Preferences
                        </h2>

                        <div class="table-scroll">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-100 text-left text-xs font-semibold tracking-wide text-on-surface-variant uppercase">
                                        <th class="pb-3 pr-4">Alert</th>
                                        <th class="pb-3 px-4 text-center">Email</th>
                                        <th class="pb-3 pl-4 text-center">App</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ([
                                        'new_report_assigned' => ['group' => 'Report Alerts', 'label' => 'New Report Assigned'],
                                        'comment_replies' => ['group' => null, 'label' => 'Comment Replies'],
                                        'approval_required' => ['group' => 'Approval Workflow', 'label' => 'Approval Required'],
                                        'status_changes' => ['group' => null, 'label' => 'Status Changes'],
                                        'security_alerts' => ['group' => 'System & Security', 'label' => 'Security Alerts'],
                                        'weekly_digest' => ['group' => null, 'label' => 'Weekly Digest'],
                                    ] as $key => $meta)
                                        @if ($meta['group'])
                                            <tr><td colspan="3" class="pt-4 pb-2 text-xs font-semibold text-primary-600 uppercase">{{ $meta['group'] }}</td></tr>
                                        @endif
                                        <tr>
                                            <td class="py-3 pr-4 font-medium text-on-surface">{{ $meta['label'] }}</td>
                                            @foreach (['email', 'app'] as $channel)
                                                <td class="px-4 py-3 text-center">
                                                    <input type="hidden" name="notification_preferences[{{ $key }}][{{ $channel }}]" value="0">
                                                    <input
                                                        type="checkbox"
                                                        name="notification_preferences[{{ $key }}][{{ $channel }}]"
                                                        value="1"
                                                        class="h-4 w-4 rounded border-gray-300 text-primary-500 focus:ring-primary-500"
                                                        @checked(old("notification_preferences.{$key}.{$channel}", $prefs[$key][$channel] ?? false))
                                                    >
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <div class="space-y-6">
                    <section class="form-card text-center">
                        <div class="relative mx-auto mb-4 h-28 w-28">
                            <div class="user-avatar mx-auto h-28 w-28 text-2xl">{{ $initials }}</div>
                        </div>
                        <h3 class="text-lg font-bold text-on-surface">{{ $user->name }}</h3>
                        <p class="text-sm text-on-surface-variant">Member since {{ $user->created_at->format('M Y') }}</p>
                        <span class="mt-3 inline-flex items-center gap-1 rounded-full bg-success-50 px-3 py-1 text-xs font-semibold text-success-600">
                            <span class="status-dot"></span>
                            Active Account
                        </span>
                    </section>

                    <section class="form-card">
                        <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-on-surface">
                            <span class="material-symbols-outlined text-primary-500">shield</span>
                            Security
                        </h2>
                        <p class="mb-4 text-sm text-on-surface-variant">Password last changed {{ $user->updated_at->diffForHumans() }}.</p>

                        <form method="POST" action="{{ route('settings.account.password') }}" class="mb-4 space-y-3">
                            @csrf
                            @method('PUT')
                            <div>
                                <label for="current_password" class="form-label">Current Password</label>
                                <input id="current_password" name="current_password" type="password" class="form-input" required>
                                @error('current_password')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="password" class="form-label">New Password</label>
                                <input id="password" name="password" type="password" class="form-input" required>
                                @error('password')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="password_confirmation" class="form-label">Confirm New Password</label>
                                <input id="password_confirmation" name="password_confirmation" type="password" class="form-input" required>
                            </div>
                            <button type="submit" class="btn-secondary w-full gap-2">
                                <span class="material-symbols-outlined text-[18px]">lock_reset</span>
                                Change Password
                            </button>
                        </form>

                        <label class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
                            <span class="text-sm font-medium text-on-surface">2FA Enabled</span>
                            <input type="hidden" name="two_factor_enabled" value="0" form="account-settings-form">
                            <input type="checkbox" name="two_factor_enabled" value="1" form="account-settings-form" class="h-5 w-10 rounded-full" @checked(old('two_factor_enabled', $user->two_factor_enabled))>
                        </label>
                        <p class="mt-2 text-xs text-on-surface-variant">Saves preference; full 2FA setup ships with SSO phase.</p>
                    </section>

                    @if (auth()->user()->isAdmin())
                        <section class="form-card">
                            <h3 class="mb-2 text-sm font-semibold text-on-surface">Administrator</h3>
                            <p class="mb-3 text-sm text-on-surface-variant">Manage organization branding, integrations, and access.</p>
                            <div class="space-y-2">
                                <a href="{{ route('settings.app') }}" class="btn-ghost w-full justify-center">App Settings →</a>
                                <a href="{{ route('settings.microsoft') }}" class="btn-ghost w-full justify-center">Microsoft Integration →</a>
                            </div>
                        </section>
                    @endif
                </div>
            </div>
        </form>

        <section class="mt-6 rounded-xl border border-danger-500/30 bg-danger-50/40 p-6">
            <h2 class="mb-2 flex items-center gap-2 text-lg font-semibold text-danger-600">
                <span class="material-symbols-outlined">warning</span>
                Danger Zone
            </h2>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-semibold text-danger-600">Deactivate Account</p>
                    <p class="mt-1 text-sm text-danger-600/80">Permanently deactivate your access to InTheLoop. This action cannot be undone by yourself.</p>
                </div>
                <form method="POST" action="{{ route('settings.account.destroy') }}" onsubmit="return confirm('Deactivate your account? You will be signed out immediately.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-secondary border-danger-500 text-danger-600">Deactivate Account</button>
                </form>
            </div>
        </section>
    </div>
@endsection
