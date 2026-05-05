<x-reconciliation-layout>
    <x-slot name="pageTitle">Commission-Ready Final BOB</x-slot>
    <x-slot name="pageSubtitle">Export and review final, locked data ready for commission payout mapping.</x-slot>

    <x-slot name="headerActions">
        <a href="{{ route('reconciliation.reporting.dashboard') }}" class="bob-btn-ghost text-xs">Dashboard</a>
        <a href="{{ route('reconciliation.reporting.final-bob') }}" class="bob-btn-primary text-xs">View Final BOB</a>
        <a href="{{ route('reconciliation.reporting.contract-patches') }}" class="bob-btn-ghost text-xs">Commission Adjustments</a>
        @can('reconciliation.export.download')
        <a :href="finalBobExportUrl()" class="bob-btn-primary group">

            <svg class="w-4 h-4 transition-transform duration-300 group-hover:-translate-y-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            @php
                $prefFormat = strtoupper(auth()->user()->preferences['export_format'] ?? 'XLSX');
            @endphp
            Export ({{ $prefFormat }})
        </a>
        @endcan
    </x-slot>



    <div class="max-w-[1600px] mx-auto space-y-6" x-data="finalBobReporting()">


        {{-- ── Reporting Intelligence Snapshots ── --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bob-glass-panel p-4 relative overflow-hidden" style="background: var(--bob-metric-indigo)">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-indigo-500/10 border border-indigo-500/20 shadow-lg">
                        <svg class="w-6 h-6 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-widest text-indigo-300/60 leading-none mb-1">Production Ready</div>
                        <div class="text-2xl font-black text-white" id="stat-total">0</div>
                    </div>
                </div>
            </div>

            <div class="bob-glass-panel p-5 relative overflow-hidden" style="background: var(--bob-metric-rose)">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-rose-500/10 border border-rose-500/20 shadow-lg">
                        <svg class="w-6 h-6 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-widest text-rose-300/60 leading-none mb-1">Impacted Records</div>
                        <div class="text-2xl font-black text-white" id="stat-impact">0</div>
                    </div>
                </div>
            </div>

            <div class="bob-glass-panel p-5 relative overflow-hidden" style="background: var(--bob-metric-emerald)">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-emerald-500/10 border border-emerald-500/20 shadow-lg">
                        <svg class="w-6 h-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-widest text-emerald-300/60 leading-none mb-1">Override Rate</div>
                        <div class="text-2xl font-black text-white" id="stat-override-rate">0.0%</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Final Grid View ── --}}
        <div class="bob-glass-panel bob-grid-shell overflow-hidden">
            <div class="bob-grid-toolbar px-6 py-4 flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-700/50" style="background: var(--bob-toolbar-bg)">
                <div class="relative w-full md:w-80">
                    <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    <input type="text" id="fb-search" @input.debounce.500ms="onSearch" class="bob-search-input" placeholder="Search Contract ID, Agent, Payee..." />

                </div>
                
                <div class="flex gap-2">
                    <button class="bob-btn-ghost group" @click="refreshGrid">
                        <svg class="w-4 h-4 transition-transform duration-300 group-hover:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" />
                        </svg>
                        Refresh Data
                    </button>
                    <div class="h-9 w-[1px] bg-slate-700/50 mx-1"></div>
                    <button class="bob-btn-ghost flex items-center gap-2" @click="clearSearch">
                        <span class="text-xs font-bold uppercase tracking-tight">Clear Filters</span>
                    </button>
                </div>
            </div>

            <div class="px-2 pb-2">
                <div id="finalBobGrid" class="ag-theme-alpine ag-theme-bob w-full shadow-2xl" style="height: 640px;"></div>
            </div>
        </div>

    </div>

    @push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-grid.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-theme-alpine.css">
    <style>
        .ag-theme-bob .ag-header { border-bottom: 1px solid rgba(255,255,255,0.06) !important; }
        .ag-theme-bob .ag-row { border-bottom: 1px solid rgba(255,255,255,0.03) !important; }
    </style>
    @endpush

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('finalBobReporting', () => ({
                gridApi: null,
                gridOptions: null,
                metrics: { total: 0, override: 0 },
                searchQuery: '',
                batchId: new URLSearchParams(window.location.search).get('batch_id') || '',

                init() {
                    this.initGrid();
                },

                escapeHtml(str) {
                    if (!str) return '';
                    return String(str)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#39;');
                },

                sourceBadge(method) {
                    const m = String(method || '').toLowerCase();
                    const ms = this.escapeHtml(method);
                    if (m === 'lock list override') {
                        return `<span title="Overridden by Lock List" style="background:rgba(168,85,247,0.12);color:#c084fc;padding:4px 10px;border-radius:8px;font-size:10px;font-weight:800;border:1px solid rgba(168,85,247,0.2);display:inline-flex;align-items:center;gap:4px;letter-spacing:0.02em;">LOCKED</span>`;
                    }
                    if (m.startsWith('ims')) {
                        const sub = this.escapeHtml(String(method || '').split(':')[1] || '');
                        return `<span style="background:rgba(99,102,241,0.1);color:#818cf8;padding:4px 10px;border-radius:8px;font-size:9px;font-weight:700;letter-spacing:0.05em;border:1px solid rgba(99,102,241,0.15);">IMS MATCH</span>`;
                    }
                    if (m.startsWith('health sherpa') || m.startsWith('hs')) {
                        return `<span style="background:rgba(16,185,129,0.1);color:#10b981;padding:4px 10px;border-radius:8px;font-size:9px;font-weight:700;letter-spacing:0.05em;border:1px solid rgba(16,185,129,0.15);">HS MATCH</span>`;
                    }
                    return `<span style="font-size:9px;font-weight:700;color:var(--bob-text-faint);text-transform:uppercase;letter-spacing:0.05em">${ms || 'Manual'}</span>`;
                },

                updateMetrics(metrics) {
                    this.metrics.total = Number(metrics.total || 0);
                    this.metrics.override = Number(metrics.override_count || 0);

                    const overrideRate = this.metrics.total > 0
                        ? ((this.metrics.override / this.metrics.total) * 100).toFixed(1)
                        : '0.0';

                    document.getElementById('stat-total').innerText = this.metrics.total.toLocaleString();
                    document.getElementById('stat-impact').innerText = this.metrics.override.toLocaleString();
                    document.getElementById('stat-override-rate').innerText = `${overrideRate}%`;
                },

                initGrid() {
                    const colDefs = [
                        { field: 'contract_id', headerName: 'Contract ID', flex: 1.2, minWidth: 130, 
                          cellRenderer: p => `<span class="font-mono text-xs font-bold text-white tracking-wider">${this.escapeHtml(p.value) || '—'}</span>` },
                        { field: 'member_name', headerName: 'Member', flex: 1.4, minWidth: 140,
                          cellRenderer: p => `<span class="text-slate-200 font-semibold">${this.escapeHtml(p.value) || '—'}</span>` },
                        { field: 'agent_name', headerName: 'Assigned Agent', flex: 1.5, minWidth: 150,
                          cellRenderer: p => `<span class="text-indigo-300 font-bold">${this.escapeHtml(p.value) || '—'}</span>` },
                        { field: 'department', headerName: 'Department', flex: 1.3, minWidth: 130,
                          cellRenderer: p => `<span class="text-slate-400 font-mono text-[10px] font-bold tracking-tight bg-white/5 px-2 py-1 rounded shadow-sm">${this.escapeHtml(p.value) || '—'}</span>` },
                        { field: 'payee_name', headerName: 'Payee Name', flex: 1.5, minWidth: 150,
                        cellRenderer: p => `<span style="color: var(--bob-text-secondary); font-weight: 700; letter-spacing: 0.01em;">${this.escapeHtml(p.value) || '—'}</span>` },
                        { field: 'match_method', headerName: 'Source', flex: 1.2, minWidth: 120, 
                          cellRenderer: p => this.sourceBadge(p.value) },
                                                {
                                                    field: 'patch_ledger_url',
                                                    headerName: 'Adjustment Trace',
                                                    flex: 0.9,
                                                    minWidth: 120,
                                                    sortable: false,
                                                    filter: false,
                                                    cellRenderer: p => p.data?.patch_trace_available
                                                            ? `<a href="${this.escapeHtml(p.value)}" class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[10px] font-black tracking-wider" style="background:rgba(99,102,241,0.14);color:#a5b4fc;border:1px solid rgba(99,102,241,0.25);">DETAILS</a>`
                                                            : `<span class="text-slate-600 text-[10px] uppercase font-bold tracking-widest">N/A</span>`
                                                },
                        { field: 'override_flag', headerName: 'Override?', flex: 0.8, minWidth: 100, 
                          cellRenderer: p => p.value ? `<span class="text-purple-400 font-black text-[10px] uppercase tracking-widest">Yes</span>` : `<span class="text-slate-600 text-[10px] uppercase font-bold tracking-widest">No</span>` }
                    ];

                    this.gridOptions = {
                        columnDefs: colDefs,
                        // AG Grid v33+ requires explicit theme. 'legacy' keeps the
                        // existing ag-grid.css styles (resolves console error #239).
                        theme: 'legacy',
                        rowModelType: 'infinite',
                        rowHeight: 56,
                        headerHeight: 52,
                        pagination: true,
                        paginationPageSize: 100,
                        paginationPageSizeSelector: [25, 50, 100, 250],
                        cacheBlockSize: 100,
                        maxBlocksInCache: 10,
                        suppressCellFocus: true,
                        enableCellTextSelection: true,
                        animateRows: true,
                        defaultColDef: {
                            sortable: true,
                            filter: false,
                            resizable: true,
                        },
                        overlayLoadingTemplate: '<div class="ag-custom-loading"><div class="w-10 h-10 rounded-full border-4 border-indigo-500/20 border-t-indigo-500 animate-spin"></div></div>',
                        overlayNoRowsTemplate: '<div class="flex flex-col items-center gap-2 pt-20"><svg class="w-10 h-10 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7zM9 12h6M9 16h3"/></svg><span class="text-slate-500 font-bold text-sm uppercase tracking-widest">Archive Empty</span></div>',
                    };

                    const gridDiv = document.querySelector('#finalBobGrid');
                    this.gridApi = agGrid.createGrid(gridDiv, this.gridOptions);
                    this.setGridDataSource();
                },

                setGridDataSource() {
                    const pageSize = this.gridOptions?.cacheBlockSize || 100;
                    const datasource = {
                        getRows: async (params) => {
                            const startRow = params.startRow ?? 0;
                            const page = Math.floor(startRow / pageSize) + 1;
                            const url = new URL('{{ route('reconciliation.reporting.final-bob.data') }}');

                            if (this.batchId) {
                                url.searchParams.set('batch_id', this.batchId);
                            }

                            if (this.searchQuery) {
                                url.searchParams.set('search', this.searchQuery);
                            }

                            url.searchParams.set('page', page);
                            url.searchParams.set('limit', pageSize);
                            url.searchParams.set('sortModel', JSON.stringify(params.sortModel || []));

                            try {
                                const res = await fetch(url);
                                if (!res.ok) {
                                    throw new Error('Failed to load Final BOB data');
                                }

                                const payload = await res.json();
                                const rows = payload.data || [];
                                const total = Number(payload.total || 0);
                                const endRow = startRow + rows.length;
                                const lastRow = endRow >= total ? total : -1;

                                this.updateMetrics(payload.metrics || { total, override_count: 0 });

                                if (typeof params.successCallback === 'function') {
                                    params.successCallback(rows, lastRow);
                                    return;
                                }

                                if (typeof params.success === 'function') {
                                    params.success({ rowData: rows, rowCount: total });
                                }
                            } catch (err) {
                                console.error('Grid load error:', err);
                                this.updateMetrics({ total: 0, override_count: 0 });

                                if (typeof params.failCallback === 'function') {
                                    params.failCallback();
                                    return;
                                }

                                if (typeof params.fail === 'function') {
                                    params.fail();
                                }
                            }
                        }
                    };

                    if (typeof this.gridApi.setGridOption === 'function') {
                        this.gridApi.setGridOption('datasource', datasource);
                        return;
                    }

                    if (typeof this.gridApi.setDatasource === 'function') {
                        this.gridApi.setDatasource(datasource);
                    }
                },

                refreshGrid() {
                    if (!this.gridApi) return;

                    if (typeof this.gridApi.purgeInfiniteCache === 'function') {
                        this.gridApi.purgeInfiniteCache();
                    } else if (typeof this.gridApi.refreshInfiniteCache === 'function') {
                        this.gridApi.refreshInfiniteCache();
                    }
                },

                onSearch() {
                    this.searchQuery = document.getElementById('fb-search').value.trim();
                    this.refreshGrid();
                },

                clearSearch() {
                    document.getElementById('fb-search').value = '';
                    this.searchQuery = '';
                    this.refreshGrid();
                },

                finalBobExportUrl() {
                    const url = new URL('{{ route('reconciliation.reporting.final-bob.export') }}');

                    if (this.batchId) {
                        url.searchParams.set('batch_id', this.batchId);
                    }

                    if (this.searchQuery) {
                        url.searchParams.set('search', this.searchQuery);
                    }

                    return url.toString();
                }
            }));
        });
    </script>
    @endpush
</x-reconciliation-layout>
