@props(['status'])

@php
    $value = $status instanceof \App\Enums\EmailStatus ? $status->value : $status;
    $label = match ($value) {
        'in_review' => 'Under Review',
        'rejected' => 'Revision Needed',
        default => $status instanceof \App\Enums\EmailStatus ? $status->label() : ucfirst(str_replace('_', ' ', $value)),
    };
@endphp

<span class="status-badge status-{{ $value }}" role="status">
    <span class="status-dot" aria-hidden="true"></span>
    <span class="sr-only">Status:</span>
    {{ $label }}
</span>
