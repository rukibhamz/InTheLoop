@extends('layouts.app')

@section('title', $category->exists ? 'Edit Category' : 'New Category')

@section('content')
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('categories.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-medium text-primary-600 hover:underline">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            Back to categories
        </a>

        <h1 class="page-title mb-6">{{ $category->exists ? 'Edit Category' : 'New Category' }}</h1>

        <form method="POST" action="{{ $category->exists ? route('categories.update', $category) : route('categories.store') }}" class="form-card space-y-5">
            @csrf
            @if ($category->exists) @method('PUT') @endif

            <div>
                <label for="name" class="form-label">Category Name</label>
                <input id="name" name="name" type="text" class="form-input" value="{{ old('name', $category->name) }}" required>
                @error('name')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="description" class="form-label">Description</label>
                <textarea id="description" name="description" class="form-textarea min-h-[120px]">{{ old('description', $category->description) }}</textarea>
            </div>

            <div>
                <label for="default_recipient_id" class="form-label">Default Recipient (routing)</label>
                <select id="default_recipient_id" name="default_recipient_id" class="form-select">
                    <option value="">None</option>
                    @foreach ($recipients as $recipient)
                        <option value="{{ $recipient->id }}" @selected(old('default_recipient_id', $category->default_recipient_id) == $recipient->id)>
                            {{ $recipient->name }} ({{ $recipient->shared_mailbox_email }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-100 pt-6">
                <a href="{{ route('categories.index') }}" class="btn-ghost">Cancel</a>
                <button type="submit" class="btn-primary">Save Category</button>
            </div>
        </form>

        @if ($category->exists && ! $category->reports()->exists())
            <form method="POST" action="{{ route('categories.destroy', $category) }}" class="mt-6" onsubmit="return confirm('Delete this category?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-secondary border-danger-500 text-danger-600">Delete Category</button>
            </form>
        @endif
    </div>
@endsection
