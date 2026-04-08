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
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                --auth-bg-start: #0a1024;
                --auth-bg-end: #1d2458;
                --auth-surface: rgba(16, 24, 48, 0.76);
                --auth-border: rgba(156, 173, 210, 0.22);
                --auth-input-bg: rgba(14, 22, 44, 0.72);
                --auth-input-border: rgba(156, 173, 210, 0.35);
                --auth-text-main: #eef2ff;
                --auth-text-muted: #a4b0cf;
                --auth-text-faint: #8b97bc;
                --auth-accent: #7f8bff;
                --auth-accent-soft: #c7d2fe;
                --auth-danger: #fda4af;
                --auth-success-bg: rgba(34, 197, 94, 0.14);
                --auth-success-border: rgba(74, 222, 128, 0.35);
                --auth-success-text: #bbf7d0;
            }

            body {
                margin: 0;
                font-family: 'Inter', system-ui, -apple-system, sans-serif;
                color: var(--auth-text-main);
                background: var(--auth-bg-start);
            }

            .auth-page {
                position: relative;
                min-height: 100vh;
                overflow: hidden;
                background:
                    radial-gradient(900px 460px at 14% 20%, rgba(96, 165, 250, 0.18), transparent 62%),
                    radial-gradient(760px 420px at 86% 16%, rgba(129, 140, 248, 0.2), transparent 64%),
                    linear-gradient(130deg, var(--auth-bg-start), var(--auth-bg-end));
            }

            .auth-page::before,
            .auth-page::after {
                content: '';
                position: absolute;
                pointer-events: none;
            }

            .auth-page::before {
                width: 440px;
                height: 440px;
                border-radius: 999px;
                right: -120px;
                bottom: -160px;
                background: radial-gradient(circle, rgba(99, 102, 241, 0.2), transparent 68%);
            }

            .auth-page::after {
                inset: 0;
                background-image: linear-gradient(rgba(148, 163, 184, 0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(148, 163, 184, 0.05) 1px, transparent 1px);
                background-size: 34px 34px;
                mask-image: radial-gradient(circle at center, black 0%, transparent 78%);
            }

            .auth-shell {
                width: min(1120px, 95vw);
                min-height: 95vh;
                margin: 0 auto;
                display: grid;
                grid-template-columns: 1.08fr 1fr;
                align-items: center;
                gap: 2.5rem;
                position: relative;
                z-index: 2;
                padding: 2rem 0;
            }

            .auth-brand {
                padding: 2rem;
            }

            .auth-brand-top {
                display: inline-flex;
                align-items: center;
                gap: 0.85rem;
                margin-bottom: 1.35rem;
            }

            .auth-brand-logo {
                width: 3.15rem;
                height: 3.15rem;
                border-radius: 0.95rem;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: linear-gradient(145deg, #5b6fff, #8b5cf6);
                box-shadow: 0 16px 24px rgba(79, 70, 229, 0.35);
            }

            .auth-brand-name {
                font-size: 1.85rem;
                font-weight: 800;
                letter-spacing: -0.02em;
                color: #ffffff;
                line-height: 1.05;
            }

            .auth-brand-kicker {
                font-size: 0.78rem;
                font-weight: 700;
                letter-spacing: 0.12em;
                text-transform: uppercase;
                color: var(--auth-accent-soft);
            }

            .auth-brand-copy {
                margin-top: 1.15rem;
                max-width: 30rem;
                color: var(--auth-text-muted);
                line-height: 1.65;
                font-size: 0.95rem;
            }

            .auth-brand-list {
                margin-top: 1.4rem;
                display: grid;
                gap: 0.65rem;
            }

            .auth-brand-list-item {
                display: flex;
                align-items: center;
                gap: 0.6rem;
                color: var(--auth-text-faint);
                font-size: 0.86rem;
                font-weight: 600;
            }

            .auth-dot {
                width: 0.48rem;
                height: 0.48rem;
                border-radius: 999px;
                background: linear-gradient(145deg, #93c5fd, #818cf8);
                box-shadow: 0 0 0 4px rgba(129, 140, 248, 0.16);
            }

            .auth-panel {
                display: flex;
                justify-content: center;
                width: 100%;
            }

            .auth-card {
                width: min(470px, 100%);
                border-radius: 1.15rem;
                border: 1px solid var(--auth-border);
                background: linear-gradient(160deg, rgba(19, 29, 56, 0.9), var(--auth-surface));
                backdrop-filter: blur(14px);
                box-shadow: 0 24px 48px rgba(5, 10, 30, 0.5);
                padding: 2rem;
            }

            .auth-title {
                margin: 0;
                font-size: 1.65rem;
                font-weight: 800;
                color: #ffffff;
                letter-spacing: -0.02em;
            }

            .auth-subtitle {
                margin: 0.45rem 0 1.3rem;
                color: var(--auth-text-muted);
                font-size: 0.92rem;
                line-height: 1.55;
            }

            .auth-status {
                margin-bottom: 1rem;
                border-radius: 0.7rem;
                padding: 0.7rem 0.85rem;
                border: 1px solid var(--auth-success-border);
                background: var(--auth-success-bg);
                color: var(--auth-success-text);
                font-size: 0.83rem;
                font-weight: 600;
            }

            .auth-form {
                display: grid;
                gap: 0.95rem;
            }

            .auth-field {
                display: grid;
                gap: 0.42rem;
            }

            .auth-label {
                font-size: 0.78rem;
                font-weight: 700;
                letter-spacing: 0.05em;
                text-transform: uppercase;
                color: #cbd5f2;
            }

            .auth-input {
                width: 100%;
                border-radius: 0.74rem;
                border: 1px solid var(--auth-input-border);
                background: var(--auth-input-bg);
                color: var(--auth-text-main);
                font-size: 0.94rem;
                padding: 0.78rem 0.88rem;
                transition: border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
            }

            .auth-input::placeholder {
                color: #7f8aaa;
            }

            .auth-input:focus {
                outline: none;
                border-color: var(--auth-accent);
                box-shadow: 0 0 0 3px rgba(127, 139, 255, 0.2);
                background: rgba(15, 24, 50, 0.85);
            }

            .auth-input:-webkit-autofill,
            .auth-input:-webkit-autofill:hover,
            .auth-input:-webkit-autofill:focus {
                -webkit-text-fill-color: var(--auth-text-main);
                -webkit-box-shadow: 0 0 0 30px #18213f inset;
                transition: background-color 5000s ease-in-out 0s;
            }

            .auth-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.9rem;
                flex-wrap: wrap;
                margin-top: 0.2rem;
            }

            .auth-check {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                color: var(--auth-text-muted);
                font-size: 0.86rem;
                font-weight: 600;
                user-select: none;
            }

            .auth-check input {
                width: 0.95rem;
                height: 0.95rem;
                border-radius: 0.25rem;
                border: 1px solid var(--auth-input-border);
                accent-color: var(--auth-accent);
                background: rgba(15, 23, 42, 0.9);
            }

            .auth-link {
                color: var(--auth-accent-soft);
                text-decoration: none;
                font-size: 0.86rem;
                font-weight: 600;
                transition: color 0.15s ease;
            }

            .auth-link:hover {
                color: #e0e7ff;
                text-decoration: underline;
            }

            .auth-submit {
                margin-top: 0.25rem;
                width: 100%;
                border: none;
                border-radius: 0.74rem;
                padding: 0.82rem 0.95rem;
                background: linear-gradient(135deg, #6475ff, #8b5cf6);
                color: #ffffff;
                font-size: 0.78rem;
                font-weight: 800;
                letter-spacing: 0.09em;
                text-transform: uppercase;
                cursor: pointer;
                box-shadow: 0 10px 20px rgba(79, 70, 229, 0.35);
                transition: transform 0.16s ease, box-shadow 0.16s ease, filter 0.16s ease;
            }

            .auth-submit:hover {
                transform: translateY(-1px);
                box-shadow: 0 14px 24px rgba(79, 70, 229, 0.42);
                filter: brightness(1.03);
            }

            .auth-submit:focus-visible {
                outline: none;
                box-shadow: 0 0 0 3px rgba(129, 140, 248, 0.4), 0 14px 24px rgba(79, 70, 229, 0.42);
            }

            .auth-error {
                margin: 0.1rem 0 0;
                color: var(--auth-danger);
                font-size: 0.79rem;
                font-weight: 600;
            }

            .auth-footnote {
                margin-top: 1rem;
                text-align: center;
                color: var(--auth-text-faint);
                font-size: 0.84rem;
                font-weight: 600;
            }

            .auth-security {
                margin-top: 1rem;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.45rem;
                color: var(--auth-text-faint);
                opacity: 0.95;
                font-size: 0.78rem;
                font-weight: 600;
            }

            @media (max-width: 1024px) {
                .auth-shell {
                    grid-template-columns: 1fr;
                    align-content: center;
                    gap: 1.25rem;
                    padding: 1.25rem 0 1.5rem;
                }

                .auth-brand {
                    padding: 1rem 0.5rem 0;
                    text-align: center;
                }

                .auth-brand-top {
                    justify-content: center;
                }

                .auth-brand-copy,
                .auth-brand-list {
                    display: none;
                }

                .auth-card {
                    padding: 1.5rem 1.25rem;
                }
            }
        </style>
    </head>
    <body>
        <div class="auth-page">
            <main class="auth-shell">
                <section class="auth-brand">
                    <a href="/" class="auth-brand-top" aria-label="BOB System Home">
                        <span class="auth-brand-logo" aria-hidden="true">
                            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                            </svg>
                        </span>
                        <span>
                            <span class="auth-brand-name">BOB System</span>
                            <span class="auth-brand-kicker">Secure Platform Access</span>
                        </span>
                    </a>

                    <p class="auth-brand-copy">
                        Access enterprise reconciliation workflows with secure identity controls, complete audit traceability, and role-based operations for production teams.
                    </p>

                    <div class="auth-brand-list" aria-hidden="true">
                        <div class="auth-brand-list-item"><span class="auth-dot"></span>Centralized reconciliation and reporting workspace</div>
                        <div class="auth-brand-list-item"><span class="auth-dot"></span>Role-aware controls for operational governance</div>
                        <div class="auth-brand-list-item"><span class="auth-dot"></span>Encrypted sessions with monitored access lifecycle</div>
                    </div>
                </section>

                <section class="auth-panel">
                    <div class="auth-card">
                        {{ $slot }}
                    </div>
                </section>
            </main>

            <div class="auth-security" role="status" aria-live="polite">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Secure 256-bit encrypted connection
            </div>
        </div>
    </body>
</html>
