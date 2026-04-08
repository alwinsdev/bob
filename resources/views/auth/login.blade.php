<x-guest-layout>
    <h1 class="auth-title">Welcome Back</h1>
    <p class="auth-subtitle">Sign in to continue to your reconciliation workspace and operational dashboards.</p>

    @if (session('status'))
        <div class="auth-status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="auth-form" novalidate>
        @csrf
        <input type="hidden" name="remember" value="0">

        <div class="auth-field">
            <label for="email" class="auth-label">Work Email</label>
            <input id="email"
                   class="auth-input"
                   type="email"
                   name="email"
                   value="{{ old('email') }}"
                   placeholder="you@company.com"
                   required
                   autofocus
                   autocomplete="username">
            @error('email')
                <p class="auth-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-field">
            <label for="password" class="auth-label">Password</label>
            <input id="password"
                   class="auth-input"
                   type="password"
                   name="password"
                   placeholder="Enter your password"
                   required
                   autocomplete="current-password">
            @error('password')
                <p class="auth-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-row">
            <label for="remember_me" class="auth-check">
                <input id="remember_me" type="checkbox" name="remember" value="1" @checked(old('remember'))>
                <span>Remember this device</span>
            </label>

            @if (Route::has('password.request'))
                <a class="auth-link" href="{{ route('password.request') }}">
                    Forgot password?
                </a>
            @endif
        </div>

        <button type="submit" class="auth-submit">Log In</button>
    </form>

    @if (Route::has('register'))
        <p class="auth-footnote">
            New to BOB System?
            <a href="{{ route('register') }}" class="auth-link">Create an account</a>
        </p>
    @endif
</x-guest-layout>
