document.addEventListener('alpine:init', () => {
    Alpine.data('reconciliationHub', () => ({
        gridOptions: null,
        gridApi: null,
        activeRecord: null,
        drawerOpen: false,
        selectedCount: 0,
        filterStatus: 'all',
        searchQuery: '',
        resolutionData: {
            aligned_agent_code: '',
            compensation_type: ''
        },

        init() {
            this.initGrid();
        },

        initGrid() {
            const gridDiv = document.querySelector('#reconciliationGrid');
            if (!gridDiv) return;

            this.gridOptions = {
                rowModelType: 'serverSide',
                pagination: true,
                paginationPageSize: 50,
                cacheBlockSize: 50,
                rowSelection: 'multiple',
                suppressRowClickSelection: true,
                onSelectionChanged: () => {
                    if (this.gridApi) {
                        this.selectedCount = this.gridApi.getSelectedNodes().length;
                    }
                },
                columnDefs: [
                    { 
                        headerName: 'Select', 
                        checkboxSelection: true, 
                        headerCheckboxSelection: true,
                        width: 50,
                        pinned: 'left'
                    },
                    { 
                        headerName: 'Action', 
                        field: 'id', 
                        width: 120,
                        pinned: 'left',
                        cellRenderer: (params) => {
                            if (!params.data) return '';
                            if (params.data.status === 'resolved' || !window.canEdit) {
                                return `<span class="text-gray-400 text-xs text-center w-full block mt-2">N/A</span>`;
                            }
                            return `<button onclick="window.dispatchEvent(new CustomEvent('open-drawer', {detail: '${params.data.id}'}))" class="text-indigo-600 hover:text-indigo-900 text-xs font-semibold py-1 px-2 border border-indigo-200 rounded mt-1 bg-indigo-50">Review</button>`;
                        }
                    },
                    { field: 'status', headerName: 'Status', width: 120, cellRenderer: (params) => {
                        if (!params.value) return '';
                        const colors = {
                            pending: 'bg-gray-100 text-gray-800',
                            matched: 'bg-green-100 text-green-800',
                            flagged: 'bg-yellow-100 text-yellow-800',
                            resolved: 'bg-blue-100 text-blue-800'
                        };
                        const cls = colors[params.value] || colors.pending;
                        return `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${cls}">${params.value.toUpperCase()}</span>`;
                    }},
                    { field: 'contract_id', headerName: 'Contract ID', width: 140 },
                    { field: 'member_first_name', headerName: 'First Name', width: 130 },
                    { field: 'member_last_name', headerName: 'Last Name', width: 130 },
                    { field: 'carrier', headerName: 'Carrier', width: 130 },
                    { 
                        headerName: 'Fuzzy Match', 
                        field: 'match_confidence', 
                        width: 150,
                        cellRenderer: (params) => {
                            if (!params.value) return `<span class="text-gray-400 text-sm">--</span>`;
                            return `<div class="w-full bg-gray-200 rounded-full h-2 mt-4"><div class="bg-blue-600 h-2 rounded-full" style="width: ${params.value}%"></div></div><span class="text-xs text-gray-500">${params.value}%</span>`;
                        }
                    },
                    { field: 'ims_transaction_id', headerName: 'IMS ID', width: 130 },
                    { field: 'aligned_agent_code', headerName: 'Aligned Agent', width: 140 },
                ],
                defaultColDef: {
                    sortable: true,
                    resizable: true,
                    filter: false 
                }
            };

            this.gridApi = agGrid.createGrid(gridDiv, this.gridOptions);
            this.setGridDataSource();

            // Listen to open drawer
            window.addEventListener('open-drawer', (e) => this.openDrawerFor(e.detail));
        },

        setGridDataSource() {
            const datasource = {
                getRows: (params) => {
                    const page = (params.request.startRow / 50) + 1;
                    const sortModel = params.request.sortModel[0];
                    const sortCol = sortModel ? sortModel.colId : 'created_at';
                    const sortDir = sortModel ? sortModel.sort : 'desc';

                    const url = new URL(window.endpoints.data);
                    url.searchParams.append('page', page);
                    url.searchParams.append('per_page', 50);
                    url.searchParams.append('status', this.filterStatus);
                    url.searchParams.append('search', this.searchQuery);
                    url.searchParams.append('sort_by', sortCol);
                    url.searchParams.append('sort_dir', sortDir);

                    fetch(url)
                        .then(response => response.json())
                        .then(data => {
                            params.success({
                                rowData: data.data,
                                rowCount: data.total
                            });
                        })
                        .catch(error => {
                            console.error(error);
                            params.fail();
                        });
                }
            };
            this.gridApi.setServerSideDatasource(datasource);
        },

        updateFilter(val) {
            this.filterStatus = val;
            this.refreshGrid();
        },

        updateSearch(val) {
            this.searchQuery = val;
            this.refreshGrid();
        },

        refreshGrid() {
            if (this.gridApi) {
                this.gridApi.refreshServerSide({ purge: true });
                this.selectedCount = 0;
            }
        },

        async openDrawerFor(id) {
            // Find record data
            let obj = null;
            this.gridApi.forEachNode((node) => { if (node.data.id === id) obj = node.data; });
            
            if (!obj) {
                // if it's not found in current page nodes
                alert("Data not found on current page");
                return;
            }
            const nodeData = obj;

            try {
                const res = await fetch(window.endpoints.lock(id), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': window.csrfToken, 'Content-Type': 'application/json' }
                });
                const response = await res.json();
                
                if (!response.success) {
                    alert(response.message);
                    return;
                }

                this.activeRecord = nodeData;
                this.resolutionData.aligned_agent_code = '';
                this.resolutionData.compensation_type = '';
                
                if (this.activeRecord.agent_id) {
                    this.resolutionData.aligned_agent_code = this.activeRecord.agent_id;
                }

                this.drawerOpen = true;

            } catch (err) {
                alert('Could not lock record. See console.');
                console.error(err);
            }
        },

        async closeDrawer() {
            if (this.activeRecord) {
                const id = this.activeRecord.id;
                fetch(window.endpoints.unlock(id), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': window.csrfToken, 'Content-Type': 'application/json' }
                }).catch(e => console.error(e));
            }
            this.drawerOpen = false;
            setTimeout(() => this.activeRecord = null, 300);
            this.refreshGrid();
        },

        async submitResolution() {
            if (!this.resolutionData.aligned_agent_code || !this.resolutionData.compensation_type) {
                alert('Please fill out all resolution fields.');
                return;
            }

            try {
                const res = await fetch(window.endpoints.resolve(this.activeRecord.id), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': window.csrfToken, 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.resolutionData)
                });
                
                const response = await res.json();
                if (response.success) {
                    this.activeRecord = null;
                    this.drawerOpen = false;
                    this.refreshGrid();
                } else {
                    alert(response.message || 'Validation failed.');
                }
            } catch (err) {
                console.error(err);
                alert('An error occurred during resolution.');
            }
        },

        async flagRecord() {
            try {
                const res = await fetch(window.endpoints.flag(this.activeRecord.id), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': window.csrfToken, 'Content-Type': 'application/json' }
                });
                
                const response = await res.json();
                if (response.success) {
                    this.activeRecord = null;
                    this.drawerOpen = false;
                    this.refreshGrid();
                } else {
                    alert(response.message);
                }
            } catch (err) {
                console.error(err);
            }
        },

        async bulkResolve() {
            if (!confirm(`Are you sure you want to resolve ${this.selectedCount} marked records?`)) return;

            const selectedNodes = this.gridApi.getSelectedNodes();
            const ids = selectedNodes.map(n => n.data.id);

            const code = prompt("Enter Aligning Agent Code for all selected:");
            if (!code) return;

            const compType = prompt("Enter Compensation Type (New/Renewal):");
            if (!compType || (compType !== 'New' && compType !== 'Renewal')) return;

            try {
                const res = await fetch(window.endpoints.bulkResolve, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': window.csrfToken, 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        record_ids: ids,
                        aligned_agent_code: code,
                        compensation_type: compType
                    })
                });
                
                const response = await res.json();
                alert(response.message || 'Done.');
                this.refreshGrid();
            } catch (err) {
                console.error(err);
                alert('Error during bulk resolution.');
            }
        }
    }));
});
