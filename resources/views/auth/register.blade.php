<x-guest-layout>
    <h1 class="auth-title">Create Account</h1>
    <p class="auth-subtitle">Provision secure access for your reconciliation, reporting, and operational workflows.</p>

    <form method="POST" action="{{ route('register') }}" class="auth-form" novalidate>
        @csrf

        <div class="auth-field">
            <label for="name" class="auth-label">Full Name</label>
            <input id="name"
                   class="auth-input"
                   type="text"
                   name="name"
                   value="{{ old('name') }}"
                   placeholder="Jane Doe"
                   required
                   autofocus
                   autocomplete="name">
            @error('name')
                <p class="auth-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-field">
            <label for="email" class="auth-label">Work Email</label>
            <input id="email"
                   class="auth-input"
                   type="email"
                   name="email"
                   value="{{ old('email') }}"
                   placeholder="you@company.com"
                   required
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
                   placeholder="Create a strong password"
                   required
                   autocomplete="new-password">
            @error('password')
                <p class="auth-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-field">
            <label for="password_confirmation" class="auth-label">Confirm Password</label>
            <input id="password_confirmation"
                   class="auth-input"
                   type="password"
                   name="password_confirmation"
                   placeholder="Re-enter your password"
                   required
                   autocomplete="new-password">
            @error('password_confirmation')
                <p class="auth-error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="auth-submit">Create Account</button>
    </form>

    <p class="auth-footnote">
        Already registered?
        <a href="{{ route('login') }}" class="auth-link">Sign in</a>
    </p>
</x-guest-layout>
