@extends('layouts.app')

@section('title', 'Report Categories')

@section('content')
    @php
        $categoryIcons = [
            'IT' => 'computer',
            'Operations' => 'precision_manufacturing',
            'Finance' => 'payments',
            'HR' => 'groups',
            'Human Resources' => 'groups',
        ];
        $iconFor = fn (string $name) => $categoryIcons[$name] ?? 'folder';
    @endphp

    <div class="mx-auto max-w-7xl">
        <div class="page-header">
            <div class="min-w-0">
                <nav class="mb-2 text-sm text-on-surface-variant">
                    <span>Admin</span>
                    <span class="mx-1">›</span>
                    <span>Categories</span>
                </nav>
                <h1 class="page-title">Report Categories</h1>
                <p class="mt-1 text-sm text-on-surface-variant">Manage organizational units and disclosure classifications.</p>
            </div>
            <a href="{{ route('categories.create') }}" class="btn-primary w-full gap-2 sm:w-auto">
                <span class="material-symbols-outlined text-[18px]">add</span>
                New Category
            </a>
        </div>

        <div class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="stat-card">
                <p class="text-xs font-semibold tracking-wide text-on-surface-variant uppercase">Total Categories</p>
                <p class="mt-1 text-3xl font-bold text-primary-600">{{ $stats['total_categories'] }}</p>
            </div>
            <div class="stat-card">
                <p class="text-xs font-semibold tracking-wide text-on-surface-variant uppercase">Active Reports</p>
                <p class="mt-1 text-3xl font-bold text-primary-600">{{ $stats['active_reports'] }}</p>
            </div>
            <div class="stat-card">
                <p class="text-xs font-semibold tracking-wide text-on-surface-variant uppercase">Avg. Review Time</p>
                <p class="mt-1 text-3xl font-bold text-primary-600">{{ $stats['avg_review_days'] }}d</p>
            </div>
            <div class="stat-card">
                <p class="text-xs font-semibold tracking-wide text-on-surface-variant uppercase">Compliance Score</p>
                <p class="mt-1 text-3xl font-bold text-success-600">{{ $stats['compliance_score'] }}%</p>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($categories as $category)
                @php
                    $isReview = $category->reports_count > 0 && $category->reports_count < 3;
                @endphp
                <article class="report-grid-card">
                    <div class="mb-4 flex items-start justify-between">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                            <span class="material-symbols-outlined">{{ $iconFor($category->name) }}</span>
                        </div>
                        @if ($isReview)
                            <span class="status-badge status-pending"><span class="status-dot"></span>Review</span>
                        @else
                            <span class="status-badge status-approved"><span class="status-dot"></span>Active</span>
                        @endif
                    </div>

                    <h3 class="text-lg font-semibold text-on-surface">{{ $category->name }}</h3>
                    <p class="mt-2 line-clamp-3 flex-1 text-sm text-on-surface-variant">
                        {{ $category->description ?: 'No description provided for this category.' }}
                    </p>

                    <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-4">
                        <span class="inline-flex items-center gap-1 text-sm text-on-surface-variant">
                            <span class="material-symbols-outlined text-[16px]">description</span>
                            {{ $category->reports_count }} {{ Str::plural('Report', $category->reports_count) }}
                        </span>
                        <a href="{{ route('categories.edit', $category) }}" class="text-sm font-semibold text-primary-600 hover:underline">Manage →</a>
                    </div>
                </article>
            @endforeach

            <a href="{{ route('categories.create') }}" class="flex min-h-[220px] flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-gray-50/50 p-6 text-center transition hover:border-primary-500 hover:bg-primary-50/30">
                <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-200 text-gray-500">
                    <span class="material-symbols-outlined text-[28px]">add</span>
                </div>
                <h3 class="font-semibold text-on-surface">Create Custom Category</h3>
                <p class="mt-1 max-w-xs text-sm text-on-surface-variant">Define new reporting workflows for specialized departments.</p>
            </a>
        </div>
    </div>
@endsection
