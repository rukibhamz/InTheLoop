@props(['contact'])

<div class="flex min-w-0 flex-1 items-center gap-2">
    <span class="truncate text-sm font-medium text-gray-800">{{ $contact['label'] ?? $contact['email'] }}</span>
    @if (! empty($contact['job_title']))
        <span class="truncate text-xs text-gray-500">{{ $contact['job_title'] }}</span>
    @endif
</div>
