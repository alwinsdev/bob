<x-reconciliation-layout>
    <x-slot name="pageTitle">Reconciliation Hub</x-slot>
    <x-slot name="pageSubtitle">Monitor, resolve & reconcile carrier vs. IMS data exceptions</x-slot>

    <x-slot name="headerActions">
        @can('reconciliation.etl.run')
            <a href="{{ route('reconciliation.upload.index') }}" class="bob-btn-primary group">
                <svg class="w-4 h-4 transition-transform duration-300 group-hover:-translate-y-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                </svg>
                Import Feeds
            </a>
        @endcan
    </x-slot>

    <div x-data="reconciliationHub()">
        <div class="space-y-6">

            {{-- ── Premium Metric Cards ── --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

                {{-- Pending --}}
                <div class="bob-metric-card bob-metric-amber group" @click="updateFilter('pending')">
                    <div class="absolute top-0 right-0 w-24 h-24 rounded-full opacity-10 -translate-y-8 translate-x-8 transition-transform duration-500 group-hover:scale-150" style="background: radial-gradient(circle, #f59e0b, transparent 70%);"></div>
                    <div class="flex items-center gap-4 mb-3 relative z-10">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center transition-transform duration-300 group-hover:scale-110" style="background: rgba(245,158,11,0.15);">
                            <svg class="w-6 h-6 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-widest text-amber-400/80">Pending Actions</div>
                            <div class="text-3xl font-extrabold tracking-tight" style="color: var(--bob-text-primary)">{{ number_format($metrics['total_pending']) }}</div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between relative z-10">
                        <div class="text-xs font-medium" style="color: var(--bob-text-muted)">Awaiting manual review</div>
                        <div class="w-16 h-1 rounded-full overflow-hidden" style="background: var(--bob-border-medium)">
                            <div class="h-full rounded-full bg-amber-400/60 transition-all duration-700" style="width: {{ $metrics['total_pending'] > 0 ? min(100, ($metrics['total_pending'] / max(1, $metrics['total_pending'] + $metrics['total_resolved'])) * 100) : 0 }}%"></div>
                        </div>
                    </div>
                </div>

                {{-- Flagged --}}
                <div class="bob-metric-card bob-metric-rose group" @click="updateFilter('flagged')">
                    <div class="absolute top-0 right-0 w-24 h-24 rounded-full opacity-10 -translate-y-8 translate-x-8 transition-transform duration-500 group-hover:scale-150" style="background: radial-gradient(circle, #e11d48, transparent 70%);"></div>
                    <div class="flex items-center gap-4 mb-3 relative z-10">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center transition-transform duration-300 group-hover:scale-110" style="background: rgba(225,29,72,0.15);">
                            <svg class="w-6 h-6 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0l2.77-.693a9 9 0 016.208.682l.108.054a9 9 0 006.086.71l3.114-.732a48.524 48.524 0 01-.005-10.499l-3.11.732a9 9 0 01-6.085-.711l-.108-.054a9 9 0 00-6.208-.682L3 4.5M3 15V4.5" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-widest text-rose-400/80">Flagged Exceptions</div>
                            <div class="text-3xl font-extrabold tracking-tight" style="color: var(--bob-text-primary)">{{ number_format($metrics['total_flagged']) }}</div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between relative z-10">
                        <div class="text-xs font-medium" style="color: var(--bob-text-muted)">Needs supervisor attention</div>
                        <div class="w-16 h-1 rounded-full overflow-hidden bg-white/5">
                            <div class="h-full rounded-full bg-rose-400/60" style="width: {{ $metrics['total_flagged'] > 0 ? min(100, ($metrics['total_flagged'] / max(1, $metrics['total_flagged'] + $metrics['total_resolved'])) * 100) : 0 }}%"></div>
                        </div>
                    </div>
                </div>

                {{-- Resolved --}}
                <div class="bob-metric-card bob-metric-emerald group" @click="updateFilter('resolved')">
                    <div class="absolute top-0 right-0 w-24 h-24 rounded-full opacity-10 -translate-y-8 translate-x-8 transition-transform duration-500 group-hover:scale-150" style="background: radial-gradient(circle, #10b981, transparent 70%);"></div>
                    <div class="flex items-center gap-4 mb-3 relative z-10">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center transition-transform duration-300 group-hover:scale-110" style="background: rgba(16,185,129,0.15);">
                            <svg class="w-6 h-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-widest text-emerald-400/80">Resolved</div>
                            <div class="text-3xl font-extrabold tracking-tight" style="color: var(--bob-text-primary)">{{ number_format($metrics['total_resolved']) }}</div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between relative z-10">
                        <div class="text-xs font-medium" style="color: var(--bob-text-muted)">Successfully aligned</div>
                        <div class="w-16 h-1 rounded-full overflow-hidden bg-white/5">
                            <div class="h-full rounded-full bg-emerald-400/60" style="width: {{ $metrics['total_resolved'] > 0 ? min(100, ($metrics['total_resolved'] / max(1, $metrics['total_pending'] + $metrics['total_resolved'])) * 100) : 0 }}%"></div>
                        </div>
                    </div>
                </div>

                {{-- Import Batches CTA --}}
                @can('reconciliation.etl.run')
                    <a href="{{ route('reconciliation.upload.index') }}" class="bob-metric-card bob-metric-indigo group cursor-pointer block">
                        <div class="absolute top-0 right-0 w-24 h-24 rounded-full opacity-10 -translate-y-8 translate-x-8 transition-transform duration-500 group-hover:scale-150" style="background: radial-gradient(circle, #6366f1, transparent 70%);"></div>
                        <div class="flex items-center justify-between mb-3 relative z-10">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center transition-transform duration-300 group-hover:scale-110" style="background: rgba(99,102,241,0.15);">
                                    <svg class="w-6 h-6 text-indigo-400 transition-transform duration-300 group-hover:-translate-y-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-[10px] font-bold uppercase tracking-widest text-indigo-400/80">Import Batches</div>
                                    <div class="text-3xl font-extrabold tracking-tight" style="color: var(--bob-text-primary)">{{ $metrics['total_batches'] }}</div>
                                </div>
                            </div>
                            <svg class="w-5 h-5 text-indigo-400/50 transition-transform duration-300 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                        </div>
                        <div class="text-xs font-medium group-hover:text-indigo-400 transition-colors relative z-10" style="color: var(--bob-text-muted)">Upload new carrier / IMS feeds →</div>
                    </a>
                @endcan
            </div>

            {{-- ── Summary Stats Strip ── --}}
            @php
                $totalAll = $metrics['total_pending'] + $metrics['total_flagged'] + $metrics['total_resolved'];
            @endphp
            <div class="bob-glass-panel p-4">
                <div class="flex items-center justify-between gap-6">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(99,102,241,0.12);">
                            <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                        </div>
                        <span class="text-sm font-bold" style="color: var(--bob-text-primary)">{{ number_format($totalAll) }} Total Records</span>
                        <span class="text-xs font-medium" style="color: var(--bob-text-muted)">across all batches</span>
                    </div>
                    {{-- Progress Bar --}}
                    <div class="flex-1 max-w-md">
                        <div class="flex h-2 rounded-full overflow-hidden" style="background: var(--bob-border-medium)">
                            @if($totalAll > 0)
                            <div class="h-full transition-all duration-700" style="width: {{ ($metrics['total_resolved'] / $totalAll) * 100 }}%; background: linear-gradient(90deg, #10b981, #34d399);"></div>
                            <div class="h-full transition-all duration-700" style="width: {{ ($metrics['total_flagged'] / $totalAll) * 100 }}%; background: linear-gradient(90deg, #e11d48, #fb7185);"></div>
                            <div class="h-full transition-all duration-700" style="width: {{ ($metrics['total_pending'] / $totalAll) * 100 }}%; background: linear-gradient(90deg, #d97706, #fbbf24);"></div>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-4 text-[11px] font-bold">
                        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-400"></span> <span style="color: var(--bob-text-muted)">Resolved</span></span>
                        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-rose-400"></span> <span style="color: var(--bob-text-muted)">Flagged</span></span>
                        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-400"></span> <span style="color: var(--bob-text-muted)">Pending</span></span>
                    </div>
                </div>
            </div>

            {{-- ── Premium Glassmorphism Toolbar ── --}}
            <div class="bob-glass-panel p-4">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="flex items-center w-full sm:w-auto gap-3">
                        <div class="relative w-full sm:w-80">
                            <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                            <input @input.debounce.500ms="updateSearch($event.target.value)" type="text" class="bob-search-input" placeholder="Search by ID, name, carrier..." />
                        </div>
                        <div class="flex items-center gap-1.5 p-1 rounded-xl" style="background: var(--bob-bg-input); border: 1px solid var(--bob-border-light);">
                            <template x-for="opt in [{v:'all',l:'All'},{v:'pending',l:'Pending'},{v:'matched',l:'Matched'},{v:'flagged',l:'Flagged'},{v:'resolved',l:'Resolved'}]" :key="opt.v">
                                <button @click="updateFilter(opt.v)"
                                    class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all duration-200"
                                    :class="filterStatus === opt.v ? 'bg-indigo-500/20 text-indigo-600 shadow-sm' : 'hover:bg-white/5'"
                                    :style="filterStatus !== opt.v ? 'color: var(--bob-text-muted)' : ''"
                                    x-text="opt.l">
                                </button>
                            </template>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button @click="bulkResolve" x-show="selectedCount > 0" x-cloak x-transition class="bob-btn-primary">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            <span x-text="`Resolve (${selectedCount})`"></span>
                        </button>
                        @if(auth()->user()?->hasAnyRole(['admin', 'operations_manager', 'Operations Manager', 'Admin', 'Manager']))
                        <button @click="bulkPromoteToLocklist" x-show="selectedCount > 0" x-cloak x-transition
                                class="bob-btn-ghost flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg"
                                style="color: #c084fc; border: 1px solid rgba(168,85,247,0.25);">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                            <span x-text="`Lock List (${selectedCount})`"></span>
                        </button>
                        @endif
                        <button @click="refreshGrid" class="bob-btn-ghost group">
                            <svg class="w-4 h-4 transition-transform duration-300 group-hover:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" /></svg>
                            Refresh
                        </button>
                    </div>
                </div>
            </div>

            {{-- ── Premium AG Grid Container ── --}}
            <div class="bob-glass-panel bob-grid-shell overflow-hidden">
                {{-- Grid Header Bar --}}
                <div class="bob-grid-toolbar px-6 py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background: linear-gradient(140deg, rgba(99,102,241,0.22), rgba(14,165,233,0.12)); border: 1px solid rgba(129,140,248,0.35);">
                            <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0112 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0h7.5c.621 0 1.125.504 1.125 1.125M3.375 8.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m17.25-3.75h-7.5c-.621 0-1.125.504-1.125 1.125m8.625-1.125c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125M12 10.875v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125M13.125 12h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125M20.625 12c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5M12 14.625v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 14.625c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125m0 0v1.5c0 .621-.504 1.125-1.125 1.125M15 10.875c0 .621-.504 1.125-1.125 1.125M15 10.875c0 .621.504 1.125 1.125 1.125" /></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold" style="color: var(--bob-text-primary)">Exception Records</h3>
                            <p class="text-[11px] font-medium" style="color: var(--bob-text-muted)">Click "Review" to open the resolution drawer</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 text-[11px] font-semibold">
                        <span class="bob-grid-chip">
                            <span class="bob-grid-chip-dot bob-grid-chip-dot-indigo"></span>
                            <span x-text="`${selectedCount} selected`"></span>
                        </span>
                        <span class="bob-grid-chip">
                            <span class="bob-grid-chip-dot bob-grid-chip-dot-emerald"></span>
                            <span x-text="`Filter: ${filterStatus === 'all' ? 'All' : (filterStatus.charAt(0).toUpperCase() + filterStatus.slice(1))}`"></span>
                        </span>
                        <span class="bob-grid-chip bob-grid-chip-muted" x-text="`Page ${currentPage || 1}`"></span>
                        <span class="bob-grid-chip bob-grid-chip-muted">50 per page</span>
                    </div>
                </div>
                <div class="px-2 pb-2">
                    <div id="reconciliationGrid" class="ag-theme-alpine ag-theme-bob w-full" style="height: 620px;"></div>
                </div>
            </div>
        </div>

        @include('reconciliation.drawer')
    </div>

    @push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-grid.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-theme-alpine.css">
    @endpush

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.js"></script>
    <script>
        window.csrfToken = '{{ csrf_token() }}';
        window.endpoints = {
            data: '{{ route('reconciliation.data') }}',
            lock: (id) => `/reconciliation/records/${id}/lock`,
            unlock: (id) => `/reconciliation/records/${id}/unlock`,
            resolve: (id) => `/reconciliation/records/${id}/resolve`,
            flag: (id) => `/reconciliation/records/${id}/flag`,
            bulkResolve: '{{ route('reconciliation.records.bulk-resolve') }}',
            bulkPromoteToLocklist: '{{ route('reconciliation.records.bulk-promote-to-locklist') }}',
        };
        window.canBulkApprove = @json(auth()->user() ? auth()->user()->can('reconciliation.bulk_approve') : false);
        window.canEdit = @json(auth()->user() ? auth()->user()->can('reconciliation.edit') : false);
    </script>
    @endpush

    @vite(['resources/js/reconciliation.js'])
</x-reconciliation-layout>