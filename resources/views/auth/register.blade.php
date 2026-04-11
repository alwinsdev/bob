<x-guest-layout>
    <h1 class="auth-title">Create Account</h1>
    <p class="auth-subtitle">Set up your account to access reconciliation workflows, governed reporting, and secure collaboration.</p>

    <div class="auth-pill-row" aria-hidden="true">
        <span class="auth-pill">Fast Onboarding</span>
        <span class="auth-pill">Role Scoped Access</span>
        <span class="auth-pill">Compliance Ready</span>
    </div>

    <form method="POST" action="{{ route('register') }}" class="auth-form" novalidate>
        @csrf

        <div class="auth-field-grid">
            <div class="auth-field">
                <label for="name" class="auth-label">Full Name</label>
                <div class="auth-input-wrap">
                    <span class="auth-input-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a7.5 7.5 0 0115 0"/>
                        </svg>
                    </span>
                    <input id="name"
                           class="auth-input auth-input-has-icon"
                           type="text"
                           name="name"
                           value="{{ old('name') }}"
                           placeholder="Jane Doe"
                           required
                           autofocus
                           autocomplete="name">
                </div>
                @error('name')
                    <p class="auth-error">{{ $message }}</p>
                @enderror
            </div>

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
                              inputmode="email"
                              autocapitalize="off"
                              autocorrect="off"
                           autocomplete="username">
                </div>
                @error('email')
                    <p class="auth-error">{{ $message }}</p>
                @enderror
            </div>
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
                       placeholder="Create a strong password"
                       required
                       autocomplete="new-password">
            </div>
            <p class="auth-hint">Use at least 8 characters with a mix of letters, numbers, and symbols.</p>
            @error('password')
                <p class="auth-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-field">
            <label for="password_confirmation" class="auth-label">Confirm Password</label>
            <div class="auth-input-wrap">
                <span class="auth-input-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l2.25 2.25L15 11.25m1.5-.75V7.875a4.5 4.5 0 10-9 0V10.5m-.75 9h10.5A2.25 2.25 0 0019.5 17.25v-4.5a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 12.75v4.5A2.25 2.25 0 006.75 19.5z"/>
                    </svg>
                </span>
                <input id="password_confirmation"
                       class="auth-input auth-input-has-icon"
                       type="password"
                       name="password_confirmation"
                       placeholder="Re-enter your password"
                       required
                       autocomplete="new-password">
            </div>
            @error('password_confirmation')
                <p class="auth-error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="auth-submit">Create Secure Account</button>
    </form>

    <p class="auth-footnote">
        Already have an account?
        <a href="{{ route('login') }}" class="auth-link">Sign in</a>
    </p>
</x-guest-layout>
