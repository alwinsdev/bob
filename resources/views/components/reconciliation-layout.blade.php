<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'BOB') }} — Reconciliation Hub</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.SwalBob = Swal.mixin({
            customClass: {
                popup: 'bob-swal-popup',
                title: 'bob-swal-title',
                htmlContainer: 'bob-swal-html',
                confirmButton: 'bob-swal-confirm',
                cancelButton: 'bob-swal-cancel',
                input: 'bob-swal-input'
            },
            buttonsStyling: false
        });
    </script>
    <style>
        .bob-swal-popup {
            background: var(--bob-bg-card) !important;
            color: var(--bob-text-primary) !important;
            border: 1px solid var(--bob-border-medium) !important;
            border-radius: 1rem !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
        }

        .bob-swal-title {
            color: var(--bob-text-primary) !important;
            font-size: 1.125rem !important;
            font-weight: 700 !important;
        }

        .bob-swal-html {
            color: var(--bob-text-muted) !important;
            font-size: 0.875rem !important;
        }

        .bob-swal-confirm {
            background-color: var(--bob-accent) !important;
            color: white !important;
            border: none !important;
            border-radius: 0.5rem !important;
            font-weight: 600 !important;
            font-size: 0.875rem !important;
            padding: 0.5rem 1.25rem !important;
        }

        .bob-swal-cancel {
            background-color: transparent !important;
            color: var(--bob-text-muted) !important;
            border: 1px solid var(--bob-border-medium) !important;
            border-radius: 0.5rem !important;
            font-weight: 600 !important;
            font-size: 0.875rem !important;
            padding: 0.5rem 1.25rem !important;
        }

        .bob-swal-cancel:hover {
            background-color: rgba(255, 255, 255, 0.05) !important;
            color: var(--bob-text-primary) !important;
        }

        .bob-swal-input {
            background-color: var(--bob-bg-input) !important;
            color: var(--bob-text-primary) !important;
            border: 1px solid var(--bob-border-medium) !important;
            border-radius: 0.5rem !important;
            font-size: 0.875rem !important;
        }

        .bob-swal-input:focus {
            border-color: var(--bob-accent) !important;
            box-shadow: 0 0 0 1px var(--bob-accent) !important;
        }
    </style>
    @stack('head')
</head>

<body class="font-sans antialiased ag-theme-bob">

    {{-- ── DARK SIDEBAR ── --}}
    <aside class="bob-sidebar">
        <div class="bob-sidebar-brand">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center shadow-lg"
                style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                </svg>
            </div>
            <div>
                <div class="text-sm font-bold tracking-tight" style="color: var(--bob-text-primary)">BOB System</div>
                <div class="text-[10px] font-semibold uppercase tracking-wider"
                    style="color: var(--bob-accent-muted); opacity: 0.7">Reconciliation</div>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto space-y-0.5">
            <div class="bob-sidebar-section-label">Main</div>

            <a href="{{ route('reconciliation.home') }}" title="Home"
                class="bob-sidebar-nav-item {{ request()->routeIs('reconciliation.home') ? 'active' : '' }}">
                <svg class="w-[18px] h-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
                <span>Home</span>
            </a>

            @can('viewAny', App\Models\ReconciliationQueue::class)
                <a href="{{ route('reconciliation.dashboard') }}" title="Reconciliation Grid"
                    class="bob-sidebar-nav-item {{ request()->routeIs('reconciliation.dashboard') || request()->routeIs('reconciliation.data') ? 'active' : '' }}">
                    <svg class="w-[18px] h-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                    </svg>
                    <span>Reconciliation Grid</span>
                </a>
            @endcan

            <div class="bob-sidebar-section-label">Operations</div>

            @can('create', App\Models\ImportBatch::class)
                <a href="{{ route('reconciliation.upload.index') }}" title="Import Feeds"
                    class="bob-sidebar-nav-item {{ request()->routeIs('reconciliation.upload.*') ? 'active' : '' }}">
                    <svg class="w-[18px] h-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                    </svg>
                    <span>Import Feeds</span>
                </a>
            @endcan

            <a href="{{ route('reconciliation.locklist.index') }}" title="Lock List"
                class="bob-sidebar-nav-item {{ request()->routeIs('reconciliation.locklist.*') ? 'active' : '' }}">
                <svg class="w-[18px] h-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                </svg>
                <span>Lock List</span>
            </a>

            @can('reconciliation.results.view')
                {{-- ── Reporting Section ── --}}
                <div class="bob-sidebar-section-label">Reporting</div>

                <a href="{{ route('reconciliation.reporting.dashboard') }}" title="Comm. Dashboard"
                    class="bob-sidebar-nav-item {{ request()->routeIs('reconciliation.reporting.dashboard') ? 'active' : '' }}">
                    <svg class="w-[18px] h-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" />
                    </svg>
                    <span>Comm. Dashboard</span>
                </a>

                <a href="{{ route('reconciliation.reporting.final-bob') }}" title="Final BOB"
                    class="bob-sidebar-nav-item {{ request()->routeIs('reconciliation.reporting.final-bob') ? 'active' : '' }}">
                    <svg class="w-[18px] h-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0119.5 16.5h-2.25m-9 0h9l-4.5 5.25L9 16.5z" />
                    </svg>
                    <span>Final BOB</span>
                </a>

                <a href="{{ route('reconciliation.reporting.contract-patches') }}" title="Commission Adjustments"
                    class="bob-sidebar-nav-item {{ request()->routeIs('reconciliation.reporting.contract-patches*') ? 'active' : '' }}">
                    <svg class="w-[18px] h-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    <span>Commission</span>
                </a>

                <a href="{{ route('reconciliation.reporting.locklist-impact') }}" title="Locklist Impact"
                    class="bob-sidebar-nav-item {{ request()->routeIs('reconciliation.reporting.locklist-impact') ? 'active' : '' }}">
                    <svg class="w-[18px] h-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                    <span>Locklist Impact</span>
                </a>

            @endcan

            @can('reconciliation.bulk_approve')
                <div class="bob-sidebar-section-label">System</div>

                <a href="{{ route('reconciliation.audit-logs') }}" title="Audit Logs"
                    class="bob-sidebar-nav-item {{ request()->routeIs('reconciliation.audit-logs') ? 'active' : '' }}">
                    <svg class="w-[18px] h-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    <span>Audit Logs</span>
                </a>
            @endcan

            @can('access.manage')
                @cannot('reconciliation.bulk_approve')
                    <div class="bob-sidebar-section-label">System</div>
                @endcannot
                <a href="{{ route('reconciliation.access-control.index') }}" title="Access Control"
                    class="bob-sidebar-nav-item {{ request()->routeIs('reconciliation.access-control.*') ? 'active' : '' }}">
                    <svg class="w-[18px] h-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 6.75a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.118a7.5 7.5 0 0115 0M19.5 10.5h-6m0 0v-6m0 6l4.5 4.5" />
                    </svg>
                    <span>Access Control</span>
                </a>
            @endcan

        </nav>

        <div class="bob-sidebar-footer-link-wrap shrink-0 px-4 pb-2">
            <a href="{{ route('reconciliation.settings') }}" title="Settings"
                class="bob-sidebar-nav-item {{ request()->routeIs('reconciliation.settings') ? 'active' : '' }} mx-0">
                <svg class="w-[18px] h-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>Settings</span>
            </a>
        </div>

        {{-- User with dropdown --}}
        <div class="bob-sidebar-footer-user shrink-0 px-4 py-4" style="border-top: 1px solid var(--bob-border-subtle);"
            x-data="{
                userMenu: false,
                theme: localStorage.getItem('bob_theme') || 'dark',
                settingsUpdateUrl: @js(route('reconciliation.settings.update')),
                setTheme(mode) {
                    this.theme = mode;
                    if (window.bobSetTheme) {
                        window.bobSetTheme(mode);
                    } else {
                        localStorage.setItem('bob_theme', mode);
                        document.documentElement.classList.toggle('bob-light', mode === 'light');
                    }

                    this.persistThemePreference(mode);
                },
                async persistThemePreference(mode) {
                    try {
                        await fetch(this.settingsUpdateUrl, {
                            method: 'PUT',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']')?.getAttribute('content') || '',
                            },
                            body: JSON.stringify({ theme: mode }),
                        });
                    } catch (error) {
                        // Keep UI responsive even if preference sync fails.
                    }
                }
            }">
            <div class="relative">
                <button @click="userMenu = !userMenu" title="User Menu"
                    class="bob-sidebar-user-trigger w-full flex items-center gap-3 group cursor-pointer">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-bold shadow-lg shrink-0"
                        style="background: linear-gradient(135deg, #6366f1, #a855f7); color: white;">
                        {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                    </div>
                    <div class="bob-sidebar-user-meta flex-1 min-w-0 text-left">
                        <div class="text-[13px] font-semibold truncate" style="color: var(--bob-text-primary)">
                            {{ Auth::user()->name }}</div>
                        <div class="text-[11px] truncate" style="color: var(--bob-text-faint)">
                            {{ Auth::user()->roles->pluck('name')->first() ?? 'User' }}</div>
                    </div>
                    <svg class="bob-sidebar-user-chevron w-4 h-4 transition-all shrink-0"
                        style="color: var(--bob-text-faint)" :class="userMenu ? 'rotate-180' : ''" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                    </svg>
                </button>

                {{-- Dropdown --}}
                <div x-show="userMenu" x-cloak x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-2" @click.outside="userMenu = false"
                    class="bob-sidebar-user-menu absolute bottom-full left-0 right-0 mb-2 rounded-xl overflow-hidden shadow-2xl"
                    style="background: #1e1b4b; border: 1px solid rgba(255,255,255,0.12); backdrop-filter: blur(20px);">

                    <div class="px-4 py-3 flex items-center gap-3"
                        style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-bold"
                            style="background: linear-gradient(135deg, #6366f1, #a855f7); color: white; border: 2px solid rgba(255,255,255,0.1);">
                            {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                        </div>
                        <div class="min-w-0">
                            <div class="text-sm font-bold text-white truncate">{{ Auth::user()->name }}</div>
                            <div class="text-[11px] text-indigo-200/60 truncate">{{ Auth::user()->email }}</div>
                        </div>
                    </div>

                    <div class="py-1.5">
                        <a href="{{ route('reconciliation.settings') }}?tab=profile"
                            class="flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-indigo-100/70 hover:text-white hover:bg-white/8 transition-all">
                            <svg class="w-4 h-4 text-indigo-200/50" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                            My Profile
                        </a>
                        <a href="{{ route('reconciliation.settings') }}?tab=security"
                            class="flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-indigo-100/70 hover:text-white hover:bg-white/8 transition-all">
                            <svg class="w-4 h-4 text-indigo-200/50" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                            Account Security
                        </a>

                        <div class="px-4 pt-2 pb-1">
                            <div class="text-[10px] uppercase tracking-wider font-bold text-indigo-200/50">Theme</div>
                            <div class="mt-2 grid grid-cols-2 gap-2">
                                <button type="button" @click="setTheme('dark')"
                                    class="inline-flex items-center justify-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[11px] font-semibold border transition-all"
                                    :class="theme === 'dark'
                                                ? 'bg-white/12 text-white border-white/20'
                                                : 'bg-transparent text-indigo-100/70 border-white/10 hover:bg-white/8'">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                                    </svg>
                                    Dark
                                </button>
                                <button type="button" @click="setTheme('light')"
                                    class="inline-flex items-center justify-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[11px] font-semibold border transition-all"
                                    :class="theme === 'light'
                                                ? 'bg-white/12 text-white border-white/20'
                                                : 'bg-transparent text-indigo-100/70 border-white/10 hover:bg-white/8'">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                                    </svg>
                                    Light
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="py-1.5" style="border-top: 1px solid rgba(255,255,255,0.06);">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-rose-400 hover:text-rose-300 hover:bg-rose-500/5 transition-all">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                                </svg>
                                Sign Out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    {{-- ── MAIN ── --}}
    <div class="bob-main">
        <header class="bob-topbar">
            <div class="bob-topbar-title-wrap">
                @isset($pageTitle)
                    <h1 class="text-lg font-bold tracking-tight" style="color: var(--bob-text-primary)">{{ $pageTitle }}
                    </h1>
                @endisset
                @isset($pageSubtitle)
                    <p class="text-xs mt-0.5 font-medium" style="color: var(--bob-text-muted)">{{ $pageSubtitle }}</p>
                @endisset
            </div>
            <div class="bob-topbar-actions flex items-center gap-3">
                @isset($headerActions)
                    <div class="bob-topbar-action-group flex items-center gap-3">
                        {{ $headerActions }}
                    </div>
                @endisset
                <div x-data="{ time: '' }"
                    x-init="setInterval(() => { time = new Date().toLocaleTimeString('en-US', {hour: '2-digit', minute:'2-digit'}); }, 1000)"
                    class="bob-topbar-clock text-[11px] font-mono font-semibold px-3 py-1.5 rounded-lg"
                    style="background: var(--bob-bg-input); border: 1px solid var(--bob-border-light); color: var(--bob-text-muted)">
                    <span x-text="time"></span>
                </div>
            </div>
        </header>

        <main class="p-6 lg:p-8">
            {{ $slot }}
        </main>
    </div>

    @stack('scripts')
</body>

</html>