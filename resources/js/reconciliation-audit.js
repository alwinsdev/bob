import Alpine from 'alpinejs';

window.auditLogsHub = function() {
    return {
            gridApi: null,
            filterAction: 'all',
            searchQuery: '',
            currentPage: 1,

            init() {
                this.initGrid();
            },

            initGrid() {
                const gridOptions = {
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
                                    <div class="bob-time-pill font-semibold">${timeStr}</div>
                                    <div class="text-[10px] text-slate-500 mt-0.5 ml-1 leading-none uppercase tracking-wider">${dateStr}</div>
                                </div>`;
                            }
                        },
                        {
                            field: 'user',
                            headerName: 'Modified By',
                            width: 200,
                            sortable: true,
                            cellRenderer: (params) => {
                                if (!params.data || !params.data.modified_by) {
                                    return `<div class="flex items-center h-full"><span class="bob-empty-value">System</span></div>`;
                                }
                                const user = params.data.modified_by;
                                const initials = user.name ? user.name.substring(0, 2).toUpperCase() : '??';
                                return `<div class="flex items-center h-full gap-3">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0" 
                                         style="background: linear-gradient(135deg, rgb(99 102 241), rgb(168 85 247)); color: white; border: 1px solid rgba(255,255,255,0.1);">
                                        ${initials}
                                    </div>
                                    <div class="flex flex-col overflow-hidden">
                                        <span class="text-xs font-bold text-slate-200 truncate">${user.name}</span>
                                        <span class="text-[10px] text-slate-500 truncate">${user.email}</span>
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
                                const actionLabels = {
                                    lock_acquired: 'Lock Acquired',
                                    lock_released: 'Lock Released',
                                    resolved: 'Resolved',
                                    flagged: 'Flagged',
                                };
                                const actionText = actionLabels[status] || status.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
                                const colorClass = status === 'resolved' ? 'resolved' : (status === 'flagged' ? 'flagged' : 'pending');
                                return `<div class="flex items-center h-full">
                                    <span class="bob-status-pill bob-status-${colorClass}" title="${actionText}">
                                        <span class="bob-status-dot"></span>${actionText}
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
                                if (!params.value) return '<div class="flex items-center h-full"><span class="bob-empty-value">—</span></div>';
                                return `<div class="flex items-center h-full">
                                    <span class="bob-contract-id text-indigo-300 ml-1">#${params.value}</span>
                                </div>`;
                            }
                        },
                        {
                            headerName: 'Changes / Details',
                            flex: 1,
                            minWidth: 300,
                            sortable: false,
                            cellRenderer: (params) => {
                                if (!params.data) return '';
                                const action = params.data.action;
                                
                                if (action === 'resolved') {
                                    const prev = params.data.previous_agent_code || 'Unassigned';
                                    const next = params.data.new_agent_code || 'Unknown';
                                    return `<div class="flex items-center h-full gap-2 font-medium">
                                        <span class="text-[11px] text-slate-400">Mapped Agent:</span>
                                        <div class="bob-value-diff">
                                            <span class="bob-diff-prev">${prev}</span>
                                            <svg class="w-3.5 h-3.5 text-slate-500 mx-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                                            <span class="bob-diff-new">${next}</span>
                                        </div>
                                    </div>`;
                                } else if (action === 'flagged') {
                                    return `<div class="flex items-center h-full">
                                        <span class="inline-flex items-center gap-1.5 text-xs text-rose-400 font-medium">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                            Record marked for supervisor review.
                                        </span>
                                    </div>`;
                                }
                                
                                return `<div class="flex items-center h-full">
                                    <span class="text-xs text-slate-500 font-medium">Standard system action</span>
                                </div>`;
                            }
                        }
                    ],
                };

                const gridDiv = document.querySelector('#auditLogGrid');
                this.gridApi = agGrid.createGrid(gridDiv, gridOptions);

                const datasource = {
                    getRows: (params) => {
                        const startRow = params.startRow ?? 0;
                        const page = Math.floor(startRow / 50) + 1;
                        
                        const requestData = {
                            page: page,
                            limit: 50,
                            search: this.searchQuery,
                            actionType: this.filterAction,
                            sortModel: JSON.stringify(params.sortModel || [])
                        };

                        const query = new URLSearchParams(requestData).toString();

                        fetch(`${window.endpoints.auditData}?${query}`)
                            .then(res => res.json())
                            .then(data => {
                                let lastRow = -1;
                                if (data.data.length < 50) {
                                    lastRow = startRow + data.data.length;
                                }
                                params.successCallback(data.data, lastRow);
                            })
                            .catch(error => {
                                console.error('Error fetching audit logs:', error);
                                params.failCallback();
                            });
                    }
                };

                this.gridApi.setGridOption('datasource', datasource);
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
