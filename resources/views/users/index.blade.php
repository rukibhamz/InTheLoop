@extends('layouts.app')

@section('title', 'Manage Users')

@section('content')
    <div class="mx-auto max-w-7xl">
        <div class="page-header">
            <div class="min-w-0">
                <h1 class="page-title">User Management</h1>
                <p class="mt-1 text-sm text-on-surface-variant">Manage staff accounts, roles, and access.</p>
            </div>
            <a href="{{ route('users.create') }}" class="btn-primary w-full gap-2 sm:w-auto">
                <span class="material-symbols-outlined text-[18px]">person_add</span>
                Add User
            </a>
        </div>

        <div class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Total Users', 'value' => $stats['total'], 'icon' => 'group'],
                ['label' => 'Admins', 'value' => $stats['admins'], 'icon' => 'admin_panel_settings'],
                ['label' => 'Approvers', 'value' => $stats['approvers'], 'icon' => 'verified_user'],
                ['label' => 'Inactive', 'value' => $stats['inactive'], 'icon' => 'person_off'],
            ] as $stat)
                <div class="stat-card flex items-center gap-4">
                    <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
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
            <form method="GET" class="flex flex-col gap-3 border-b border-gray-100 p-3 sm:flex-row sm:items-center sm:p-4">
                <div class="relative min-w-0 flex-1">
                    <span class="material-symbols-outlined pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-[20px] text-gray-400">search</span>
                    <input type="search" name="q" value="{{ request('q') }}" class="form-input-icon" placeholder="Search by name, email, or job role...">
                </div>
                <select name="role" class="form-select w-full sm:max-w-[180px]" onchange="this.form.submit()">
                    <option value="">All Roles</option>
                    <option value="admin" @selected(request('role') === 'admin')>Admins</option>
                    <option value="approver" @selected(request('role') === 'approver')>Approvers</option>
                    <option value="inactive" @selected(request('role') === 'inactive')>Inactive</option>
                </select>
                <button type="submit" class="btn-secondary w-full py-2.5 sm:w-auto">Search</button>
            </form>

            <div class="table-scroll">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gray-100 bg-gray-50/80 text-xs font-semibold tracking-wide text-on-surface-variant uppercase">
                        <tr>
                            <th class="px-5 py-3">User</th>
                            <th class="px-5 py-3">Job Role</th>
                            <th class="px-5 py-3">Roles</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($users as $user)
                            <tr class="hover:bg-gray-50/50">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="user-avatar h-9 w-9 text-xs">{{ strtoupper(substr($user->name, 0, 2)) }}</div>
                                        <div>
                                            <p class="font-semibold text-on-surface">{{ $user->name }}</p>
                                            <p class="text-xs text-on-surface-variant">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-on-surface-variant">{{ $jobTitles[$user->email] ?? '—' }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        @if ($user->is_admin)<span class="category-pill">Admin</span>@endif
                                        @if ($user->is_approver)<span class="category-pill">Approver</span>@endif
                                        @if (! $user->is_admin && ! $user->is_approver)<span class="text-gray-400">Staff</span>@endif
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium {{ $user->is_active ? 'text-success-600' : 'text-danger-600' }}">
                                        <span class="h-2 w-2 rounded-full {{ $user->is_active ? 'bg-success-500' : 'bg-danger-500' }}"></span>
                                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('users.edit', $user) }}" class="text-sm font-semibold text-primary-600 hover:underline">Edit</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-12 text-center text-on-surface-variant">No users found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="pagination-wrap border-t border-gray-100 px-3 py-4 sm:px-5">{{ $users->links() }}</div>
            @endif
        </section>
    </div>
@endsection
