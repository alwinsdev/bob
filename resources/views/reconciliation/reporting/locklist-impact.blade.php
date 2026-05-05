<x-reconciliation-layout>
    <x-slot name="pageTitle">Locklist Impact Report</x-slot>
    <x-slot name="pageSubtitle">Displays the "Before & After" effect of Locklist Overrides on automated agent assignments.</x-slot>
    <x-slot name="headerActions">
        <a href="{{ route('reconciliation.reporting.dashboard') }}" class="bob-btn-ghost text-xs">Dashboard</a>
        <a href="{{ route('reconciliation.reporting.final-bob') }}" class="bob-btn-primary text-xs">View Final BOB</a>
        @can('reconciliation.export.download')
        <a :href="locklistExportUrl()" class="bob-btn-primary group" style="background: linear-gradient(135deg, #10b981, #059669); border-color: #047857;">
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


    <div class="max-w-[1600px] mx-auto space-y-6" x-data="locklistImpact()">

        {{-- ── Data Integrity Snapshots ── --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bob-glass-panel p-4 relative overflow-hidden" style="background: var(--bob-metric-rose)">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-rose-500/10 border border-rose-500/20 shadow-lg">
                        <svg class="w-6 h-6 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[10px] font-black uppercase tracking-widest text-rose-300/60 leading-none mb-1">Conflict Resolution</div>
                        <div class="text-2xl font-black text-white" id="stat-conflicts">0</div>
                    </div>
                </div>
            </div>

            <div class="bob-glass-panel p-6 relative overflow-hidden" style="background: var(--bob-metric-indigo)">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-indigo-500/10 border border-indigo-500/20 shadow-lg">
                        <svg class="w-6 h-6 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[10px] font-black uppercase tracking-widest text-indigo-300/60 leading-none mb-1">Automated Overrides</div>
                        <div class="text-2xl font-black text-white" id="stat-automation">0</div>
                    </div>
                </div>
            </div>

            <div class="bob-glass-panel p-6 relative overflow-hidden" style="background: var(--bob-metric-emerald)">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-emerald-500/10 border border-emerald-500/20 shadow-lg">
                        <svg class="w-6 h-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[10px] font-black uppercase tracking-widest text-emerald-300/60 leading-none mb-1">Last Refresh</div>
                        <div class="text-2xl font-black text-white" id="stat-refresh">--:--</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bob-glass-panel bob-grid-shell overflow-hidden shadow-2xl">
            <div class="bob-grid-toolbar px-6 py-4 flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-700/50" style="background: var(--bob-toolbar-bg)">
                <div class="relative w-full md:w-80">
                    <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    <input type="text" id="li-search" @input.debounce.500ms="onSearch" class="bob-search-input" placeholder="Search Contract ID, Agent..." />
                </div>
                <div class="flex items-center gap-3">
                    <div class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Live Difference Feed</div>
                    <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse shadow-[0_0_8px_rgba(16,185,129,0.5)]"></div>
                </div>
            </div>

            <div class="px-2 pb-2">
                <div id="locklistImpactGrid" class="ag-theme-alpine ag-theme-bob w-full" style="height: 640px;"></div>
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
            Alpine.data('locklistImpact', () => ({
                gridApi: null,
                gridOptions: null,
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
                    if (m.includes('ims')) {
                        return `<span style="background:rgba(99,102,241,0.1);color:#818cf8;padding:4px 10px;border-radius:8px;font-size:9px;font-weight:700;border:1px solid rgba(99,102,241,0.15);letter-spacing:0.05em">IMS CORE</span>`;
                    }
                    if (m.includes('health sherpa') || m.includes('hs')) {
                        return `<span style="background:rgba(16,185,129,0.1);color:#10b981;padding:4px 10px;border-radius:8px;font-size:9px;font-weight:700;border:1px solid rgba(16,185,129,0.15);letter-spacing:0.05em">SHERPA FEED</span>`;
                    }
                    return `<span style="font-size:9px;font-weight:700;color:var(--bob-text-faint);text-transform:uppercase;letter-spacing:0.05em">${ms}</span>`;
                },

                updateMetrics(total) {
                    const resolvedTotal = Number(total || 0);
                    document.getElementById('stat-conflicts').innerText = resolvedTotal.toLocaleString();
                    document.getElementById('stat-automation').innerText = resolvedTotal.toLocaleString();
                    document.getElementById('stat-refresh').innerText = new Date().toLocaleTimeString([], {
                        hour: '2-digit',
                        minute: '2-digit',
                    });
                },

                initGrid() {
                    const colDefs = [
                        { field: 'contract_id', headerName: 'Contract ID', flex: 1.2, minWidth: 130, 
                          cellRenderer: p => `<span class="font-mono text-xs font-bold text-white tracking-wider">${this.escapeHtml(p.value) || '—'}</span>` },
                        { field: 'member_name', headerName: 'Affected Member', flex: 1.4, minWidth: 140,
                          cellRenderer: p => `<span class="text-slate-200 font-semibold">${this.escapeHtml(p.value) || '—'}</span>` },
                        { field: 'source_before', headerName: 'Original Logic', flex: 1, minWidth: 130,
                          cellRenderer: p => this.sourceBadge(p.value) },
                        { field: 'old_agent', headerName: 'Agent (Before)', flex: 1.4, minWidth: 160,
                          cellRenderer: p => `<span class="text-slate-500 font-medium line-through decoration-slate-700/50">${this.escapeHtml(p.value) || 'None'}</span>` },
                        { 
                          headerName: '', flex: 0.3, minWidth: 50, sortable: false, filter: false,
                          cellRenderer: () => `<div class="flex items-center justify-center text-indigo-400/30 mt-2"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg></div>` 
                        },
                        { field: 'new_agent', headerName: 'Locked Agent (Final)', flex: 1.4, minWidth: 160,
                          cellRenderer: p => `<span class="text-purple-300 font-black text-xs uppercase tracking-tight bg-purple-500/5 px-2 py-1 rounded shadow-sm border border-purple-500/10">${this.escapeHtml(p.value) || '—'}</span>` },
                        { field: 'override_flag', headerName: 'Status', flex: 1, minWidth: 120, 
                          cellRenderer: p => p.value ? `<span style="background:rgba(168,85,247,0.12);color:#c084fc;padding:4px 10px;border-radius:8px;font-size:9px;font-weight:800;border:1px solid rgba(168,85,247,0.2);display:inline-flex;align-items:center;gap:4px;letter-spacing:0.05em">RESOLVED</span>` : '' }
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
                        overlayNoRowsTemplate: '<div class="flex flex-col items-center gap-2 pt-20"><svg class="w-10 h-10 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M12 15V3m0 12l-4-4m4 4l4-4M2 17l.621 2.485A2 2 0 004.561 21h14.878a2 2 0 001.94-1.515L22 17"/></svg><span class="text-slate-500 font-bold text-sm uppercase tracking-widest">No Impact Detected</span></div>',
                    };

                    const gridDiv = document.querySelector('#locklistImpactGrid');
                    this.gridApi = agGrid.createGrid(gridDiv, this.gridOptions);
                    this.setGridDataSource();
                },

                setGridDataSource() {
                    const pageSize = this.gridOptions?.cacheBlockSize || 100;
                    const datasource = {
                        getRows: async (params) => {
                            const startRow = params.startRow ?? 0;
                            const page = Math.floor(startRow / pageSize) + 1;
                            const url = new URL('{{ route('reconciliation.reporting.locklist-impact.data') }}');

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
                                    throw new Error('Failed to load locklist impact data');
                                }

                                const payload = await res.json();
                                const rows = payload.data || [];
                                const total = Number(payload.total || 0);
                                const endRow = startRow + rows.length;
                                const lastRow = endRow >= total ? total : -1;

                                this.updateMetrics(total);

                                if (typeof params.successCallback === 'function') {
                                    params.successCallback(rows, lastRow);
                                    return;
                                }

                                if (typeof params.success === 'function') {
                                    params.success({ rowData: rows, rowCount: total });
                                }
                            } catch (err) {
                                console.error('Grid load error:', err);
                                document.getElementById('stat-refresh').innerText = 'Error';

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
                    this.searchQuery = document.getElementById('li-search').value.trim();
                    this.refreshGrid();
                },

                locklistExportUrl() {
                    const url = new URL('{{ route('reconciliation.reporting.locklist-impact.export') }}');

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
