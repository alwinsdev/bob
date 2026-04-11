<x-guest-layout>
    <h1 class="auth-title">Welcome Back</h1>
    <p class="auth-subtitle">Sign in to access live reconciliation operations, reporting insights, and controlled actions.</p>

    <div class="auth-pill-row" aria-hidden="true">
        <span class="auth-pill">Role-Based Access</span>
        <span class="auth-pill">Auditable Session</span>
        <span class="auth-pill">Session Security</span>
    </div>

    @if (session('status'))
        <div class="auth-status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="auth-form" novalidate>
        @csrf

        <div class="auth-field">
            <label for="email" class="auth-label">Work Email</label>
            <div class="auth-input-wrap">
                <span class="auth-input-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5A2.25 2.25 0 0119.5 19.5h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15A2.25 2.25 0 002.25 6.75m19.5 0l-8.69 5.212a2.25 2.25 0 01-2.12 0L2.25 6.75"/>
                    </svg>
                </span>
                <input id="email"
                       class="auth-input auth-input-has-icon"
                       type="email"
                       name="email"
                       value="{{ old('email') }}"
                       placeholder="you@company.com"
                       required
                       autofocus
                      inputmode="email"
                      autocapitalize="off"
                      autocorrect="off"
                       autocomplete="username">
            </div>
            @error('email')
                <p class="auth-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-field">
            <label for="password" class="auth-label">Password</label>
            <div class="auth-input-wrap">
                <span class="auth-input-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V7.875a4.5 4.5 0 10-9 0V10.5m-.75 9h10.5A2.25 2.25 0 0019.5 17.25v-4.5a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 12.75v4.5A2.25 2.25 0 006.75 19.5z"/>
                    </svg>
                </span>
                <input id="password"
                       class="auth-input auth-input-has-icon"
                       type="password"
                       name="password"
                       placeholder="Enter your password"
                       required
                       autocomplete="current-password">
            </div>
            @error('password')
                <p class="auth-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-row">
            <label for="remember_me" class="auth-check">
                <input id="remember_me" type="checkbox" name="remember" value="1" @checked(old('remember'))>
                <span>Keep me signed in on this device</span>
            </label>

            @if (Route::has('password.request'))
                <a class="auth-link" href="{{ route('password.request') }}">
                    Forgot password?
                </a>
            @endif
        </div>

        <button type="submit" class="auth-submit">Access Workspace</button>
    </form>

    @if (Route::has('register'))
        <p class="auth-footnote">
            Need an account?
            <a href="{{ route('register') }}" class="auth-link">Create one now</a>
        </p>
    @endif
</x-guest-layout>
