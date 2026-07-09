@extends('layouts.app')

@section('title', 'Manage Recipients')

@section('content')
    <div class="mx-auto max-w-7xl">
        <div class="page-header">
            <div class="min-w-0">
                <h1 class="page-title">Manage Recipients</h1>
                <p class="mt-1 text-sm text-on-surface-variant">View and coordinate internal communication pathways.</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('recipients.export') }}" class="btn-secondary gap-2 py-2">
                    <span class="material-symbols-outlined text-[18px]">download</span>
                    Export List
                </a>
                <a href="{{ route('recipients.create') }}" class="btn-secondary gap-2 border-dashed border-primary-500 py-2 text-primary-600">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    Add Recipient
                </a>
            </div>
        </div>

        <div class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Total Recipients', 'value' => $stats['total'], 'icon' => 'group', 'color' => 'text-primary-500 bg-primary-50'],
                ['label' => 'Admins', 'value' => $stats['admins'], 'icon' => 'admin_panel_settings', 'color' => 'text-info-500 bg-info-50'],
                ['label' => 'Active Threads', 'value' => $stats['active_threads'], 'icon' => 'forum', 'color' => 'text-success-600 bg-success-50'],
                ['label' => 'Pending Invites', 'value' => $stats['pending_invites'], 'icon' => 'mail', 'color' => 'text-warning-600 bg-warning-50'],
            ] as $stat)
                <div class="stat-card flex items-center gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg {{ $stat['color'] }}">
                        <span class="material-symbols-outlined">{{ $stat['icon'] }}</span>
                    </div>
                    <div>
                        <p class="text-xs font-semibold tracking-wide text-on-surface-variant uppercase">{{ $stat['label'] }}</p>
                        <p class="text-2xl font-bold text-on-surface">{{ number_format($stat['value']) }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <section class="form-card overflow-hidden p-0">
            <form method="GET" action="{{ route('recipients.index') }}" class="flex flex-col gap-3 border-b border-gray-100 p-3 sm:flex-row sm:items-center sm:p-4">
                <div class="relative min-w-0 flex-1">
                    <span class="material-symbols-outlined pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-[20px] text-gray-400">search</span>
                    <input type="search" name="q" value="{{ request('q') }}" class="form-input-icon" placeholder="Search by name, email, or job role...">
                </div>
                <select name="role" class="form-select w-full sm:max-w-[180px]" onchange="this.form.submit()">
                    <option value="">All Roles</option>
                    @foreach ($roles as $roleOption)
                        <option value="{{ $roleOption }}" @selected(request('role') === $roleOption)>{{ $roleOption }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn-secondary p-2.5" title="Apply filters">
                    <span class="material-symbols-outlined">tune</span>
                </button>
            </form>

            <div class="table-scroll">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gray-100 bg-gray-50/80 text-xs font-semibold tracking-wide text-on-surface-variant uppercase">
                        <tr>
                            <th class="px-5 py-3">Name</th>
                            <th class="px-5 py-3">Email</th>
                            <th class="px-5 py-3">Job Role</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($recipients as $recipient)
                            @php
                                $statuses = ['Online', 'Offline', 'Busy'];
                                $status = $statuses[$recipient->id % 3];
                                $statusColor = match ($status) {
                                    'Online' => 'bg-success-500',
                                    'Busy' => 'bg-warning-500',
                                    default => 'bg-gray-400',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50/50">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="user-avatar h-9 w-9 text-xs">{{ strtoupper(substr($recipient->name, 0, 2)) }}</div>
                                        <div>
                                            <p class="font-semibold text-on-surface">{{ $recipient->name }}</p>
                                            <p class="text-xs text-on-surface-variant">Active since {{ $recipient->created_at->format('M Y') }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-on-surface-variant">{{ $recipient->shared_mailbox_email }}</td>
                                <td class="px-5 py-4 text-on-surface-variant">{{ $recipient->role ?? '—' }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-on-surface-variant">
                                        <span class="h-2 w-2 rounded-full {{ $statusColor }}"></span>
                                        {{ $status }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('recipients.edit', $recipient) }}" class="text-sm font-semibold text-primary-600 hover:underline">Edit</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center text-on-surface-variant">
                                    No recipients configured yet. Add shared mailboxes used for email routing.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($recipients->hasPages())
                <div class="pagination-wrap border-t border-gray-100 px-3 py-4 sm:px-5">
                    {{ $recipients->links() }}
                </div>
            @elseif ($recipients->total() > 0)
                <p class="border-t border-gray-100 px-5 py-3 text-xs text-on-surface-variant">
                    Showing {{ $recipients->firstItem() }} to {{ $recipients->lastItem() }} of {{ $recipients->total() }} recipients
                </p>
            @endif
        </section>

        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="report-grid-card">
                <form method="POST" action="{{ route('recipients.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="flex items-start justify-between">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-info-50 text-info-500">
                            <span class="material-symbols-outlined">upload_file</span>
                        </div>
                    </div>
                    <h3 class="mt-4 font-semibold text-on-surface">Bulk Import</h3>
                    <p class="mt-1 mb-4 text-sm text-on-surface-variant">Upload a CSV file with columns: name, email, department, role.</p>
                    <input type="file" name="file" accept=".csv,.txt" class="form-input mb-3" required>
                    <button type="submit" class="btn-secondary w-full">Import CSV</button>
                </form>
            </div>
            <a href="{{ route('routing.index') }}" class="report-grid-card group">
                <div class="flex items-start justify-between">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                        <span class="material-symbols-outlined">account_tree</span>
                    </div>
                    <span class="material-symbols-outlined text-gray-400 transition group-hover:text-primary-500">arrow_forward</span>
                </div>
                <h3 class="mt-4 font-semibold text-on-surface">Mapping Rules</h3>
                <p class="mt-1 text-sm text-on-surface-variant">Configure automated routing rules to ensure emails reach the right department heads.</p>
            </a>
        </div>
    </div>
@endsection
