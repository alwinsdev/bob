<x-reconciliation-layout>
    <x-slot name="pageTitle">Commission Adjustment Details</x-slot>
    <x-slot name="pageSubtitle">Detailed before-and-after history for commission adjustment runs.</x-slot>

    <x-slot name="headerActions">
        <a href="{{ route('reconciliation.reporting.dashboard') }}" class="bob-btn-ghost text-xs">Dashboard</a>
        <a href="{{ route('reconciliation.reporting.final-bob', ['batch_id' => $selectedParentBatchId]) }}" class="bob-btn-primary text-xs">Final BOB</a>
        @can('reconciliation.etl.run')
            <a href="{{ route('reconciliation.upload.index') }}" class="bob-btn-ghost text-xs">Upload Runs</a>
        @endcan
    </x-slot>

    <div class="max-w-[1600px] mx-auto space-y-4"
        x-data="contractPatchLedger({
            parentBatchOptions: @js($parentBatchOptions),
            patchBatchOptions: @js($patchBatchOptions),
            selectedParentBatchId: @js($selectedParentBatchId),
            selectedPatchBatchId: @js($selectedPatchBatchId),
            initialContractSearch: @js($initialContractSearch),
            dataUrl: @js(route('reconciliation.reporting.contract-patches.data')),
            pageUrl: @js(route('reconciliation.reporting.contract-patches')),
        })">

        <div x-show="parentBatchOptions.length === 0" x-cloak>
            <div class="bob-glass-panel p-6 border border-amber-500/20" style="background: rgba(245, 158, 11, 0.08);">
                <p class="text-sm font-semibold text-amber-200">No commission adjustment run is available for reporting yet.</p>
                <p class="text-xs text-amber-100/70 mt-1">Run an associated contract correction from Upload to populate this detail view.</p>
            </div>
        </div>

        <div x-show="parentBatchOptions.length > 0" x-cloak class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bob-glass-panel p-4" style="background: var(--bob-metric-emerald)">
                        <div class="text-[10px] font-black uppercase tracking-widest text-emerald-300/70">Adjusted Rows</div>
                        <div class="text-2xl font-black text-white mt-1.5" x-text="metrics.patched.toLocaleString()">0</div>
                        <div class="text-[11px] text-emerald-200/80 mt-1">Successful row-level updates in selected scope.</div>
                    </div>

                    <div class="bob-glass-panel p-4" style="background: var(--bob-metric-indigo)">
                        <div class="text-[10px] font-black uppercase tracking-widest text-indigo-300/70">Skipped Rows</div>
                        <div class="text-2xl font-black text-white mt-1.5" x-text="metrics.skipped.toLocaleString()">0</div>
                        <div class="text-[11px] text-indigo-200/80 mt-1">Rows intentionally not patched due to rules.</div>
                    </div>

                    <div class="bob-glass-panel p-4" style="background: var(--bob-metric-rose)">
                        <div class="text-[10px] font-black uppercase tracking-widest text-rose-300/70">Failed Rows</div>
                        <div class="text-2xl font-black text-white mt-1.5" x-text="metrics.failed.toLocaleString()">0</div>
                        <div class="text-[11px] text-rose-200/80 mt-1">Rows that failed during contract patch processing.</div>
                    </div>
                </div>

                <div class="bob-glass-panel bob-grid-shell overflow-hidden">
                    <div class="contract-filter-toolbar bob-grid-toolbar px-4 py-3 border-b border-slate-700/50 grid grid-cols-1 lg:grid-cols-12 gap-3"
                        style="background: var(--bob-toolbar-bg)">
                        <div class="w-full lg:col-span-4 xl:col-span-3">
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Parent Standard Batch</label>
                            <div class="relative">
                                <select class="bob-search-input contract-filter-select h-10" x-model="parentBatchId" @change="onParentBatchChange($event)">
                                    <template x-for="option in parentBatchOptions" :key="option.id">
                                        <option :value="option.id" x-text="option.label"></option>
                                    </template>
                                </select>
                                <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </div>
                        </div>

                        <div class="w-full lg:col-span-4 xl:col-span-3">
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Adjustment Run</label>
                            <div class="relative">
                                <select class="bob-search-input contract-filter-select h-10" x-model="patchBatchId" @change="onPatchBatchChange($event)">
                                    <option value="">All patch runs for selected batch</option>
                                    <template x-for="option in patchBatchOptions" :key="option.id">
                                        <option :value="option.id" x-text="option.label"></option>
                                    </template>
                                </select>
                                <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </div>
                        </div>

                        <div class="relative w-full lg:col-span-3 xl:col-span-4">
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Contract / Agent / Payee Search</label>
                            <input type="text"
                                x-model="searchQuery"
                                @input.debounce.350ms="onSearch"
                                class="bob-search-input h-10"
                                placeholder="Filter ledger entries..." />
                        </div>

                        <div class="w-full lg:col-span-1 xl:col-span-2 self-end flex lg:justify-end">
                            <button class="bob-btn-ghost h-10" @click="loadData">
                                Refresh
                            </button>
                        </div>
                    </div>

                    <div class="px-4 py-2 border-b border-slate-700/30 flex items-center justify-between text-[11px]" style="background: rgba(2, 6, 23, 0.35);">
                        <span class="text-slate-500 font-semibold uppercase tracking-wider">Adjustment Trace Log</span>
                        <span class="text-indigo-300 font-semibold"><span class="text-slate-500">Rows:</span> <span x-text="totalRows.toLocaleString()">0</span></span>
                    </div>

                    <div class="px-2 pb-2">
                        <div id="contractPatchLedgerGrid" class="ag-theme-alpine ag-theme-bob w-full" style="height: 520px;"></div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4" x-show="runSummary">
                    <div class="bob-glass-panel p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Current Adjustment Run</div>
                                <div class="text-sm font-bold text-white mt-1" x-text="runSummary?.contract_original_name || 'Commission Adjustment'"></div>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-md"
                                style="background: rgba(99, 102, 241, 0.12); color: #a5b4fc; border: 1px solid rgba(99, 102, 241, 0.25);"
                                x-text="(runSummary?.status || 'unknown').replaceAll('_', ' ')"></span>
                        </div>
                        <div class="text-xs text-slate-400 mt-2" x-text="runSummary?.formatted_date"></div>
                    </div>

                    <div class="bob-glass-panel p-4">
                        <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Top Skip Reasons</div>
                        <div class="mt-2 space-y-1.5" x-show="reasonEntries(runSummary?.skipped_summary).length > 0">
                            <template x-for="entry in reasonEntries(runSummary?.skipped_summary)" :key="entry[0]">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-300 truncate pr-2" x-text="entry[0]"></span>
                                    <span class="font-mono font-bold text-indigo-300" x-text="entry[1]"></span>
                                </div>
                            </template>
                        </div>
                        <div class="mt-2 text-xs text-slate-500" x-show="reasonEntries(runSummary?.skipped_summary).length === 0">
                            No skip reasons were recorded for this patch run.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('head')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-grid.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-theme-alpine.css">
        <style>
            .ag-theme-bob .ag-header {
                border-bottom: 1px solid rgba(255, 255, 255, 0.06) !important;
            }

            .ag-theme-bob .ag-row {
                border-bottom: 1px solid rgba(255, 255, 255, 0.03) !important;
            }

            .contract-filter-select {
                appearance: none;
                padding-right: 2.25rem;
            }

            .contract-filter-select option {
                background: #0b1638;
                color: #e2e8f0;
            }

            @media (max-width: 1024px) {
                #contractPatchLedgerGrid {
                    height: 460px !important;
                }
            }

            html.bob-light .contract-filter-select option {
                background: #ffffff;
                color: #0f172a;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.js"></script>
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('contractPatchLedger', (config) => ({
                    parentBatchOptions: config.parentBatchOptions || [],
                    patchBatchOptions: config.patchBatchOptions || [],
                    parentBatchId: config.selectedParentBatchId || '',
                    patchBatchId: config.selectedPatchBatchId || '',
                    searchQuery: config.initialContractSearch || '',
                    metrics: {
                        patched: 0,
                        skipped: 0,
                        failed: 0,
                    },
                    totalRows: 0,
                    runSummary: null,
                    gridApi: null,

                    init() {
                        if (!this.parentBatchOptions.length) {
                            return;
                        }

                        this.$nextTick(() => {
                            this.initGrid();
                            this.loadData();
                        });
                    },

                    reasonEntries(summary) {
                        if (!summary || typeof summary !== 'object') {
                            return [];
                        }

                        return Object.entries(summary)
                            .sort((a, b) => Number(b[1]) - Number(a[1]))
                            .slice(0, 6);
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

                    onParentBatchChange(event) {
                        const selectedParent = event.target.value || '';
                        const params = new URLSearchParams(window.location.search);

                        if (selectedParent) {
                            params.set('parent_batch_id', selectedParent);
                        } else {
                            params.delete('parent_batch_id');
                        }

                        params.delete('batch_id');
                        params.delete('contract_id');
                        window.location.href = `${config.pageUrl}?${params.toString()}`;
                    },

                    onPatchBatchChange(event) {
                        const selectedPatch = event.target.value || '';
                        const params = new URLSearchParams(window.location.search);

                        if (this.parentBatchId) {
                            params.set('parent_batch_id', this.parentBatchId);
                        }

                        if (selectedPatch) {
                            params.set('batch_id', selectedPatch);
                        } else {
                            params.delete('batch_id');
                        }

                        params.delete('contract_id');
                        window.location.href = `${config.pageUrl}?${params.toString()}`;
                    },

                    onSearch() {
                        this.loadData();
                    },

                    requestUrl() {
                        const params = new URLSearchParams();

                        if (this.parentBatchId) {
                            params.set('parent_batch_id', this.parentBatchId);
                        }
                        if (this.patchBatchId) {
                            params.set('batch_id', this.patchBatchId);
                        }
                        if (this.searchQuery) {
                            params.set('search', this.searchQuery);
                        }

                        return `${config.dataUrl}?${params.toString()}`;
                    },

                    initGrid() {
                        if (this.gridApi) {
                            return;
                        }

                        const gridElement = this.$el.querySelector('#contractPatchLedgerGrid');
                        if (!gridElement) {
                            return;
                        }

                        const columnDefs = [
                            {
                                field: 'patched_at',
                                headerName: 'Patched At',
                                minWidth: 160,
                                flex: 1,
                                cellRenderer: (params) => `<span class="text-slate-300 text-xs font-medium">${this.escapeHtml(params.value) || 'n/a'}</span>`,
                            },
                            {
                                field: 'contract_id',
                                headerName: 'Contract ID',
                                minWidth: 140,
                                flex: 1,
                                cellRenderer: (params) => `<span class="font-mono text-xs font-bold text-white">${this.escapeHtml(params.value) || 'n/a'}</span>`,
                            },
                            {
                                field: 'old_agent_name',
                                headerName: 'Agent Before',
                                minWidth: 170,
                                flex: 1.2,
                                cellRenderer: (params) => `<span class="text-slate-500 line-through decoration-slate-700/60">${this.escapeHtml(params.value) || 'n/a'}</span>`,
                            },
                            {
                                field: 'new_agent_name',
                                headerName: 'Agent After',
                                minWidth: 170,
                                flex: 1.2,
                                cellRenderer: (params) => `<span class="text-emerald-300 font-bold">${this.escapeHtml(params.value) || 'n/a'}</span>`,
                            },
                            {
                                field: 'old_department',
                                headerName: 'Dept Before',
                                minWidth: 160,
                                flex: 1,
                                cellRenderer: (params) => `<span class="text-slate-500">${this.escapeHtml(params.value) || 'n/a'}</span>`,
                            },
                            {
                                field: 'new_department',
                                headerName: 'Dept After',
                                minWidth: 160,
                                flex: 1,
                                cellRenderer: (params) => `<span class="text-indigo-300 font-semibold">${this.escapeHtml(params.value) || 'n/a'}</span>`,
                            },
                            {
                                field: 'old_payee_name',
                                headerName: 'Payee Before',
                                minWidth: 180,
                                flex: 1.2,
                                cellRenderer: (params) => `<span class="text-slate-500">${this.escapeHtml(params.value) || 'n/a'}</span>`,
                            },
                            {
                                field: 'new_payee_name',
                                headerName: 'Payee After',
                                minWidth: 180,
                                flex: 1.2,
                                cellRenderer: (params) => `<span class="text-teal-300 font-semibold">${this.escapeHtml(params.value) || 'n/a'}</span>`,
                            },
                            {
                                field: 'flag_value',
                                headerName: 'Flag',
                                minWidth: 110,
                                flex: 0.7,
                                cellRenderer: (params) => `<span class="text-[10px] uppercase font-black tracking-widest text-amber-300">${this.escapeHtml(params.value) || 'n/a'}</span>`,
                            },
                            {
                                field: 'change_type',
                                headerName: 'Change Type',
                                minWidth: 130,
                                flex: 0.8,
                                cellRenderer: (params) => `<span class="text-[10px] uppercase font-black tracking-widest text-cyan-300">${this.escapeHtml(params.value) || 'n/a'}</span>`,
                            },
                            {
                                field: 'updated_by_name',
                                headerName: 'Patched By',
                                minWidth: 130,
                                flex: 0.9,
                                cellRenderer: (params) => `<span class="text-slate-200 font-semibold">${this.escapeHtml(params.value) || 'System'}</span>`,
                            },
                        ];

                        const gridOptions = {
                            columnDefs,
                            rowModelType: 'clientSide',
                            rowHeight: 48,
                            headerHeight: 46,
                            pagination: true,
                            paginationPageSize: 75,
                            suppressCellFocus: true,
                            enableCellTextSelection: true,
                            animateRows: true,
                            defaultColDef: {
                                sortable: true,
                                filter: true,
                                resizable: true,
                            },
                            overlayLoadingTemplate: '<div class="ag-custom-loading"><div class="w-10 h-10 rounded-full border-4 border-indigo-500/20 border-t-indigo-500 animate-spin"></div></div>',
                            overlayNoRowsTemplate: '<div class="flex flex-col items-center gap-2 pt-20"><span class="text-slate-500 font-bold text-sm uppercase tracking-widest">No adjustment records found</span></div>',
                        };

                        this.gridApi = agGrid.createGrid(gridElement, gridOptions);
                    },

                    async loadData() {
                        if (!this.gridApi) {
                            this.initGrid();
                        }

                        if (!this.gridApi) {
                            return;
                        }

                        this.gridApi.showLoadingOverlay();

                        try {
                            const response = await fetch(this.requestUrl());
                            if (!response.ok) {
                                throw new Error('Failed to load adjustment detail data');
                            }
                            const payload = await response.json();

                            const rows = payload.rows || [];
                            this.totalRows = Number(payload.totalCount || rows.length || 0);
                            this.runSummary = payload.runSummary || null;

                            this.gridApi.setGridOption('rowData', rows);
                            this.gridApi.setGridOption('quickFilterText', this.searchQuery || '');

                            if (rows.length === 0) {
                                this.gridApi.showNoRowsOverlay();
                            } else {
                                this.gridApi.hideOverlay();
                            }

                            this.metrics.patched = Number(this.runSummary?.contract_patched_records ?? rows.length);
                            this.metrics.skipped = Number(this.runSummary?.skipped_records ?? 0);
                            this.metrics.failed = Number(this.runSummary?.failed_records ?? 0);
                        } catch (error) {
                            console.error('Commission adjustment detail load error:', error);
                            this.gridApi.showNoRowsOverlay();
                        }
                    },
                }));
            });
        </script>
    @endpush
</x-reconciliation-layout>
