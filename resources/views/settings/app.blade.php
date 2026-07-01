@extends('layouts.app')

@section('title', 'App Settings')

@section('content')
    <div class="mx-auto max-w-6xl">
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <nav class="mb-2 text-sm text-on-surface-variant">
                    <a href="{{ route('settings.account') }}" class="hover:text-primary-600">Settings</a>
                    <span class="mx-1">›</span>
                    <span>App</span>
                </nav>
                <h1 class="page-title">App Settings</h1>
                <p class="mt-1 text-sm text-on-surface-variant">Manage your organization's global identity and brand guidelines.</p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <form method="POST" action="{{ route('settings.app.update') }}" enctype="multipart/form-data" class="form-card">
                    @csrf
                    @method('PUT')

                    <h2 class="mb-6 flex items-center gap-2 text-lg font-semibold text-on-surface">
                        <span class="material-symbols-outlined text-primary-500">palette</span>
                        Global Brand Identity
                    </h2>

                    <div class="space-y-5">
                        <div>
                            <label for="org_name" class="form-label">Organization Name</label>
                            <input id="org_name" name="org_name" type="text" class="form-input" value="{{ old('org_name', $settings->org_name ?? config('app.name')) }}" required>
                            <p class="mt-1 text-xs text-on-surface-variant">This name will appear in internal headers and automated notifications.</p>
                            @error('org_name')<p class="form-error">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="accent_color" class="form-label">Primary Accent Color</label>
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                <input type="color" id="accent_color_picker" class="h-11 w-full max-w-[4rem] cursor-pointer rounded-lg border border-gray-300 p-1 sm:w-14">
                                <input id="accent_color" name="accent_color" type="text" class="form-input w-full font-mono uppercase sm:max-w-[200px]" value="{{ old('accent_color', $settings->accent_color ?? '#4648D4') }}" required pattern="^#[0-9A-Fa-f]{6}$">
                            </div>
                            <p class="mt-1 text-xs text-on-surface-variant">Applied to buttons, active navigation states, and primary UI elements.</p>
                            @error('accent_color')<p class="form-error">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="form-label">Company Logo</label>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="upload-zone py-8">
                                    <input id="logo" name="logo" type="file" class="sr-only" accept=".svg,.png,.jpg,.jpeg,.webp,image/*">
                                    <label for="logo" class="cursor-pointer">
                                        <span class="material-symbols-outlined mb-2 text-[36px] text-primary-500">cloud_upload</span>
                                        <p class="text-sm font-semibold text-on-surface">Click to upload or drag and drop</p>
                                        <p class="mt-1 text-xs text-on-surface-variant">SVG, PNG (max 2MB)</p>
                                    </label>
                                </div>

                                @if ($settings->logo_path)
                                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                        <p class="mb-2 text-xs font-semibold tracking-wide text-on-surface-variant uppercase">Current file</p>
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-medium text-on-surface">{{ basename($settings->logo_path) }}</p>
                                                <p class="text-xs text-on-surface-variant">Uploaded logo</p>
                                            </div>
                                        </div>
                                        <label class="mt-3 flex cursor-pointer items-center gap-2 text-sm text-danger-600">
                                            <input type="checkbox" name="remove_logo" value="1" class="rounded border-gray-300 text-danger-500 focus:ring-danger-500">
                                            Remove current logo
                                        </label>
                                    </div>
                                @endif
                            </div>
                            @error('logo')<p class="form-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                            <p class="mb-3 text-xs font-semibold tracking-wide text-on-surface-variant uppercase">Preview</p>
                            <div class="flex items-center gap-3 rounded-lg bg-white p-4 shadow-sm">
                                @if ($branding->logoUrl())
                                    <img src="{{ $branding->logoUrl() }}" alt="Logo preview" class="h-10 w-auto">
                                @else
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-500">
                                        <span class="material-symbols-outlined material-symbols-filled text-white">sync_alt</span>
                                    </div>
                                    <span class="text-lg font-bold text-primary-500">{{ $settings->org_name ?? config('app.name') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex flex-col-reverse gap-3 border-t border-gray-100 pt-6 sm:flex-row sm:justify-end">
                        <a href="{{ route('settings.account') }}" class="btn-ghost justify-center">Discard</a>
                        <button type="submit" class="btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>

            <div class="space-y-4">
                <section class="info-banner flex-col items-start">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined shrink-0 text-primary-500">description</span>
                        <div>
                            <p class="font-medium text-on-surface">Branding Guidelines</p>
                            <p class="mt-1 text-sm">Refer to your organization's style guide for typography and spacing standards.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined shrink-0 text-primary-500">shield</span>
                        <p class="text-sm">Changes to global branding require administrator access.</p>
                    </div>
                </section>

                <section class="form-card overflow-hidden p-0">
                    <div class="h-32 bg-gradient-to-br from-primary-500 to-primary-700"></div>
                    <div class="p-4">
                        <h3 class="font-semibold text-on-surface">InTheLoop Atmosphere</h3>
                        <p class="mt-1 text-sm text-on-surface-variant">Brand configuration shapes how staff experience the platform across reports, email, and approvals.</p>
                    </div>
                </section>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            const picker = document.getElementById('accent_color_picker');
            const input = document.getElementById('accent_color');
            if (picker && input) {
                picker.addEventListener('input', () => input.value = picker.value.toUpperCase());
                input.addEventListener('input', () => {
                    if (/^#[0-9A-Fa-f]{6}$/.test(input.value)) picker.value = input.value;
                });
            }
        </script>
    @endpush
@endsection
