import Alpine from 'alpinejs';

window.auditLogsHub = function() {
    return {
        gridApi: null,
        gridOptions: null,
        filterAction: 'all',
        searchQuery: '',
        currentPage: 1,

        init() {
            this.initGrid();
        },

        initGrid() {
            const gridDiv = document.querySelector('#auditLogGrid');
            if (!gridDiv) {
                return;
            }

            this.gridOptions = {
                // AG Grid v33+ requires explicit theme. 'legacy' keeps existing ag-grid.css.
                theme: 'legacy',
                rowModelType: 'infinite',
                pagination: true,
                paginationPageSize: 50,
                cacheBlockSize: 50,
                maxBlocksInCache: 10,
                suppressRowClickSelection: true,
                rowHeight: 60,
                headerHeight: 52,
                animateRows: true,
                onPaginationChanged: () => {
                    if (this.gridApi) {
                        this.currentPage = this.gridApi.paginationGetCurrentPage() + 1;
                    }
                },
                columnDefs: [
                    {
                        field: 'created_at',
                        headerName: 'Timeline',
                        width: 170,
                        sortable: true,
                        cellRenderer: (params) => {
                            if (!params.value) return '';
                            const d = new Date(params.value);
                            const dateStr = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                            const timeStr = d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                            return `<div class="flex flex-col justify-center h-full">
                                <div class="bob-time-pill font-semibold">${this.escapeHtml(timeStr)}</div>
                                <div class="text-[10px] text-slate-500 mt-0.5 ml-1 leading-none uppercase tracking-wider">${this.escapeHtml(dateStr)}</div>
                            </div>`;
                        }
                    },
                    {
                        field: 'user',
                        headerName: 'Modified By',
                        width: 200,
                        sortable: true,
                        cellRenderer: (params) => {
                            const user = params.data?.modified_by;
                            if (!user) {
                                return `<div class="flex items-center h-full"><span class="bob-empty-value">System</span></div>`;
                            }

                            const userName = this.escapeHtml(user.name || 'Unknown User');
                            const userEmail = this.escapeHtml(user.email || 'No email');
                            const initials = this.escapeHtml((user.name || '??').substring(0, 2).toUpperCase());

                            return `<div class="flex items-center h-full gap-3">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0"
                                     style="background: linear-gradient(135deg, rgb(99 102 241), rgb(168 85 247)); color: white; border: 1px solid rgba(255,255,255,0.1);">
                                    ${initials}
                                </div>
                                <div class="flex flex-col overflow-hidden">
                                    <span class="text-xs font-bold text-slate-200 truncate">${userName}</span>
                                    <span class="text-[10px] text-slate-500 truncate">${userEmail}</span>
                                </div>
                            </div>`;
                        }
                    },
                    {
                        field: 'action',
                        headerName: 'Action',
                        width: 190,
                        sortable: true,
                        cellRenderer: (params) => {
                            if (!params.value) return '';
                            const status = String(params.value).toLowerCase();
                            const actionText = this.actionLabel(status);
                            const colorClass = ['resolved', 'patch_applied'].includes(status)
                                ? 'resolved'
                                : (status === 'flagged' ? 'flagged' : 'pending');

                            return `<div class="flex items-center h-full">
                                <span class="bob-status-pill bob-status-${colorClass}" title="${this.escapeHtml(actionText)}">
                                    <span class="bob-status-dot"></span>${this.escapeHtml(actionText)}
                                </span>
                            </div>`;
                        }
                    },
                    {
                        field: 'transaction_id',
                        headerName: 'Transaction ID',
                        width: 180,
                        sortable: true,
                        cellRenderer: (params) => {
                            if (!params.value) {
                                return '<div class="flex items-center h-full"><span class="bob-empty-value">—</span></div>';
                            }

                            return `<div class="flex items-center h-full">
                                <span class="bob-contract-id text-indigo-300 ml-1">#${this.escapeHtml(params.value)}</span>
                            </div>`;
                        }
                    },
                    {
                        headerName: 'Changes / Details',
                        flex: 1,
                        minWidth: 300,
                        sortable: false,
                        cellRenderer: (params) => this.renderDetailsCell(params),
                    }
                ],
            };

            this.gridApi = agGrid.createGrid(gridDiv, this.gridOptions);
            this.setGridDataSource();
        },

        setGridDataSource() {
            const pageSize = this.gridOptions?.cacheBlockSize || 50;

            const datasource = {
                getRows: (params) => {
                    const startRow = params.startRow ?? 0;
                    const page = Math.floor(startRow / pageSize) + 1;
                    const requestData = {
                        page,
                        limit: pageSize,
                        search: this.searchQuery,
                        actionType: this.filterAction,
                        sortModel: JSON.stringify(params.sortModel || []),
                    };

                    const query = new URLSearchParams(requestData).toString();

                    fetch(`${window.endpoints.auditData}?${query}`)
                        .then((res) => res.json())
                        .then((data) => {
                            const rows = data.data || [];
                            const total = Number(data.total || 0);
                            const endRow = startRow + rows.length;
                            const lastRow = endRow >= total ? total : -1;

                            if (typeof params.successCallback === 'function') {
                                params.successCallback(rows, lastRow);
                                return;
                            }

                            if (typeof params.success === 'function') {
                                params.success({ rowData: rows, rowCount: total });
                            }
                        })
                        .catch((error) => {
                            console.error('Error fetching audit logs:', error);

                            if (typeof params.failCallback === 'function') {
                                params.failCallback();
                                return;
                            }

                            if (typeof params.fail === 'function') {
                                params.fail();
                            }
                        });
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

        actionLabel(action) {
            const actionLabels = {
                lock_acquired: 'Lock Acquired',
                lock_released: 'Lock Released',
                resolved: 'Resolved',
                flagged: 'Flagged',
                patch_applied: 'Patch Applied',
                skipped_locked: 'Skipped Locked',
                skipped_no_flag: 'Skipped No Flag',
            };

            return actionLabels[action] || String(action).replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
        },

        renderDetailsCell(params) {
            if (!params.data) return '';

            const action = String(params.data.action || '').toLowerCase();
            const previousAgent = this.escapeHtml(params.data.previous_agent_code || 'Unassigned');
            const nextAgent = this.escapeHtml(params.data.new_agent_code || 'Unknown');
            const notes = this.escapeHtml(params.data.notes || '');

            if (action === 'resolved') {
                return `<div class="flex items-center h-full gap-2 font-medium">
                    <span class="text-[11px] text-slate-400">Mapped Agent:</span>
                    <div class="bob-value-diff">
                        <span class="bob-diff-prev">${previousAgent}</span>
                        <svg class="w-3.5 h-3.5 text-slate-500 mx-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                        <span class="bob-diff-new">${nextAgent}</span>
                    </div>
                </div>`;
            }

            if (action === 'flagged') {
                return `<div class="flex items-center h-full">
                    <span class="inline-flex items-center gap-1.5 text-xs text-rose-400 font-medium">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        ${notes || 'Record marked for supervisor review.'}
                    </span>
                </div>`;
            }

            if ((params.data.previous_agent_code || params.data.new_agent_code) && params.data.source === 'contract_patch') {
                return `<div class="flex items-center h-full gap-2 font-medium">
                    <span class="text-[11px] text-slate-400">Patch Update:</span>
                    <div class="bob-value-diff">
                        <span class="bob-diff-prev">${previousAgent}</span>
                        <svg class="w-3.5 h-3.5 text-slate-500 mx-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                        <span class="bob-diff-new">${nextAgent}</span>
                    </div>
                </div>`;
            }

            return `<div class="flex items-center h-full">
                <span class="text-xs text-slate-500 font-medium">${notes || 'Standard system action'}</span>
            </div>`;
        },

        escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        },

        updateFilter(actionType) {
            this.filterAction = actionType;
            this.refreshGrid();
        },

        updateSearch(query) {
            this.searchQuery = query;
            this.refreshGrid();
        },

        refreshGrid() {
            if (this.gridApi) {
                if (typeof this.gridApi.purgeInfiniteCache === 'function') {
                    this.gridApi.purgeInfiniteCache();
                } else if (typeof this.gridApi.refreshInfiniteCache === 'function') {
                    this.gridApi.refreshInfiniteCache();
                }
            }
        }
    };
};

if (!window.Alpine) {
    window.Alpine = Alpine;
    Alpine.start();
}
