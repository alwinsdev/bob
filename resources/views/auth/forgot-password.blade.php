<x-guest-layout>
    <h1 class="auth-title">Reset Password</h1>
    <p class="auth-subtitle">Enter your work email and we will send a secure reset link to restore account access.</p>

    @if (session('status'))
        <div class="auth-status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="auth-form" novalidate>
        @csrf

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

        <button type="submit" class="auth-submit">Send Reset Link</button>
    </form>

    <p class="auth-footnote">
        Remembered your password?
        <a href="{{ route('login') }}" class="auth-link">Back to sign in</a>
    </p>
</x-guest-layout>
