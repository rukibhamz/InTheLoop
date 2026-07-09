@php
    $logoUrl = $branding->logoUrl();
    $orgName = $branding->orgName();
@endphp

<div class="flex items-center gap-3">
    @if ($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ $orgName }}" class="h-9 w-auto max-w-[140px] object-contain">
    @else
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-500">
            <span class="material-symbols-outlined material-symbols-filled text-[20px] text-white">sync_alt</span>
        </div>
        <div class="min-w-0 text-left">
            <p class="truncate text-sm font-bold text-on-surface">{{ $orgName }}</p>
            <p class="truncate text-xs text-on-surface-variant">Internal Email</p>
        </div>
    @endif
</div>
