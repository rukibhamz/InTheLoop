<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $branding->orgName())</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            @foreach ($branding->cssVariables() as $var => $value)
            {{ $var }}: {{ $value }};
            @endforeach
        }
    </style>
    @stack('head')
</head>
<body class="min-h-screen bg-surface" x-data="{ sidebarOpen: false, notificationsOpen: false }">
    <div class="flex min-h-screen min-w-0">
        <div
            x-show="sidebarOpen"
            x-transition.opacity
            class="fixed inset-0 z-40 bg-gray-900/30 backdrop-blur-sm lg:hidden"
            @click="sidebarOpen = false"
            aria-hidden="true"
        ></div>

        <aside
            class="sidebar-shell fixed inset-y-0 left-0 z-50 flex w-[min(100vw-3rem,16rem)] max-w-[85vw] flex-col transition-transform duration-300 lg:static lg:w-64 lg:max-w-none lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="border-b border-outline-variant/40 px-4 py-4 sm:px-5 sm:py-5">
                @include('partials.logo')
            </div>

            <nav
                class="flex-1 space-y-1 overflow-y-auto px-3 py-4"
                aria-label="Main navigation"
                @click="if ($event.target.closest('a')) sidebarOpen = false"
            >
                <a href="{{ route('emails.index') }}" class="nav-item {{ request()->routeIs('emails.*') ? 'nav-item-active' : '' }}">
                    <span class="material-symbols-outlined text-[20px]">mail</span>
                    Email
                </a>

                <a href="{{ route('announcements.index') }}" class="nav-item {{ request()->routeIs('announcements.*') ? 'nav-item-active' : '' }}">
                    <span class="material-symbols-outlined text-[20px]">campaign</span>
                    Announcements
                </a>

                @if (auth()->user()?->isAdmin())
                    <a href="{{ route('recipients.index') }}" class="nav-item {{ request()->routeIs('recipients.*') || request()->routeIs('routing.*') ? 'nav-item-active' : '' }}">
                        <span class="material-symbols-outlined text-[20px]">group</span>
                        Recipients
                    </a>
                    <a href="{{ route('categories.index') }}" class="nav-item {{ request()->routeIs('categories.*') ? 'nav-item-active' : '' }}">
                        <span class="material-symbols-outlined text-[20px]">category</span>
                        Categories
                    </a>
                    <a href="{{ route('users.index') }}" class="nav-item {{ request()->routeIs('users.*') ? 'nav-item-active' : '' }}">
                        <span class="material-symbols-outlined text-[20px]">manage_accounts</span>
                        Users
                    </a>
                    <a href="{{ route('settings.app') }}" class="nav-item {{ request()->routeIs('settings.app') ? 'nav-item-active' : '' }}">
                        <span class="material-symbols-outlined text-[20px]">palette</span>
                        App Settings
                    </a>
                    <a href="{{ route('settings.microsoft') }}" class="nav-item {{ request()->routeIs('settings.microsoft') ? 'nav-item-active' : '' }}">
                        <span class="material-symbols-outlined text-[20px]">cloud</span>
                        Microsoft
                    </a>
                @endif

                <a href="{{ route('settings.account') }}" class="nav-item {{ request()->routeIs('settings.account') ? 'nav-item-active' : '' }}">
                    <span class="material-symbols-outlined text-[20px]">settings</span>
                    Account
                </a>
            </nav>

            <div class="border-t border-outline-variant/40 p-3 lg:hidden">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="nav-item w-full text-left text-danger-600">
                        <span class="material-symbols-outlined text-[20px]">logout</span>
                        Sign out
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-30 flex h-14 items-center justify-between gap-2 border-b border-border-subtle bg-white/90 px-3 backdrop-blur sm:h-16 sm:gap-3 sm:px-4 lg:px-8">
                <div class="flex min-w-0 flex-1 items-center gap-2">
                    <button type="button" class="shrink-0 rounded-lg p-2 text-gray-500 hover:bg-gray-100 lg:hidden" @click="sidebarOpen = true" aria-label="Open navigation">
                        <span class="material-symbols-outlined">menu</span>
                    </button>

                    <div class="min-w-0 flex-1 lg:hidden">
                        @include('partials.logo')
                    </div>

                    <div class="hidden min-w-0 flex-1 lg:block">
                        @hasSection('header')
                            @yield('header')
                        @endif
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-1 sm:gap-2">
                    <div class="relative">
                        <button type="button" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100" @click="notificationsOpen = !notificationsOpen" aria-label="Notifications">
                            <span class="material-symbols-outlined">notifications</span>
                            @if (($notifications ?? collect())->isNotEmpty())
                                <span class="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-danger-500"></span>
                            @endif
                        </button>
                        <div
                            x-show="notificationsOpen"
                            x-cloak
                            @click.outside="notificationsOpen = false"
                            class="absolute right-0 z-50 mt-2 w-[min(20rem,calc(100vw-1.5rem))] rounded-xl border border-gray-200 bg-white py-2 shadow-lg"
                        >
                            <p class="px-4 py-2 text-xs font-semibold tracking-wide text-gray-500 uppercase">Recent activity</p>
                            @forelse ($notifications ?? [] as $notification)
                                <a href="{{ $notification->email_id ? route('emails.show', $notification->email_id) : '#' }}" class="block px-4 py-2 text-sm hover:bg-gray-50" @click="notificationsOpen = false">
                                    <p class="font-medium text-gray-800">{{ ucfirst(str_replace('_', ' ', $notification->type)) }}</p>
                                    <p class="truncate text-xs text-gray-500">{{ $notification->email?->subject }}</p>
                                    <p class="text-xs text-gray-400">{{ $notification->created_at->diffForHumans() }}</p>
                                </a>
                            @empty
                                <p class="px-4 py-3 text-sm text-gray-500">No recent notifications.</p>
                            @endforelse
                        </div>
                    </div>
                    <a href="{{ route('emails.create') }}" class="btn-primary inline-flex p-2.5 sm:hidden" aria-label="New Email">
                        <span class="material-symbols-outlined text-[20px]">add</span>
                    </a>
                    <a href="{{ route('emails.create') }}" class="btn-primary hidden gap-2 sm:inline-flex">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        <span class="hidden md:inline">New Email</span>
                        <span class="md:hidden">New</span>
                    </a>
                    <a href="{{ route('users.show', auth()->user()) }}" class="user-avatar shrink-0" title="{{ auth()->user()->name }}">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                        @csrf
                        <button type="submit" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100" aria-label="Sign out">
                            <span class="material-symbols-outlined">logout</span>
                        </button>
                    </form>
                </div>
            </header>

            <main class="min-w-0 flex-1 overflow-x-hidden px-3 py-4 sm:px-6 sm:py-6 lg:px-8">
                @if (session('success'))
                    <div class="mb-4 rounded-lg border border-success-500/20 bg-success-50 px-4 py-3 text-sm text-success-600 sm:mb-6" role="status">
                        {{ session('success') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
