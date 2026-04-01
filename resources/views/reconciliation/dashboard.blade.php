<x-reconciliation-layout>
    <x-slot name="pageTitle">Reconciliation Hub</x-slot>
    <x-slot name="pageSubtitle">Monitor, resolve & reconcile carrier vs. IMS data exceptions</x-slot>

    <div x-data="reconciliationHub()">
        <div class="space-y-6">

            {{-- ── Dark Metric Cards ── --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

                {{-- Pending --}}
                <div class="bob-metric-card bob-metric-amber">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background: rgba(245,158,11,0.15);">
                            <svg class="w-6 h-6 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-widest text-amber-400/80">Pending Actions</div>
                            <div class="text-3xl font-extrabold text-white tracking-tight">{{ number_format($metrics['total_pending']) }}</div>
                        </div>
                    </div>
                    <div class="text-xs font-medium text-slate-500">Awaiting manual review</div>
                </div>

                {{-- Flagged --}}
                <div class="bob-metric-card bob-metric-rose">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background: rgba(225,29,72,0.15);">
                            <svg class="w-6 h-6 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0l2.77-.693a9 9 0 016.208.682l.108.054a9 9 0 006.086.71l3.114-.732a48.524 48.524 0 01-.005-10.499l-3.11.732a9 9 0 01-6.085-.711l-.108-.054a9 9 0 00-6.208-.682L3 4.5M3 15V4.5" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-widest text-rose-400/80">Flagged Exceptions</div>
                            <div class="text-3xl font-extrabold text-white tracking-tight">{{ number_format($metrics['total_flagged']) }}</div>
                        </div>
                    </div>
                    <div class="text-xs font-medium text-slate-500">Needs supervisor attention</div>
                </div>

                {{-- Resolved --}}
                <div class="bob-metric-card bob-metric-emerald">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background: rgba(16,185,129,0.15);">
                            <svg class="w-6 h-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-widest text-emerald-400/80">Resolved</div>
                            <div class="text-3xl font-extrabold text-white tracking-tight">{{ number_format($metrics['total_resolved']) }}</div>
                        </div>
                    </div>
                    <div class="text-xs font-medium text-slate-500">Successfully aligned</div>
                </div>

                {{-- Import CTA --}}
                <a href="{{ route('reconciliation.upload.index') }}" class="bob-metric-card bob-metric-indigo group cursor-pointer block">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background: rgba(99,102,241,0.15);">
                                <svg class="w-6 h-6 text-indigo-400 transition-transform duration-300 group-hover:-translate-y-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-[10px] font-bold uppercase tracking-widest text-indigo-400/80">Import Batches</div>
                                <div class="text-3xl font-extrabold text-white tracking-tight">{{ $metrics['total_batches'] }}</div>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-indigo-400/50 transition-transform duration-300 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                    </div>
                    <div class="text-xs font-medium text-slate-500 group-hover:text-indigo-400 transition-colors">Upload new carrier / IMS feeds →</div>
                </a>
            </div>

            {{-- ── Toolbar ── --}}
            <div class="bob-toolbar">
                <div class="flex items-center w-full sm:w-auto gap-3">
                    <div class="relative w-full sm:w-80">
                        <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        <input @input.debounce.500ms="updateSearch($event.target.value)" type="text" class="bob-search-input" placeholder="Search by ID, name, carrier..." />
                    </div>
                    <select @change="updateFilter($event.target.value)" class="bob-select">
                        <option value="all">All Records</option>
                        <option value="pending">Pending</option>
                        <option value="matched">Matched</option>
                        <option value="flagged">Flagged</option>
                        <option value="resolved">Resolved</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button @click="bulkResolve" x-show="selectedCount > 0" x-cloak x-transition class="bob-btn-primary">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        <span x-text="`Resolve (${selectedCount})`"></span>
                    </button>
                    <button @click="refreshGrid" class="bob-btn-ghost">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" /></svg>
                        Refresh
                    </button>
                </div>
            </div>

            {{-- ── AG Grid ── --}}
            <div class="bob-glass-panel">
                <div id="reconciliationGrid" class="ag-theme-alpine ag-theme-bob w-full" style="height: 560px;"></div>
            </div>
        </div>

        @include('reconciliation.drawer')
    </div>

    @push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-grid.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-theme-alpine.css">
    @endpush

    @push('scripts')
    <script>
        window.csrfToken = '{{ csrf_token() }}';
        window.endpoints = {
            data: '{{ route('reconciliation.data') }}',
            lock: (id) => `/reconciliation/records/${id}/lock`,
            unlock: (id) => `/reconciliation/records/${id}/unlock`,
            resolve: (id) => `/reconciliation/records/${id}/resolve`,
            flag: (id) => `/reconciliation/records/${id}/flag`,
            bulkResolve: '{{ route('reconciliation.records.bulk-resolve') }}',
        };
        window.canBulkApprove = @json(auth()->user() ? auth()->user()->can('reconciliation.bulk_approve') : false);
        window.canEdit = @json(auth()->user() ? auth()->user()->can('reconciliation.edit') : false);
    </script>
    @endpush

    @vite(['resources/js/reconciliation.js'])
</x-reconciliation-layout>