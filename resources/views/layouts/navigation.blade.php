<nav x-data="{ open: false }" class="border-b border-slate-200/70 bg-white/72 backdrop-blur-xl shadow-[0_22px_48px_-42px_rgba(15,23,42,0.55)]">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center gap-8">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-2xl border border-white/80 bg-white/90 p-2.5 shadow-[0_18px_36px_-28px_rgba(37,99,235,0.65)] transition hover:-translate-y-px hover:shadow-[0_22px_42px_-28px_rgba(37,99,235,0.78)]">
                        <x-application-logo class="block h-8 w-auto fill-current text-slate-900" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden items-center gap-3 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    @can('viewAny', App\Models\ReconciliationQueue::class)
                    <x-nav-link :href="route('reconciliation.dashboard')" :active="request()->routeIs('reconciliation.*')">
                        {{ __('Reconciliation') }}
                    </x-nav-link>
                    @endcan
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 rounded-full border border-slate-200/80 bg-white/88 px-4 py-2 text-sm font-semibold leading-4 text-slate-600 shadow-[0_16px_34px_-28px_rgba(15,23,42,0.75)] transition duration-150 ease-in-out hover:-translate-y-px hover:text-slate-900 hover:shadow-[0_20px_36px_-26px_rgba(37,99,235,0.35)] focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center rounded-xl border border-slate-200/80 bg-white/88 p-2 text-slate-400 shadow-[0_16px_30px_-28px_rgba(15,23,42,0.75)] transition duration-150 ease-in-out hover:text-slate-600 hover:shadow-[0_18px_32px_-28px_rgba(37,99,235,0.3)] focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="space-y-1 px-2 pb-3 pt-2">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            @can('viewAny', App\Models\ReconciliationQueue::class)
            <x-responsive-nav-link :href="route('reconciliation.dashboard')" :active="request()->routeIs('reconciliation.*')">
                {{ __('Reconciliation') }}
            </x-responsive-nav-link>
            @endcan
        </div>

        <!-- Responsive Settings Options -->
        <div class="border-t border-slate-200/70 pb-2 pt-4">
            <div class="px-4">
                <div class="font-semibold text-base text-slate-900">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-slate-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
