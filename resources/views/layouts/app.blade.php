<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'BOB System') }}</title>
        
        {{-- Custom SVG Favicon --}}
        <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='25' fill='%236366f1'/%3E%3Cpath fill='none' stroke='white' stroke-width='8' stroke-linecap='round' stroke-linejoin='round' d='M29.375 35A5.625 5.625 0 0 1 35 29.375h5.625A5.625 5.625 0 0 1 46.25 35v5.625a5.625 5.625 0 0 1-5.625 5.625H35A5.625 5.625 0 0 1 29.375 40.625V35zM29.375 59.375A5.625 5.625 0 0 1 35 53.75h5.625A5.625 5.625 0 0 1 46.25 59.375V65a5.625 5.625 0 0 1-5.625 5.625H35A5.625 5.625 0 0 1 29.375 65v-5.625zM53.75 35A5.625 5.625 0 0 1 59.375 29.375H65A5.625 5.625 0 0 1 70.625 35v5.625a5.625 5.625 0 0 1-5.625 5.625h-5.625A5.625 5.625 0 0 1 53.75 40.625V35zM53.75 59.375A5.625 5.625 0 0 1 59.375 53.75H65A5.625 5.625 0 0 1 70.625 59.375V65A5.625 5.625 0 0 1 65 70.625h-5.625A5.625 5.625 0 0 1 53.75 65v-5.625z'/%3E%3C/svg%3E">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-900">
        <div class="min-h-screen min-h-[100dvh]" style="background:
            radial-gradient(900px 420px at 12% -6%, rgba(37, 99, 235, 0.10), transparent 55%),
            radial-gradient(880px 460px at 88% -12%, rgba(8, 145, 178, 0.08), transparent 58%),
            linear-gradient(180deg, #f8fbff 0%, #edf2f8 100%);">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="border-b border-slate-200/70 bg-white/70 backdrop-blur-xl shadow-[0_24px_60px_-40px_rgba(15,23,42,0.45)]">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>

            <footer class="border-t border-slate-200/70 bg-white/70 backdrop-blur-xl shadow-[0_-24px_60px_-48px_rgba(15,23,42,0.35)]">
                @php
                    $developerName = config('branding.developed_by');
                    $developerUrl = config('branding.developed_by_url');
                    $isRedMindBrand = strcasecmp((string) $developerName, 'RedMind Technologies') === 0;
                @endphp
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 text-xs text-slate-500 flex flex-col md:flex-row md:items-center md:justify-between gap-1.5">
                    <div>
                        Developed by
                        <a href="{{ $developerUrl }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-slate-700 hover:text-slate-900 hover:underline">
                            @if ($isRedMindBrand)
                                <span style="color: #ef4444;">R</span>ed<span style="color: #ef4444;">M</span>ind Technologies
                            @else
                                {{ $developerName }}
                            @endif
                        </a>
                    </div>
                    <div>Support: <a href="mailto:{{ config('branding.support_email') }}" class="font-medium text-slate-600 hover:text-slate-900 hover:underline">{{ config('branding.support_email') }}</a></div>
                    <div>Copyright © {{ 2026 }} {{ config('branding.copyright_holder') }} | Version {{ config('branding.version') }}</div>
                </div>
            </footer>
        </div>
    </body>
</html>
