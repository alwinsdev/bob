<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'BOB') }} — Reconciliation Hub</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('head')
    </head>
    <body class="font-sans antialiased" style="background: rgb(15 23 42);">

        {{-- ── SIDEBAR ── --}}
        <aside class="bob-sidebar" x-data="{ collapsed: false }">
            {{-- Brand --}}
            <div class="bob-sidebar-brand">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, rgb(99 102 241), rgb(168 85 247));">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                    </svg>
                </div>
                <div>
                    <div class="text-sm font-bold" style="color: rgb(241 245 249);">BOB System</div>
                    <div class="text-[10px] font-medium" style="color: rgb(148 163 184);">Reconciliation Hub</div>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 py-4 overflow-y-auto space-y-0.5">
                <div class="bob-sidebar-section-label">Main</div>

                <a href="{{ route('dashboard') }}" class="bob-sidebar-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                    <span>Home</span>
                </a>

                @can('viewAny', App\Models\ReconciliationQueue::class)
                <a href="{{ route('reconciliation.dashboard') }}" class="bob-sidebar-nav-item {{ request()->routeIs('reconciliation.dashboard') || request()->routeIs('reconciliation.data') ? 'active' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                    </svg>
                    <span>Reconciliation Grid</span>
                </a>
                @endcan

                <div class="bob-sidebar-section-label">Operations</div>

                @can('create', App\Models\ImportBatch::class)
                <a href="{{ route('reconciliation.upload.index') }}" class="bob-sidebar-nav-item {{ request()->routeIs('reconciliation.upload.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                    </svg>
                    <span>Import Feeds</span>
                </a>
                @endcan

                @can('reconciliation.bulk_approve')
                <a href="{{ route('reconciliation.audit-logs') }}" class="bob-sidebar-nav-item {{ request()->routeIs('reconciliation.audit-logs') ? 'active' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    <span>Audit Logs</span>
                </a>
                @endcan

                @can('reconciliation.results.view')
                <div class="bob-sidebar-section-label">Reporting</div>

                <a href="{{ route('reconciliation.reporting.dashboard') }}" class="bob-sidebar-nav-item {{ request()->routeIs('reconciliation.reporting.dashboard') ? 'active' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" />
                    </svg>
                    <span>Comm. Dashboard</span>
                </a>

                <a href="{{ route('reconciliation.reporting.final-bob') }}" class="bob-sidebar-nav-item {{ request()->routeIs('reconciliation.reporting.final-bob') ? 'active' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0119.5 16.5h-2.25m-9 0h9l-4.5 5.25L9 16.5z" />
                    </svg>
                    <span>Final BOB</span>
                </a>

                <a href="{{ route('reconciliation.reporting.locklist-impact') }}" class="bob-sidebar-nav-item {{ request()->routeIs('reconciliation.reporting.locklist-impact') ? 'active' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625z" />
                    </svg>
                    <span>Locklist Impact</span>
                </a>
                @endcan

                <div class="bob-sidebar-section-label">System</div>

                <a href="{{ route('reconciliation.settings') }}" class="bob-sidebar-nav-item {{ request()->routeIs('reconciliation.settings') ? 'active' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>Settings</span>
                </a>
            </nav>

            {{-- User Footer --}}
            <div class="shrink-0 px-4 py-3" style="border-top: 1px solid rgba(71 85 105 / 0.2);">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold" style="background: linear-gradient(135deg, rgb(99 102 241), rgb(168 85 247)); color: white;">
                        {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium truncate" style="color: rgb(241 245 249);">{{ Auth::user()->name }}</div>
                        <div class="text-[11px] truncate" style="color: rgb(148 163 184);">{{ Auth::user()->roles->pluck('name')->first() ?? 'User' }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="p-1.5 rounded-lg transition-colors hover:bg-slate-700/50" style="color: rgb(148 163 184);" title="Sign Out">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- ── MAIN CONTENT ── --}}
        <div class="bob-main">
            {{-- Top Bar --}}
            <header class="bob-topbar">
                <div>
                    @isset($pageTitle)
                        <h1 class="text-lg font-semibold" style="color: rgb(241 245 249);">{{ $pageTitle }}</h1>
                    @endisset
                    @isset($pageSubtitle)
                        <p class="text-xs mt-0.5" style="color: rgb(148 163 184);">{{ $pageSubtitle }}</p>
                    @endisset
                </div>
                <div class="flex items-center gap-4">
                    @isset($headerActions)
                        {{ $headerActions }}
                    @endisset
                    {{-- Live Clock --}}
                    <div x-data="{ time: '' }" x-init="setInterval(() => { time = new Date().toLocaleTimeString('en-US', {hour: '2-digit', minute:'2-digit'}); }, 1000)" class="text-xs font-mono px-3 py-1.5 rounded-lg" style="color: rgb(148 163 184); background: rgba(30 41 59 / 0.5);">
                        <span x-text="time"></span>
                    </div>
                </div>
            </header>

            {{-- Page Content --}}
            <main class="p-6">
                {{ $slot }}
            </main>
        </div>

        @stack('scripts')
    </body>
</html>
