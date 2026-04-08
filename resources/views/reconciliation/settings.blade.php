<x-reconciliation-layout>
    <x-slot name="pageTitle">Settings</x-slot>
    <x-slot name="pageSubtitle">Manage your account, security, and system preferences</x-slot>

    <div x-data="settingsPage()" class="space-y-6 max-w-4xl">

        {{-- ── Success Toast ── --}}
        @if (session('status'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-2"
                 class="bob-glass-panel p-4 flex items-center gap-3" style="border-color: rgba(16,185,129,0.3);">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0" style="background: rgba(16,185,129,0.15);">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                </div>
                <span class="text-sm font-semibold text-emerald-300">{{ session('status') === 'profile-updated' ? 'Profile updated successfully.' : (session('status') === 'password-updated' ? 'Password updated successfully.' : session('status')) }}</span>
            </div>
        @endif

        {{-- ── Tab Navigation ── --}}
        <div class="bob-glass-panel p-1.5">
            <div class="flex items-center gap-1">
                <button @click="activeTab = 'profile'" class="bob-settings-tab" :class="activeTab === 'profile' && 'active'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                    Profile
                </button>
                <button @click="activeTab = 'security'" class="bob-settings-tab" :class="activeTab === 'security' && 'active'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                    Security
                </button>
                <button @click="activeTab = 'system'" class="bob-settings-tab" :class="activeTab === 'system' && 'active'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    System
                </button>
                <button @click="activeTab = 'about'" class="bob-settings-tab" :class="activeTab === 'about' && 'active'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
                    About
                </button>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════ --}}
        {{-- TAB: Profile                                --}}
        {{-- ═══════════════════════════════════════════ --}}
        <div x-show="activeTab === 'profile'" x-cloak class="space-y-6">

            <div class="bob-glass-panel overflow-hidden">
                <div class="bob-grid-toolbar px-6 py-4 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style="background: linear-gradient(140deg, rgba(99,102,241,0.22), rgba(168,85,247,0.12)); border: 1px solid rgba(168,85,247,0.35);">
                        <svg class="w-4 h-4 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold" style="color: var(--bob-text-primary)">Profile Information</h3>
                        <p class="text-[11px] font-medium" style="color: var(--bob-text-muted)">Update your display name and email address</p>
                    </div>
                </div>
                <div class="px-6 pb-6 pt-2">
                    <form method="post" action="{{ route('profile.update') }}" class="space-y-5">
                        @csrf
                        @method('patch')

                        <div class="flex items-center gap-4 mb-2">
                            <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-xl font-bold shadow-lg" style="background: linear-gradient(135deg, #6366f1, #a855f7); color: white; border: 2px solid rgba(255,255,255,0.1);">
                                {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                            </div>
                            <div>
                                <p class="text-sm font-bold" style="color: var(--bob-text-primary)">{{ Auth::user()->name }}</p>
                                <p class="text-xs" style="color: var(--bob-text-muted)">{{ Auth::user()->email }}</p>
                                <p class="text-[10px] text-indigo-500 font-semibold uppercase tracking-wider mt-0.5">{{ Auth::user()->roles->pluck('name')->first() ?? 'User' }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label for="settings_name" class="block text-xs font-semibold mb-1.5" style="color: var(--bob-text-muted)">Full Name</label>
                                <input id="settings_name" name="name" type="text" value="{{ old('name', Auth::user()->name) }}" required autocomplete="name" class="bob-form-input" />
                                @error('name')
                                    <p class="mt-1.5 text-xs text-rose-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="settings_email" class="block text-xs font-semibold mb-1.5" style="color: var(--bob-text-muted)">Email Address</label>
                                <input id="settings_email" name="email" type="email" value="{{ old('email', Auth::user()->email) }}" required autocomplete="username" class="bob-form-input" />
                                @error('email')
                                    <p class="mt-1.5 text-xs text-rose-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        @if (Auth::user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! Auth::user()->hasVerifiedEmail())
                            <div class="bob-glass-panel p-3 flex items-center gap-3" style="border-color: rgba(245,158,11,0.3);">
                                <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                                <p class="text-xs text-amber-300 font-medium">Your email address is unverified.
                                    <form id="send-verification" method="post" action="{{ route('verification.send') }}" class="inline">@csrf
                                        <button form="send-verification" class="underline text-amber-200 hover:text-white font-semibold transition">Resend verification</button>
                                    </form>
                                </p>
                            </div>
                        @endif

                        <div class="flex justify-end pt-2">
                            <button type="submit" class="bob-btn-primary">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Account Details --}}
            <div class="bob-glass-panel overflow-hidden">
                <div class="bob-grid-toolbar px-6 py-4 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style="background: linear-gradient(140deg, rgba(16,185,129,0.18), rgba(5,150,105,0.12)); border: 1px solid rgba(16,185,129,0.35);">
                        <svg class="w-4 h-4 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold" style="color: var(--bob-text-primary)">Account Details</h3>
                        <p class="text-[11px] font-medium" style="color: var(--bob-text-muted)">Session and system information</p>
                    </div>
                </div>
                <div class="px-6 pb-6 pt-2">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bob-info-card">
                            <span class="bob-info-label">Account Created</span>
                            <span class="bob-info-value">{{ Auth::user()->created_at->format('M d, Y') }}</span>
                        </div>
                        <div class="bob-info-card">
                            <span class="bob-info-label">Role</span>
                            <span class="bob-info-value">
                                <span class="bob-status-pill bob-status-resolved" style="font-size:11px;"><span class="bob-status-dot"></span>{{ Auth::user()->roles->pluck('name')->first() ?? 'User' }}</span>
                            </span>
                        </div>
                        <div class="bob-info-card">
                            <span class="bob-info-label">Email Status</span>
                            <span class="bob-info-value">
                                @if(Auth::user()->hasVerifiedEmail())
                                    <span class="bob-status-pill bob-status-matched" style="font-size:11px;"><span class="bob-status-dot"></span>Verified</span>
                                @else
                                    <span class="bob-status-pill bob-status-pending" style="font-size:11px;"><span class="bob-status-dot"></span>Unverified</span>
                                @endif
                            </span>
                        </div>
                        <div class="bob-info-card">
                            <span class="bob-info-label">Current Session IP</span>
                            <span class="bob-info-value font-mono text-xs" style="color: var(--bob-text-muted)">{{ request()->ip() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════ --}}
        {{-- TAB: Security                               --}}
        {{-- ═══════════════════════════════════════════ --}}
        <div x-show="activeTab === 'security'" x-cloak class="space-y-6">

            <div class="bob-glass-panel overflow-hidden">
                <div class="bob-grid-toolbar px-6 py-4 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style="background: linear-gradient(140deg, rgba(245,158,11,0.18), rgba(234,88,12,0.12)); border: 1px solid rgba(245,158,11,0.35);">
                        <svg class="w-4 h-4 text-amber-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold" style="color: var(--bob-text-primary)">Update Password</h3>
                        <p class="text-[11px] font-medium" style="color: var(--bob-text-muted)">Use a strong, unique password to protect your account</p>
                    </div>
                </div>
                <div class="px-6 pb-6 pt-2">
                    <form method="post" action="{{ route('password.update') }}" class="space-y-5">
                        @csrf
                        @method('put')
                        <div>
                            <label for="update_password_current_password" class="block text-xs font-semibold mb-1.5" style="color: var(--bob-text-muted)">Current Password</label>
                            <input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password" class="bob-form-input max-w-md" />
                            @error('current_password', 'updatePassword')
                                <p class="mt-1.5 text-xs text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label for="update_password_password" class="block text-xs font-semibold mb-1.5" style="color: var(--bob-text-muted)">New Password</label>
                                <input id="update_password_password" name="password" type="password" autocomplete="new-password" class="bob-form-input" />
                                @error('password', 'updatePassword')
                                    <p class="mt-1.5 text-xs text-rose-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="update_password_password_confirmation" class="block text-xs font-semibold mb-1.5" style="color: var(--bob-text-muted)">Confirm New Password</label>
                                <input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="bob-form-input" />
                                @error('password_confirmation', 'updatePassword')
                                    <p class="mt-1.5 text-xs text-rose-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="flex justify-end pt-2">
                            <button type="submit" class="bob-btn-primary">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                                Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Danger Zone --}}
            <div class="bob-glass-panel overflow-hidden" style="border-color: rgba(225,29,72,0.2);">
                <div class="bob-grid-toolbar px-6 py-4 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style="background: rgba(225,29,72,0.12); border: 1px solid rgba(225,29,72,0.35);">
                        <svg class="w-4 h-4 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-rose-300">Danger Zone</h3>
                        <p class="text-[11px] text-slate-400 font-medium">Irreversible actions — proceed with caution</p>
                    </div>
                </div>
                <div class="px-6 pb-6 pt-2">
                    <p class="text-xs mb-4 leading-relaxed" style="color: var(--bob-text-muted)">Once your account is deleted, all associated data will be permanently removed. This action cannot be undone.</p>
                    <button @click="showDeleteModal = true" type="button"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-rose-300 transition-all duration-200 cursor-pointer"
                            style="background: rgba(225,29,72,0.1); border: 1px solid rgba(225,29,72,0.25);"
                            onmouseover="this.style.background='rgba(225,29,72,0.2)'" onmouseout="this.style.background='rgba(225,29,72,0.1)'">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                        Delete My Account
                    </button>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════ --}}
        {{-- TAB: System                                 --}}
        {{-- ═══════════════════════════════════════════ --}}
        <div x-show="activeTab === 'system'" x-cloak class="space-y-6">

            <div class="bob-glass-panel p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold" style="color: var(--bob-text-secondary)">System Preferences</p>
                    <p x-show="!isSaving && !isDirty && !saveError" class="text-[11px] mt-0.5" style="color: var(--bob-text-faint)" x-text="saveSuccess || 'All changes saved'"></p>
                    <p x-show="isSaving" class="text-[11px] mt-0.5 text-indigo-300">Saving your preferences...</p>
                    <p x-show="isDirty && !isSaving" class="text-[11px] mt-0.5 text-amber-300">You have unsaved preference changes.</p>
                    <p x-show="saveError" class="text-[11px] mt-0.5 text-rose-300" x-text="saveError"></p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="resetSystemPreferences"
                            :disabled="isSaving || !isDirty"
                            class="px-3.5 py-2 rounded-xl text-xs font-semibold transition disabled:opacity-50 disabled:cursor-not-allowed"
                            style="background: var(--bob-bg-input); border: 1px solid var(--bob-border-light); color: var(--bob-text-muted);">
                        Reset
                    </button>
                    <button type="button" @click="saveSystemPreferences"
                            :disabled="isSaving || !isDirty"
                            class="bob-btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-text="isSaving ? 'Saving...' : 'Save Preferences'"></span>
                    </button>
                </div>
            </div>

            {{-- Appearance --}}
            <div class="bob-glass-panel overflow-hidden">
                <div class="bob-grid-toolbar px-6 py-4 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style="background: linear-gradient(140deg, rgba(99,102,241,0.22), rgba(168,85,247,0.12)); border: 1px solid rgba(168,85,247,0.35);">
                        <svg class="w-4 h-4 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold" style="color: var(--bob-text-primary)">Appearance</h3>
                        <p class="text-[11px] font-medium" style="color: var(--bob-text-muted)">Customize how the interface looks</p>
                    </div>
                </div>
                <div class="px-6 pb-6 pt-4">
                    <div class="space-y-5">
                        {{-- Theme --}}
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold" style="color: var(--bob-text-secondary)">Interface Theme</p>
                                <p class="text-[11px] mt-0.5" style="color: var(--bob-text-faint)">Choose between dark and light mode</p>
                            </div>
                            <div class="flex items-center gap-1 p-1 rounded-xl" style="background: var(--bob-bg-input); border: 1px solid var(--bob-border-light);">
                                <button @click="setTheme('dark')" class="bob-settings-tab-sm" :class="theme === 'dark' && 'active'">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
                                    Dark
                                </button>
                                <button @click="setTheme('light')" class="bob-settings-tab-sm" :class="theme === 'light' && 'active'">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
                                    Light
                                </button>
                            </div>
                        </div>

                        <div style="border-top: 1px solid rgba(255,255,255,0.04);"></div>

                        {{-- Grid Density --}}
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold" style="color: var(--bob-text-secondary)">Grid Row Density</p>
                                <p class="text-[11px] mt-0.5" style="color: var(--bob-text-faint)">Adjust row height in data grids</p>
                            </div>
                            <div class="flex items-center gap-1 p-1 rounded-xl" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);">
                                <button @click="gridDensity = 'compact'" class="bob-settings-tab-sm" :class="gridDensity === 'compact' && 'active'">Compact</button>
                                <button @click="gridDensity = 'normal'" class="bob-settings-tab-sm" :class="gridDensity === 'normal' && 'active'">Normal</button>
                                <button @click="gridDensity = 'comfortable'" class="bob-settings-tab-sm" :class="gridDensity === 'comfortable' && 'active'">Comfortable</button>
                            </div>
                        </div>

                        <div style="border-top: 1px solid rgba(255,255,255,0.04);"></div>

                        {{-- Sidebar Compact --}}
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold" style="color: var(--bob-text-secondary)">Compact Sidebar</p>
                                <p class="text-[11px] mt-0.5" style="color: var(--bob-text-faint)">Collapse the sidebar to show icons only</p>
                            </div>
                            <button @click="compactSidebar = !compactSidebar"
                                class="relative w-11 h-6 rounded-full transition-all duration-200 cursor-pointer"
                                :style="compactSidebar ? 'background: linear-gradient(135deg, #6366f1, #8b5cf6)' : 'background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.08)'">
                                <span class="absolute top-0.5 w-5 h-5 rounded-full bg-white shadow-md transition-all duration-200"
                                      :class="compactSidebar ? 'left-[22px]' : 'left-0.5'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Notifications --}}
            <div class="bob-glass-panel overflow-hidden">
                <div class="bob-grid-toolbar px-6 py-4 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style="background: linear-gradient(140deg, rgba(245,158,11,0.18), rgba(234,88,12,0.12)); border: 1px solid rgba(245,158,11,0.35);">
                        <svg class="w-4 h-4 text-amber-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold" style="color: var(--bob-text-primary)">Notifications</h3>
                        <p class="text-[11px] font-medium" style="color: var(--bob-text-muted)">Control how you receive alerts</p>
                    </div>
                </div>
                <div class="px-6 pb-6 pt-4 space-y-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold" style="color: var(--bob-text-secondary)">Email Notifications</p>
                            <p class="text-[11px] mt-0.5" style="color: var(--bob-text-faint)">Receive email when a batch completes or fails</p>
                        </div>
                        <button @click="emailNotifications = !emailNotifications"
                            class="relative w-11 h-6 rounded-full transition-all duration-200 cursor-pointer"
                            :style="emailNotifications ? 'background: linear-gradient(135deg, #6366f1, #8b5cf6)' : 'background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.08)'">
                            <span class="absolute top-0.5 w-5 h-5 rounded-full bg-white shadow-md transition-all duration-200"
                                  :class="emailNotifications ? 'left-[22px]' : 'left-0.5'"></span>
                        </button>
                    </div>
                    <div style="border-top: 1px solid rgba(255,255,255,0.04);"></div>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold" style="color: var(--bob-text-secondary)">Auto-Refresh Dashboard</p>
                            <p class="text-[11px] mt-0.5" style="color: var(--bob-text-faint)">Automatically poll for new data every 30 seconds</p>
                        </div>
                        <button @click="autoRefresh = !autoRefresh"
                            class="relative w-11 h-6 rounded-full transition-all duration-200 cursor-pointer"
                            :style="autoRefresh ? 'background: linear-gradient(135deg, #6366f1, #8b5cf6)' : 'background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.08)'">
                            <span class="absolute top-0.5 w-5 h-5 rounded-full bg-white shadow-md transition-all duration-200"
                                  :class="autoRefresh ? 'left-[22px]' : 'left-0.5'"></span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Data & Export --}}
            <div class="bob-glass-panel overflow-hidden">
                <div class="bob-grid-toolbar px-6 py-4 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style="background: linear-gradient(140deg, rgba(16,185,129,0.18), rgba(5,150,105,0.12)); border: 1px solid rgba(16,185,129,0.35);">
                        <svg class="w-4 h-4 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold" style="color: var(--bob-text-primary)">Data & Export</h3>
                        <p class="text-[11px] font-medium" style="color: var(--bob-text-muted)">Configure data processing defaults</p>
                    </div>
                </div>
                <div class="px-6 pb-6 pt-4 space-y-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold" style="color: var(--bob-text-secondary)">Default Export Format</p>
                            <p class="text-[11px] mt-0.5" style="color: var(--bob-text-faint)">File format for exported reports</p>
                        </div>
                        <div class="flex items-center gap-1 p-1 rounded-xl" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);">
                            <button @click="exportFormat = 'xlsx'" class="bob-settings-tab-sm" :class="exportFormat === 'xlsx' && 'active'">XLSX</button>
                            <button @click="exportFormat = 'csv'" class="bob-settings-tab-sm" :class="exportFormat === 'csv' && 'active'">CSV</button>
                            <button @click="exportFormat = 'pdf'" class="bob-settings-tab-sm" :class="exportFormat === 'pdf' && 'active'">PDF</button>
                        </div>
                    </div>
                    <div style="border-top: 1px solid rgba(255,255,255,0.04);"></div>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold" style="color: var(--bob-text-secondary)">Records Per Page</p>
                            <p class="text-[11px] mt-0.5" style="color: var(--bob-text-faint)">Number of rows shown in data grids</p>
                        </div>
                        <div class="flex items-center gap-1 p-1 rounded-xl" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);">
                            <button @click="pageSize = 25" class="bob-settings-tab-sm" :class="pageSize === 25 && 'active'">25</button>
                            <button @click="pageSize = 50" class="bob-settings-tab-sm" :class="pageSize === 50 && 'active'">50</button>
                            <button @click="pageSize = 100" class="bob-settings-tab-sm" :class="pageSize === 100 && 'active'">100</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════ --}}
        {{-- TAB: About                                  --}}
        {{-- ═══════════════════════════════════════════ --}}
        <div x-show="activeTab === 'about'" x-cloak class="space-y-6">
            <div class="bob-glass-panel overflow-hidden">
                <div class="bob-grid-toolbar px-6 py-4 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style="background: linear-gradient(140deg, rgba(99,102,241,0.22), rgba(168,85,247,0.12)); border: 1px solid rgba(168,85,247,0.35);">
                        <svg class="w-4 h-4 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold" style="color: var(--bob-text-primary)">About BOB System</h3>
                        <p class="text-[11px] font-medium" style="color: var(--bob-text-muted)">System version and environment information</p>
                    </div>
                </div>
                <div class="px-6 pb-6 pt-2">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bob-info-card">
                            <span class="bob-info-label">Application</span>
                            <span class="bob-info-value">BOB Reconciliation System</span>
                        </div>
                        <div class="bob-info-card">
                            <span class="bob-info-label">Version</span>
                            <span class="bob-info-value font-mono">v1.0.0</span>
                        </div>
                        <div class="bob-info-card">
                            <span class="bob-info-label">Framework</span>
                            <span class="bob-info-value font-mono">Laravel {{ app()->version() }}</span>
                        </div>
                        <div class="bob-info-card">
                            <span class="bob-info-label">PHP Version</span>
                            <span class="bob-info-value font-mono">{{ PHP_VERSION }}</span>
                        </div>
                        <div class="bob-info-card">
                            <span class="bob-info-label">Environment</span>
                            <span class="bob-info-value">
                                <span class="bob-status-pill bob-status-{{ app()->environment('production') ? 'flagged' : 'matched' }}" style="font-size:11px;">
                                    <span class="bob-status-dot"></span>{{ ucfirst(app()->environment()) }}
                                </span>
                            </span>
                        </div>
                        <div class="bob-info-card">
                            <span class="bob-info-label">Server Time</span>
                            <span class="bob-info-value font-mono text-xs">{{ now()->format('Y-m-d H:i:s T') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════ --}}
        {{-- Delete Account Confirmation Modal           --}}
        {{-- ═══════════════════════════════════════════ --}}
        <template x-if="showDeleteModal">
            <div class="fixed inset-0 z-[60] flex items-center justify-center" @keydown.escape.window="showDeleteModal = false">
                <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showDeleteModal = false"></div>
                <div class="relative bob-glass-panel max-w-md w-full mx-4 p-6" style="border-color: rgba(225,29,72,0.3);">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: rgba(225,29,72,0.15); border: 1px solid rgba(225,29,72,0.3);">
                            <svg class="w-5 h-5 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold" style="color: var(--bob-text-primary)">Confirm Account Deletion</h3>
                            <p class="text-[11px]" style="color: var(--bob-text-muted)">This action is permanent and cannot be reversed.</p>
                        </div>
                    </div>
                    <p class="text-xs mb-4 leading-relaxed" style="color: var(--bob-text-muted)">Enter your password to confirm you want to permanently delete your account and all data.</p>
                    <form method="post" action="{{ route('profile.destroy') }}">
                        @csrf
                        @method('delete')
                        <div class="mb-4">
                            <label for="delete_password" class="block text-xs font-semibold mb-1.5" style="color: var(--bob-text-muted)">Password</label>
                            <input id="delete_password" name="password" type="password" placeholder="Enter your password" required class="bob-form-input" />
                            @error('password', 'userDeletion')
                                <p class="mt-1.5 text-xs text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="showDeleteModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 hover:text-white transition-colors" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08);">Cancel</button>
                            <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold text-white transition-all" style="background: rgba(225,29,72,0.8); border: 1px solid rgba(225,29,72,0.5);">Permanently Delete</button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

    </div>

    @push('scripts')
    <script>
        window.settingsPage = function() {
            // Read tab from URL query parameter
            const params = new URLSearchParams(window.location.search);
            const urlTab = params.get('tab');
            const validTabs = ['profile', 'security', 'system', 'about'];
            const initialTab = validTabs.includes(urlTab) ? urlTab : 'system';
            const serverPreferences = @js($preferences ?? []);

            const normalized = {
                theme: ['dark', 'light'].includes(serverPreferences.theme) ? serverPreferences.theme : 'dark',
                gridDensity: ['compact', 'normal', 'comfortable'].includes(serverPreferences.grid_density) ? serverPreferences.grid_density : 'normal',
                compactSidebar: Boolean(serverPreferences.compact_sidebar),
                emailNotifications: Boolean(serverPreferences.email_notifications),
                autoRefresh: Boolean(serverPreferences.auto_refresh),
                exportFormat: ['xlsx', 'csv', 'pdf'].includes(serverPreferences.export_format) ? serverPreferences.export_format : 'xlsx',
                pageSize: [25, 50, 100].includes(Number(serverPreferences.page_size)) ? Number(serverPreferences.page_size) : 50,
            };

            return {
                activeTab: initialTab,
                showDeleteModal: {{ $errors->userDeletion->isNotEmpty() ? 'true' : 'false' }},
                // System preferences (server-backed)
                theme: normalized.theme,
                gridDensity: normalized.gridDensity,
                compactSidebar: normalized.compactSidebar,
                emailNotifications: normalized.emailNotifications,
                autoRefresh: normalized.autoRefresh,
                exportFormat: normalized.exportFormat,
                pageSize: normalized.pageSize,
                baselinePreferences: {
                    ...normalized,
                },
                isDirty: false,
                isSaving: false,
                saveError: '',
                saveSuccess: '',

                setTheme(val) {
                    this.theme = val;
                },

                currentPreferencesPayload() {
                    return {
                        theme: this.theme,
                        grid_density: this.gridDensity,
                        compact_sidebar: this.compactSidebar,
                        email_notifications: this.emailNotifications,
                        auto_refresh: this.autoRefresh,
                        export_format: this.exportFormat,
                        page_size: this.pageSize,
                    };
                },

                baselinePayload() {
                    return {
                        theme: this.baselinePreferences.theme,
                        grid_density: this.baselinePreferences.gridDensity,
                        compact_sidebar: this.baselinePreferences.compactSidebar,
                        email_notifications: this.baselinePreferences.emailNotifications,
                        auto_refresh: this.baselinePreferences.autoRefresh,
                        export_format: this.baselinePreferences.exportFormat,
                        page_size: this.baselinePreferences.pageSize,
                    };
                },

                markDirty() {
                    const current = this.currentPreferencesPayload();
                    const baseline = this.baselinePayload();

                    this.isDirty = JSON.stringify(current) !== JSON.stringify(baseline);

                    if (this.isDirty) {
                        this.saveSuccess = '';
                    }

                    if (this.saveError) {
                        this.saveError = '';
                    }
                },

                persistPreferencesLocally() {
                    localStorage.setItem('bob_theme', this.theme);
                    localStorage.setItem('bob_grid_density', this.gridDensity);
                    localStorage.setItem('bob_compact_sidebar', this.compactSidebar);
                    localStorage.setItem('bob_email_notifications', this.emailNotifications);
                    localStorage.setItem('bob_auto_refresh', this.autoRefresh);
                    localStorage.setItem('bob_export_format', this.exportFormat);
                    localStorage.setItem('bob_page_size', this.pageSize);
                },

                hydrateFromSavedPreferences(preferences) {
                    this.baselinePreferences = {
                        theme: preferences.theme,
                        gridDensity: preferences.grid_density,
                        compactSidebar: Boolean(preferences.compact_sidebar),
                        emailNotifications: Boolean(preferences.email_notifications),
                        autoRefresh: Boolean(preferences.auto_refresh),
                        exportFormat: preferences.export_format,
                        pageSize: Number(preferences.page_size),
                    };

                    this.theme = this.baselinePreferences.theme;
                    this.gridDensity = this.baselinePreferences.gridDensity;
                    this.compactSidebar = this.baselinePreferences.compactSidebar;
                    this.emailNotifications = this.baselinePreferences.emailNotifications;
                    this.autoRefresh = this.baselinePreferences.autoRefresh;
                    this.exportFormat = this.baselinePreferences.exportFormat;
                    this.pageSize = this.baselinePreferences.pageSize;
                    this.isDirty = false;
                    this.persistPreferencesLocally();
                    window.bobSetTheme(this.theme);
                    if (window.bobSetCompactSidebar) {
                        window.bobSetCompactSidebar(this.compactSidebar);
                    }
                },

                extractErrorMessage(responseData) {
                    if (responseData && typeof responseData === 'object' && responseData.errors) {
                        for (const key in responseData.errors) {
                            if (Object.hasOwn(responseData.errors, key) && Array.isArray(responseData.errors[key]) && responseData.errors[key].length > 0) {
                                return responseData.errors[key][0];
                            }
                        }
                    }

                    if (responseData && typeof responseData.message === 'string' && responseData.message.length > 0) {
                        return responseData.message;
                    }

                    return 'Unable to save settings right now.';
                },

                async saveSystemPreferences() {
                    if (this.isSaving || !this.isDirty) {
                        return;
                    }

                    this.isSaving = true;
                    this.saveError = '';

                    try {
                        const response = await fetch('{{ route('reconciliation.settings.update') }}', {
                            method: 'PUT',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            },
                            body: JSON.stringify(this.currentPreferencesPayload()),
                        });

                        const responseData = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            throw new Error(this.extractErrorMessage(responseData));
                        }

                        this.hydrateFromSavedPreferences(responseData.preferences || this.currentPreferencesPayload());
                        this.saveSuccess = responseData.message || 'Settings saved successfully.';
                    } catch (error) {
                        this.saveError = error?.message || 'Unable to save settings right now.';
                    } finally {
                        this.isSaving = false;
                    }
                },

                resetSystemPreferences() {
                    if (this.isSaving) {
                        return;
                    }

                    this.theme = this.baselinePreferences.theme;
                    this.gridDensity = this.baselinePreferences.gridDensity;
                    this.compactSidebar = this.baselinePreferences.compactSidebar;
                    this.emailNotifications = this.baselinePreferences.emailNotifications;
                    this.autoRefresh = this.baselinePreferences.autoRefresh;
                    this.exportFormat = this.baselinePreferences.exportFormat;
                    this.pageSize = this.baselinePreferences.pageSize;

                    this.persistPreferencesLocally();
                    window.bobSetTheme(this.theme);
                    if (window.bobSetCompactSidebar) {
                        window.bobSetCompactSidebar(this.compactSidebar);
                    }

                    this.isDirty = false;
                    this.saveError = '';
                    this.saveSuccess = '';
                },

                init() {
                    // Ensure the layout theme reflects server preference on initial load.
                    window.bobSetTheme(this.theme);
                    if (window.bobSetCompactSidebar) {
                        window.bobSetCompactSidebar(this.compactSidebar);
                    }
                    this.persistPreferencesLocally();

                    this.$watch('theme', () => {
                        window.bobSetTheme(this.theme);
                        this.persistPreferencesLocally();
                        this.markDirty();
                    });
                    this.$watch('gridDensity', () => {
                        this.persistPreferencesLocally();
                        this.markDirty();
                    });
                    this.$watch('compactSidebar', () => {
                        if (window.bobSetCompactSidebar) {
                            window.bobSetCompactSidebar(this.compactSidebar);
                        }
                        this.persistPreferencesLocally();
                        this.markDirty();
                    });
                    this.$watch('emailNotifications', () => {
                        this.persistPreferencesLocally();
                        this.markDirty();
                    });
                    this.$watch('autoRefresh', () => {
                        this.persistPreferencesLocally();
                        this.markDirty();
                    });
                    this.$watch('exportFormat', () => {
                        this.persistPreferencesLocally();
                        this.markDirty();
                    });
                    this.$watch('pageSize', () => {
                        this.persistPreferencesLocally();
                        this.markDirty();
                    });
                }
            };
        };
    </script>
    @endpush

</x-reconciliation-layout>
