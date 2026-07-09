@extends('layouts.app')

@section('title', 'Mapping Rules')

@section('content')
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('recipients.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-medium text-primary-600 hover:underline">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            Back to recipients
        </a>

        <h1 class="page-title">Mapping Rules</h1>
        <p class="mt-1 mb-8 text-sm text-on-surface-variant">Assign a default recipient for each category so new emails route automatically.</p>

        <form method="POST" action="{{ route('routing.update') }}" class="form-card space-y-4">
            @csrf
            @method('PUT')

            @foreach ($categories as $category)
                <div class="grid items-center gap-4 border-b border-gray-100 py-4 last:border-0 sm:grid-cols-2">
                    <div>
                        <p class="font-semibold text-on-surface">{{ $category->name }}</p>
                        <p class="text-sm text-on-surface-variant">{{ Str::limit($category->description, 80) }}</p>
                    </div>
                    <select name="routes[{{ $category->id }}]" class="form-select">
                        <option value="">— Select recipient —</option>
                        @foreach ($recipients as $recipient)
                            <option value="{{ $recipient->id }}" @selected($category->default_recipient_id == $recipient->id)>
                                {{ $recipient->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endforeach

            <div class="flex justify-end gap-3 border-t border-gray-100 pt-6">
                <button type="submit" class="btn-primary">Save Routing Rules</button>
            </div>
        </form>
    </div>
@endsection
