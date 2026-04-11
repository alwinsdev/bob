<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'BOB') }} — Secure Access</title>
    <meta name="description" content="Sign in to BOB System — Reconciliation, commission governance, and operational intelligence.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* ═══════════════════════════════════════════════════════ */
        /* ██  GUEST AUTH SHELL — Premium Full-Screen Layout    ██ */
        /* ═══════════════════════════════════════════════════════ */

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body.auth-body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            overflow-x: hidden;
        }

        /* ── Full-screen split layout ── */
        .auth-shell {
            display: grid;
            grid-template-columns: 1fr;
            min-height: 100vh;
        }

        @media (min-width: 1024px) {
            .auth-shell {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (min-width: 1280px) {
            .auth-shell {
                grid-template-columns: 55fr 45fr;
            }
        }

        /* ── Left Panel: Cinematic Hero ── */
        .auth-hero {
            display: none;
            position: relative;
            overflow: hidden;
            background: #070b18;
        }

        @media (min-width: 1024px) {
            .auth-hero {
                display: flex;
                flex-direction: column;
                justify-content: center;
                padding: 64px;
            }
        }

        @media (min-width: 1440px) {
            .auth-hero {
                padding: 80px 96px;
            }
        }

        .auth-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(900px 600px at 20% 30%, rgba(99, 102, 241, 0.18), transparent 60%),
                radial-gradient(700px 500px at 80% 70%, rgba(139, 92, 246, 0.12), transparent 55%),
                radial-gradient(500px 400px at 50% 90%, rgba(59, 130, 246, 0.1), transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        .auth-hero::after {
            content: '';
            position: absolute;
            inset: 0;
            background:
                repeating-linear-gradient(
                    0deg,
                    transparent,
                    transparent 1px,
                    rgba(255, 255, 255, 0.012) 1px,
                    rgba(255, 255, 255, 0.012) 2px
                );
            pointer-events: none;
            z-index: 0;
        }

        .auth-hero-content {
            position: relative;
            z-index: 1;
            max-width: 560px;
        }

        .auth-hero-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 56px;
        }

        .auth-hero-logo {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            box-shadow: 0 12px 28px -8px rgba(99, 102, 241, 0.5);
            flex-shrink: 0;
        }

        .auth-hero-logo svg {
            width: 24px;
            height: 24px;
            color: white;
        }

        .auth-hero-app-name {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #f8fafc;
        }

        .auth-hero-app-sub {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: #818cf8;
            margin-top: 2px;
        }

        .auth-hero-headline {
            font-size: 42px;
            font-weight: 900;
            letter-spacing: -0.03em;
            line-height: 1.1;
            color: #f8fafc;
            margin: 0 0 20px;
        }

        .auth-hero-headline span {
            background: linear-gradient(135deg, #818cf8, #a78bfa, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .auth-hero-desc {
            font-size: 16px;
            font-weight: 500;
            line-height: 1.7;
            color: #94a3b8;
            margin: 0 0 48px;
            max-width: 480px;
        }

        /* ── Feature Highlights ── */
        .auth-hero-features {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .auth-feature {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 16px 18px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.025);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.25s ease;
        }

        .auth-feature:hover {
            background: rgba(99, 102, 241, 0.06);
            border-color: rgba(129, 140, 248, 0.18);
            transform: translateX(4px);
        }

        .auth-feature-icon {
            width: 40px;
            height: 40px;
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .auth-feature-icon svg {
            width: 20px;
            height: 20px;
        }

        .auth-feature-icon.indigo  { background: rgba(99, 102, 241, 0.14); color: #818cf8; }
        .auth-feature-icon.emerald { background: rgba(16, 185, 129, 0.14); color: #6ee7b7; }
        .auth-feature-icon.amber   { background: rgba(245, 158, 11, 0.14); color: #fbbf24; }

        .auth-feature-text h4 {
            margin: 0;
            font-size: 14px;
            font-weight: 700;
            color: #e2e8f0;
            letter-spacing: -0.01em;
        }

        .auth-feature-text p {
            margin: 4px 0 0;
            font-size: 12px;
            font-weight: 500;
            color: #64748b;
            line-height: 1.5;
        }

        /* ── Floating Orb Decorations ── */
        .auth-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.35;
            z-index: 0;
            pointer-events: none;
            animation: auth-orb-drift 18s ease-in-out infinite alternate;
        }

        .auth-orb-1 {
            width: 400px;
            height: 400px;
            background: #6366f1;
            top: -120px;
            right: -80px;
            animation-delay: 0s;
        }

        .auth-orb-2 {
            width: 300px;
            height: 300px;
            background: #8b5cf6;
            bottom: -60px;
            left: -40px;
            animation-delay: -6s;
        }

        @keyframes auth-orb-drift {
            0%   { transform: translate(0, 0) scale(1); }
            50%  { transform: translate(20px, -15px) scale(1.05); }
            100% { transform: translate(-10px, 10px) scale(0.97); }
        }

        /* ── Right Panel: Auth Form ── */
        .auth-panel {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px 24px;
            background: #0b1120;
            position: relative;
            overflow: hidden;
        }

        @media (min-width: 640px) {
            .auth-panel {
                padding: 48px 40px;
            }
        }

        @media (min-width: 1024px) {
            .auth-panel {
                padding: 48px 56px;
                border-left: 1px solid rgba(255, 255, 255, 0.04);
            }
        }

        .auth-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(99, 102, 241, 0.3), transparent);
        }

        .auth-panel::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(600px 400px at 50% 30%, rgba(99, 102, 241, 0.04), transparent 60%);
            pointer-events: none;
        }

        /* ── Mobile Brand (shown < 1024px) ── */
        .auth-mobile-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 36px;
        }

        @media (min-width: 1024px) {
            .auth-mobile-brand {
                display: none;
            }
        }

        .auth-mobile-logo {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            box-shadow: 0 10px 24px -6px rgba(99, 102, 241, 0.45);
        }

        .auth-mobile-logo svg {
            width: 22px;
            height: 22px;
            color: white;
        }

        .auth-mobile-name {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #f8fafc;
        }

        /* ── Form Card ── */
        .auth-card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
        }

        /* ── Typography ── */
        .auth-title {
            font-size: 26px;
            font-weight: 900;
            letter-spacing: -0.03em;
            color: #f8fafc;
            margin: 0 0 8px;
        }

        .auth-subtitle {
            font-size: 13px;
            font-weight: 500;
            line-height: 1.6;
            color: #64748b;
            margin: 0 0 28px;
        }

        /* ── Status & Error Banners ── */
        .auth-status {
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            color: #6ee7b7;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            margin-bottom: 20px;
        }

        /* ── Pill Row ── */
        .auth-pill-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 24px;
        }

        .auth-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #c7d2fe;
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(129, 140, 248, 0.2);
        }

        /* ── Form ── */
        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .auth-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .auth-field-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 18px;
        }

        @media (min-width: 480px) {
            .auth-field-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        .auth-label {
            font-size: 12px;
            font-weight: 700;
            color: #cbd5e1;
            letter-spacing: 0.02em;
        }

        .auth-input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .auth-input-icon {
            position: absolute;
            left: 14px;
            width: 18px;
            height: 18px;
            color: #475569;
            pointer-events: none;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
        }

        .auth-input-icon svg {
            width: 100%;
            height: 100%;
        }

        .auth-input {
            width: 100%;
            padding: 12px 16px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(15, 23, 42, 0.6);
            color: #f8fafc;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 500;
            outline: none;
            transition: all 0.2s ease;
        }

        .auth-input-has-icon {
            padding-left: 44px;
        }

        .auth-input::placeholder {
            color: #475569;
            font-weight: 400;
        }

        .auth-input:focus {
            border-color: rgba(99, 102, 241, 0.5);
            background: rgba(15, 23, 42, 0.8);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12), 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .auth-input:focus + .auth-input-icon,
        .auth-input:focus ~ .auth-input-icon {
            color: #818cf8;
        }

        .auth-input-wrap:focus-within .auth-input-icon {
            color: #818cf8;
        }

        .auth-hint {
            margin: 2px 0 0;
            font-size: 11px;
            font-weight: 500;
            color: #475569;
        }

        .auth-error {
            margin: 2px 0 0;
            font-size: 11px;
            font-weight: 600;
            color: #fb7185;
        }

        /* ── Checkbox & Row ── */
        .auth-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .auth-check {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 600;
            color: #94a3b8;
            cursor: pointer;
        }

        .auth-check input[type="checkbox"] {
            width: 16px;
            height: 16px;
            border-radius: 5px;
            border: 1.5px solid rgba(255, 255, 255, 0.12);
            background: rgba(15, 23, 42, 0.6);
            cursor: pointer;
            accent-color: #6366f1;
        }

        /* ── Links ── */
        .auth-link {
            font-size: 12px;
            font-weight: 600;
            color: #818cf8;
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .auth-link:hover {
            color: #a5b4fc;
            text-decoration: underline;
        }

        /* ── Submit Button ── */
        .auth-submit {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 14px 24px;
            margin-top: 4px;
            border: none;
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.01em;
            color: white;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 50%, #7c3aed 100%);
            box-shadow:
                0 10px 24px -8px rgba(99, 102, 241, 0.5),
                0 2px 6px rgba(0, 0, 0, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.12);
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .auth-submit::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.08), transparent 50%);
            pointer-events: none;
        }

        .auth-submit:hover {
            transform: translateY(-1px);
            box-shadow:
                0 16px 32px -8px rgba(99, 102, 241, 0.55),
                0 4px 10px rgba(0, 0, 0, 0.25),
                inset 0 1px 0 rgba(255, 255, 255, 0.15);
        }

        .auth-submit:active {
            transform: translateY(0);
            box-shadow:
                0 6px 14px -4px rgba(99, 102, 241, 0.4),
                inset 0 1px 2px rgba(0, 0, 0, 0.15);
        }

        /* ── Footnote ── */
        .auth-footnote {
            text-align: center;
            margin-top: 24px;
            font-size: 13px;
            font-weight: 500;
            color: #64748b;
        }

        /* ── Footer ── */
        .auth-footer {
            position: relative;
            z-index: 1;
            margin-top: 48px;
            text-align: center;
            font-size: 11px;
            font-weight: 500;
            color: #334155;
            line-height: 1.6;
        }

        .auth-footer a {
            color: #64748b;
            text-decoration: none;
            font-weight: 600;
        }

        .auth-footer a:hover {
            color: #818cf8;
        }

        .auth-footer-redm span {
            color: #ef4444;
            font-weight: 700;
        }

        /* ── Security Signal ── */
        .auth-security-signal {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 20px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #334155;
        }

        .auth-security-signal svg {
            width: 12px;
            height: 12px;
            color: #16a34a;
        }

        /* ═══════════════════════════════════════════════════════ */
        /* ██  RESPONSIVE — Mobile First Polish                ██ */
        /* ═══════════════════════════════════════════════════════ */

        @media (max-width: 639px) {
            .auth-title {
                font-size: 22px;
            }

            .auth-hero-headline {
                font-size: 28px;
            }
        }
    </style>
</head>

<body class="auth-body">
    <div class="auth-shell">
        {{-- ══ LEFT: Cinematic Hero Panel ══ --}}
        <div class="auth-hero">
            <div class="auth-orb auth-orb-1"></div>
            <div class="auth-orb auth-orb-2"></div>

            <div class="auth-hero-content">
                <div class="auth-hero-brand">
                    <div class="auth-hero-logo">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                        </svg>
                    </div>
                    <div>
                        <div class="auth-hero-app-name">{{ config('app.name', 'BOB System') }}</div>
                        <div class="auth-hero-app-sub">Reconciliation Engine</div>
                    </div>
                </div>

                <h1 class="auth-hero-headline">
                    
                    <span>Reconciliation</span><br>
                    Intelligence
                </h1>

                <p class="auth-hero-desc">
                    Automated carrier-to-agent matching, commission governance,
                    and compliance reporting — unified in a single secure workspace.
                </p>

                <div class="auth-hero-features">
                    <div class="auth-feature">
                        <div class="auth-feature-icon indigo">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                            </svg>
                        </div>
                        <div class="auth-feature-text">
                            <h4>Multi-Feed ETL Engine</h4>
                            <p>Stream-process IMS, Health Sherpa, and carrier BOB feeds with intelligent tiered matching.</p>
                        </div>
                    </div>

                    <div class="auth-feature">
                        <div class="auth-feature-icon emerald">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                            </svg>
                        </div>
                        <div class="auth-feature-text">
                            <h4>Role-Governed Access</h4>
                            <p>Fine-grained RBAC with per-action permissions, audit trails, and super-admin governance.</p>
                        </div>
                    </div>

                    <div class="auth-feature">
                        <div class="auth-feature-icon amber">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" />
                            </svg>
                        </div>
                        <div class="auth-feature-text">
                            <h4>Commission Intelligence</h4>
                            <p>Contract patching, locklist impact analysis, and real-time commission dashboards.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══ RIGHT: Auth Form Panel ══ --}}
        <div class="auth-panel">
            {{-- Mobile-only brand --}}
            <div class="auth-mobile-brand">
                <div class="auth-mobile-logo">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                    </svg>
                </div>
                <span class="auth-mobile-name">{{ config('app.name', 'BOB System') }}</span>
            </div>

            <div class="auth-card">
                {{ $slot }}

                {{-- Security Signal --}}
                <div class="auth-security-signal">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                    <span>256-bit encrypted session · CSRF protected</span>
                </div>

                @php
                    $developerName = config('branding.developed_by');
                    $developerUrl = config('branding.developed_by_url');
                    $isRedMindBrand = strcasecmp((string) $developerName, 'RedMind Technologies') === 0;
                @endphp

                <div class="auth-footer">
                    <div>
                        Developed by
                        <a href="{{ $developerUrl }}" target="_blank" rel="noopener noreferrer">
                            @if ($isRedMindBrand)
                                <span class="auth-footer-redm"><span>R</span>ed<span>M</span>ind Technologies</span>
                            @else
                                {{ $developerName }}
                            @endif
                        </a>
                    </div>
                    <div>© {{ date('Y') }} {{ config('branding.copyright_holder') }} · v{{ config('branding.version') }}</div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
