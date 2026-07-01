@props([
    'mode' => 'single',
    'namePrefix' => 'to',
    'label' => 'To',
    'required' => false,
    'placeholder' => 'Search by name or email...',
])

<div
    x-data="directoryPicker({
        mode: @js($mode),
        namePrefix: @js($namePrefix),
        searchUrl: @js(route('directory.search')),
        placeholder: @js($placeholder),
    })"
    class="relative"
    @click.outside="open = false"
>
    <label class="form-label" for="{{ $namePrefix }}_search">
        {{ $label }}
        @if ($required)
            <span class="text-danger-500" aria-hidden="true">*</span>
            <span class="sr-only">(required)</span>
        @endif
    </label>

    <template x-if="mode === 'single' && selected">
        <div class="mb-2">
            <span class="chip-recipient">
                <span x-text="selected.label || selected.email"></span>
                <button type="button" class="rounded-full p-0.5 hover:bg-primary-100" @click="remove(selected.email)" aria-label="Remove recipient">
                    <span class="material-symbols-outlined text-[16px]">close</span>
                </button>
            </span>
            <input type="hidden" name="{{ $namePrefix }}[email]" :value="selected?.email">
            <input type="hidden" name="{{ $namePrefix }}[name]" :value="selected?.name ?? ''">
        </div>
    </template>

    <template x-if="mode === 'multiple'">
        <div class="mb-2 flex flex-wrap gap-2" x-show="selected.length">
            <template x-for="(item, index) in selected" :key="item.email">
                <span class="chip-recipient">
                    <span x-text="item.label || item.email"></span>
                    <button type="button" class="rounded-full p-0.5 hover:bg-primary-100" @click="remove(item.email)" :aria-label="`Remove ${item.email}`">
                        <span class="material-symbols-outlined text-[16px]">close</span>
                    </button>
                    <input type="hidden" :name="`${namePrefix}[${index}][email]`" :value="item.email">
                    <input type="hidden" :name="`${namePrefix}[${index}][name]`" :value="item.name ?? ''">
                </span>
            </template>
        </div>
    </template>

    <div class="relative">
        <span class="material-symbols-outlined pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-[20px] text-gray-400">person</span>
        <input
            id="{{ $namePrefix }}_search"
            type="text"
            class="form-input-icon"
            :placeholder="placeholder"
            x-model="query"
            @input="onInput()"
            @focus="query && fetchResults()"
            @keydown.escape.prevent="open = false"
            role="combobox"
            aria-autocomplete="list"
            :aria-expanded="open"
            aria-controls="{{ $namePrefix }}_listbox"
        >

        <div
            x-show="open"
            x-transition
            id="{{ $namePrefix }}_listbox"
            role="listbox"
            class="absolute z-20 mt-1 max-h-60 w-full overflow-auto rounded-lg border border-gray-200 bg-white py-1 shadow-lg"
        >
            <template x-if="loading">
                <p class="px-3 py-2 text-sm text-gray-400">Searching...</p>
            </template>
            <template x-if="!loading && results.length === 0">
                <p class="px-3 py-2 text-sm italic text-gray-400">No matches found. Type an email to add manually.</p>
            </template>
            <template x-for="(item, index) in results" :key="item.email">
                <button
                    type="button"
                    class="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-gray-50"
                    role="option"
                    @click="select(item)"
                >
                    <span>
                        <span class="font-medium text-gray-800" x-text="item.label || item.email"></span>
                        <span class="block text-xs text-gray-500" x-show="item.email !== item.label" x-text="item.email"></span>
                    </span>
                    <span x-show="item.custom" class="text-xs text-gray-400">Use email</span>
                </button>
                <div x-show="item.custom && index === results.length - 2" class="my-1 border-t border-gray-200"></div>
            </template>
        </div>
    </div>

    @error($namePrefix)
        <p class="form-error">{{ $message }}</p>
    @enderror
    @error($namePrefix.'.email')
        <p class="form-error">{{ $message }}</p>
    @enderror
</div>
