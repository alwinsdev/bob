<x-reconciliation-layout>
    <x-slot name="pageTitle">System Audit Logs</x-slot>
    <x-slot name="pageSubtitle">Monitor the trail of interventions, resolutions, and system changes</x-slot>

    <div x-data="auditLogsHub()" class="space-y-6">

        {{-- ── Premium Glassmorphism Toolbar ── --}}
        <div class="bob-glass-panel p-4">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="flex items-center w-full sm:w-auto gap-3">
                    <div class="relative w-full sm:w-80">
                        <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        <input @input.debounce.500ms="updateSearch($event.target.value)" type="text" class="bob-search-input" placeholder="Search by ID, user, action, or note..." />
                    </div>
                    <div class="flex items-center gap-1.5 p-1 rounded-xl" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);">
                        <template x-for="opt in [{v:'all',l:'All'},{v:'resolved',l:'Resolved'},{v:'flagged',l:'Flagged'},{v:'patch_applied',l:'Patched'},{v:'lock_acquired',l:'Lock In'},{v:'lock_released',l:'Lock Out'}]" :key="opt.v">
                            <button @click="updateFilter(opt.v)"
                                class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all duration-200"
                                :class="filterAction === opt.v ? 'bg-indigo-500/20 text-indigo-300 shadow-sm' : 'text-slate-500 hover:text-slate-300 hover:bg-white/5'"
                                x-text="opt.l">
                            </button>
                        </template>
                    </div>
                </div>
                <div class="flex gap-2">
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
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background: linear-gradient(140deg, rgba(99,102,241,0.22), rgba(168,85,247,0.12)); border: 1px solid rgba(168,85,247,0.35);">
                        <svg class="w-4 h-4 text-fuchsia-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white">Action Trail</h3>
                        <p class="text-[11px] text-slate-400 font-medium">Record of all manual system interventions</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 text-[11px] font-semibold">
                    <span class="bob-grid-chip">
                        <span class="bob-grid-chip-dot bob-grid-chip-dot-fuchsia" style="background: #e879f9;"></span>
                        <span x-text="`Filter: ${filterAction === 'all' ? 'All' : (filterAction.charAt(0).toUpperCase() + filterAction.slice(1))}`"></span>
                    </span>
                    <span class="bob-grid-chip bob-grid-chip-muted" x-text="`Page ${currentPage || 1}`"></span>
                </div>
            </div>
            <div class="px-2 pb-2">
                <div id="auditLogGrid" class="ag-theme-alpine ag-theme-bob w-full" style="height: 620px;"></div>
            </div>
        </div>

    </div>

    @push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-grid.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-theme-alpine.css">
    <style>
        .bob-grid-chip-dot-fuchsia { box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.03); }
        .bob-time-pill {
            display: inline-flex;
            align-items: center;
            background: rgba(15, 23, 42, 0.55);
            border: 1px solid rgba(148, 163, 184, 0.15);
            padding: 2px 8px;
            border-radius: 6px;
            color: #94a3b8;
            font-size: 11px;
            font-variant-numeric: tabular-nums;
        }
        .bob-value-diff {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .bob-diff-prev {
            font-size: 11px;
            color: #94a3b8;
            text-decoration: line-through;
            opacity: 0.7;
        }
        .bob-diff-new {
            font-size: 11px;
            font-weight: 700;
            color: #10b981;
            background: rgba(16, 185, 129, 0.1);
            padding: 1px 6px;
            border-radius: 4px;
        }

        html.bob-light .bob-time-pill {
            background: rgba(37, 99, 235, 0.08);
            border-color: rgba(59, 130, 246, 0.24);
            color: #1e3a8a;
        }

        html.bob-light .bob-diff-prev {
            color: #64748b;
            opacity: 0.85;
        }

        html.bob-light .bob-diff-new {
            color: #047857;
            background: rgba(16, 185, 129, 0.14);
        }
    </style>
    @endpush

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.js"></script>
    <script>
        window.csrfToken = '{{ csrf_token() }}';
        window.endpoints = {
            auditData: '{{ route('reconciliation.audit-logs.data') }}'
        };
    </script>
    @endpush

    @vite(['resources/js/reconciliation-audit.js'])
</x-reconciliation-layout>
