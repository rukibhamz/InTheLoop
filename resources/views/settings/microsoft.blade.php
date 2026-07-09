@extends('layouts.app')

@section('title', 'Microsoft Integration')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-8">
            <nav class="mb-2 text-sm text-on-surface-variant">
                <a href="{{ route('settings.account') }}" class="hover:text-primary-600">Settings</a>
                <span class="mx-1">›</span>
                <span>Microsoft</span>
            </nav>
            <h1 class="page-title">Microsoft Integration</h1>
            <p class="mt-1 text-sm text-on-surface-variant">Configure Microsoft Graph for mail and directory sync, plus Entra ID sign-in.</p>
        </div>

        <form method="POST" action="{{ route('settings.microsoft.update') }}" class="form-card space-y-8">
            @csrf
            @method('PUT')

            <section class="space-y-5">
                <div>
                    <h2 class="flex items-center gap-2 text-lg font-semibold text-on-surface">
                        <span class="material-symbols-outlined text-primary-500">mail</span>
                        Microsoft Graph
                    </h2>
                    <p class="mt-1 text-sm text-on-surface-variant">App-only permissions for outbound email, inbound sync, and directory typeahead.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="graph_tenant_id" class="form-label">Tenant ID</label>
                        <input id="graph_tenant_id" name="graph_tenant_id" type="text" class="form-input" value="{{ old('graph_tenant_id', $settings->graph_tenant_id) }}">
                        @error('graph_tenant_id')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="graph_client_id" class="form-label">Client ID</label>
                        <input id="graph_client_id" name="graph_client_id" type="text" class="form-input" value="{{ old('graph_client_id', $settings->graph_client_id) }}">
                        @error('graph_client_id')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="graph_client_secret" class="form-label">Client Secret</label>
                        <input id="graph_client_secret" name="graph_client_secret" type="password" class="form-input" placeholder="{{ $settings->graph_client_secret ? '•••••••• (leave blank to keep)' : '' }}">
                        @error('graph_client_secret')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="graph_default_sender_mailbox" class="form-label">Default Sender Mailbox</label>
                        <input id="graph_default_sender_mailbox" name="graph_default_sender_mailbox" type="email" class="form-input" value="{{ old('graph_default_sender_mailbox', $settings->graph_default_sender_mailbox) }}">
                        <p class="mt-1 text-xs text-on-surface-variant">Each sender needs an Exchange shared mailbox. Ask your admin to run: <code class="text-xs">Set-Mailbox &lt;email&gt; -MessageCopyForSentAsEnabled $true</code></p>
                        @error('graph_default_sender_mailbox')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="graph_monitored_mailboxes" class="form-label">Additional Monitored Mailboxes</label>
                        <input id="graph_monitored_mailboxes" name="graph_monitored_mailboxes" type="text" class="form-input" value="{{ old('graph_monitored_mailboxes', $settings->graph_monitored_mailboxes) }}" placeholder="comma-separated emails">
                        <p class="mt-1 text-xs text-on-surface-variant">Shared mailboxes polled for email thread replies. User and recipient mailboxes are included automatically.</p>
                        @error('graph_monitored_mailboxes')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="graph_announcement_mailboxes" class="form-label">Announcement / Group Mailboxes</label>
                        <input id="graph_announcement_mailboxes" name="graph_announcement_mailboxes" type="text" class="form-input" value="{{ old('graph_announcement_mailboxes', $settings->graph_announcement_mailboxes) }}" placeholder="e.g. allstaff@company.com, hr-announcements@company.com">
                        <p class="mt-1 text-xs text-on-surface-variant">Mail-enabled groups or distribution lists. Unmatched inbox mail is captured as Announcements instead of email threads.</p>
                        @error('graph_announcement_mailboxes')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-on-surface-variant">
                    <p class="mb-2 font-semibold text-on-surface">Required application permissions</p>
                    <ul class="list-inside list-disc space-y-1">
                        <li><code class="text-xs">Mail.Send</code>, <code class="text-xs">Mail.Read</code>, <code class="text-xs">User.Read.All</code></li>
                    </ul>
                    <p class="mt-2">Apply an Application Access Policy scoped to your shared mailboxes in Exchange Online.</p>
                </div>
            </section>

            <hr class="border-gray-100">

            <section class="space-y-5">
                <div>
                    <h2 class="flex items-center gap-2 text-lg font-semibold text-on-surface">
                        <span class="material-symbols-outlined text-primary-500">key</span>
                        Microsoft SSO (Login)
                    </h2>
                    <p class="mt-1 text-sm text-on-surface-variant">Delegated OAuth for staff sign-in with Entra ID accounts.</p>
                </div>

                <div class="info-banner flex-col items-start">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined shrink-0 text-primary-500">link</span>
                        <div>
                            <p class="font-medium text-on-surface">Redirect URI</p>
                            <p class="mt-1 break-all font-mono text-sm">{{ $redirectUri }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="microsoft_tenant_id" class="form-label">Tenant ID</label>
                        <input id="microsoft_tenant_id" name="microsoft_tenant_id" type="text" class="form-input" value="{{ old('microsoft_tenant_id', $settings->microsoft_tenant_id) }}">
                        @error('microsoft_tenant_id')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="microsoft_client_id" class="form-label">Client ID</label>
                        <input id="microsoft_client_id" name="microsoft_client_id" type="text" class="form-input" value="{{ old('microsoft_client_id', $settings->microsoft_client_id) }}">
                        @error('microsoft_client_id')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="microsoft_client_secret" class="form-label">Client Secret</label>
                        <input id="microsoft_client_secret" name="microsoft_client_secret" type="password" class="form-input" placeholder="{{ $settings->microsoft_client_secret ? '•••••••• (leave blank to keep)' : '' }}">
                        @error('microsoft_client_secret')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <label class="flex items-center gap-3 rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
                    <input type="hidden" name="sso_enabled" value="0">
                    <input type="checkbox" name="sso_enabled" value="1" class="h-4 w-4 rounded border-gray-300 text-primary-500" @checked(old('sso_enabled', $settings->sso_enabled))>
                    <span class="text-sm font-medium text-on-surface">Enable Microsoft sign-in button on login page</span>
                </label>

                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-on-surface-variant">
                    <p class="mb-2 font-semibold text-on-surface">Required delegated permissions</p>
                    <ul class="list-inside list-disc space-y-1">
                        <li><code class="text-xs">openid</code>, <code class="text-xs">profile</code>, <code class="text-xs">email</code>, <code class="text-xs">User.Read</code></li>
                    </ul>
                    <p class="mt-2">Graph mail and SSO can use the same Entra app registration or separate apps — credentials are stored independently above.</p>
                </div>
            </section>

            <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-6 sm:flex-row sm:justify-end">
                <a href="{{ route('settings.account') }}" class="btn-ghost justify-center">Cancel</a>
                <button type="submit" class="btn-primary">Save Microsoft Settings</button>
            </div>
        </form>
    </div>
@endsection
