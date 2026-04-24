document.addEventListener('alpine:init', () => {
    Alpine.data('reconciliationHub', () => ({
        gridOptions: null,
        gridApi: null,
        activeRecord: null,
        drawerOpen: false,
        selectedCount: 0,
        filterStatus: 'all',
        searchQuery: '',
        currentPage: 1,
        resolutionData: {
            aligned_agent_code: '',
            compensation_type: '',
            save_to_locklist: false
        },
        flagValue: 'House Open',

        init() {
            this.initGrid();
        },

        initGrid() {
            const gridDiv = document.querySelector('#reconciliationGrid');
            if (!gridDiv) return;

            this.gridOptions = {
                theme: 'legacy',
                rowModelType: 'infinite',
                pagination: true,
                paginationPageSize: 50,
                cacheBlockSize: 50,
                maxBlocksInCache: 10,
                rowSelection: {
                    mode: 'multiRow',
                    checkboxes: true,
                    headerCheckbox: false,
                    enableClickSelection: false,
                },
                selectionColumnDef: {
                    width: 52,
                    pinned: 'left',
                    suppressSizeToFit: true,
                },
                rowHeight: 58,
                headerHeight: 52,
                animateRows: true,
                onSelectionChanged: () => {
                    if (this.gridApi) {
                        this.selectedCount = this.gridApi.getSelectedNodes().length;
                    }
                },
                onPaginationChanged: () => {
                    if (this.gridApi) {
                        this.currentPage = this.gridApi.paginationGetCurrentPage() + 1;
                    }
                },
                columnDefs: [
                    {
                        headerName: 'Action',
                        field: 'id',
                        width: 110,
                        pinned: 'left',
                        cellRenderer: (params) => {
                            if (!params.data) return '';
                            if (params.data.status === 'resolved' || !window.canEdit) {
                                return `<div class="flex items-center h-full">
                                    <span class="bob-review-done">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        Done
                                    </span>
                                </div>`;
                            }
                            const rowId = encodeURIComponent(String(params.data.id || ''));
                            return `<div class="flex items-center h-full">
                                <button onclick="window.dispatchEvent(new CustomEvent('open-drawer', {detail: decodeURIComponent('${rowId}')}))"
                                    class="bob-review-btn">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Review
                                </button>
                            </div>`;
                        }
                    },
                    {
                        field: 'status',
                        headerName: 'Status',
                        width: 140,
                        cellRenderer: (params) => {
                            if (!params.value) return '';
                            const val = String(params.value).toLowerCase();
                            const styles = {
                                pending: 'pending',
                                matched: 'matched',
                                flagged: 'flagged',
                                resolved: 'resolved',
                            };
                            const status = styles[val] || styles.pending;
                            const statusLabel = this.escapeHtml(val);

                            let flagLabel = '';
                            if (val === 'flagged' && params.data && params.data.flag_value) {
                                flagLabel = `<div class="text-[9px] font-black tracking-widest uppercase mt-0.5" style="color:rgba(225,29,72,0.85);">${this.escapeHtml(params.data.flag_value)}</div>`;
                            }

                            return `<div class="flex flex-col justify-center h-full leading-tight">
                                <div>
                                    <span class="bob-status-pill bob-status-${status}">
                                        <span class="bob-status-dot"></span>${statusLabel}
                                    </span>
                                </div>
                                ${flagLabel}
                            </div>`;
                        }
                    },
                    {
                        field: 'contract_id',
                        headerName: 'Contract ID',
                        width: 145,
                        cellRenderer: (params) => {
                            if (!params.value) return '';
                            return `<span class="bob-contract-id">${this.escapeHtml(params.value)}</span>`;
                        }
                    },
                    {
                        field: 'member_first_name',
                        headerName: 'First Name',
                        width: 130,
                        cellRenderer: (params) => {
                            if (!params.value) return '';
                            return `<span class="bob-person-name">${this.escapeHtml(params.value)}</span>`;
                        }
                    },
                    {
                        field: 'member_last_name',
                        headerName: 'Last Name',
                        width: 130,
                        cellRenderer: (params) => {
                            if (!params.value) return '';
                            return `<span class="bob-person-name">${this.escapeHtml(params.value)}</span>`;
                        }
                    },
                    {
                        field: 'carrier',
                        headerName: 'Carrier',
                        width: 150,
                        cellRenderer: (params) => {
                            if (!params.value) return `<div class="flex items-center h-full"><span class="bob-empty-value">—</span></div>`;
                            return `<div class="flex items-center h-full">
                                <span class="bob-carrier-pill">
                                    ${this.escapeHtml(params.value)}
                                </span>
                            </div>`;
                        }
                    },
                    {
                        headerName: 'Source',
                        field: 'match_method',
                        width: 155,
                        cellRenderer: (params) => {
                            const record = params?.data || {};
                            if (!params?.value) return `<div class="flex items-center h-full"><span class="bob-empty-value">—</span></div>`;
                            const method = String(params.value).toLowerCase();

                            // Lock List override — highest priority indicator
                            if (record.override_flag || method.includes('locklist')) {
                                return `<div class="flex items-center h-full">
                                    <span title="Overridden by Lock List — final authority" style="
                                        background: rgba(168,85,247,0.15); color: #c084fc; padding: 3px 10px;
                                        border-radius: 6px; font-size: 10px; font-weight: 700;
                                        border: 1px solid rgba(168,85,247,0.25);
                                        display: inline-flex; align-items: center; gap: 4px;">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                                        </svg>
                                        LOCKED
                                    </span>
                                </div>`;
                            }

                            // IMS match
                            if (method.startsWith('ims:')) {
                                const sub = this.escapeHtml(params.value.split(':')[1] || '');
                                return `<div class="flex items-center h-full">
                                    <span style="background:rgba(99,102,241,0.15);color:#818cf8;padding:3px 10px;border-radius:6px;font-size:10px;font-weight:600;">
                                        IMS · ${sub}
                                    </span>
                                </div>`;
                            }

                            // Health Sherpa match
                            if (method.startsWith('hs:')) {
                                const sub = this.escapeHtml(params.value.split(':')[1] || '');
                                return `<div class="flex items-center h-full">
                                    <span class="bob-badge-matched" style="font-size:10px">
                                        HS · ${sub}
                                    </span>
                                </div>`;
                            }

                            return `<div class="flex items-center h-full">
                                <span style="font-size:10px;color:var(--bob-text-faint)">${this.escapeHtml(params.value)}</span>
                            </div>`;
                        }
                    },
                    {
                        headerName: 'Match Score',
                        field: 'match_confidence',
                        width: 245,
                        cellRenderer: (params) => {
                            const record = params?.data || {};
                            const scoreContext = this.buildScoreContext(record);
                            const reviewState = ['pending', 'flagged'].includes(String(record?.status || '').toLowerCase());
                            const reasonShort = this.escapeHtml(record?.score_reason_short || scoreContext?.reasonShort);

                            if (!params.value) {
                                return `<div class="flex items-center h-full w-full">
                                    <div class="bob-score-wrap">
                                        <div class="bob-score-row">
                                            <span class="bob-empty-value">No match</span>
                                        </div>
                                        <div class="bob-score-track">
                                            <span class="bob-score-fill bob-score-low" style="width: 2%;"></span>
                                        </div>
                                        ${reviewState && reasonShort ? `<div class="bob-score-reason-inline" title="${reasonShort}">${reasonShort}</div>` : ''}
                                    </div>
                                </div>`;
                            }

                            const tone = scoreContext.bucket === 'high' ? 'high' : (scoreContext.bucket === 'mid' ? 'mid' : 'low');
                            const confidenceText = this.escapeHtml(scoreContext.confidenceText.replace(' Confidence', ''));

                            return `<div class="flex items-center h-full w-full">
                                <div class="bob-score-wrap">
                                    <div class="bob-score-row">
                                        <span class="bob-score-value bob-score-${tone}">${confidenceText}</span>
                                    </div>
                                    <div class="bob-score-track">
                                        <span class="bob-score-fill bob-score-${tone}" style="width: ${scoreContext.progressPercent}%;"></span>
                                    </div>
                                    ${reviewState && reasonShort ? `<div class="bob-score-reason-inline" title="${reasonShort}">${reasonShort}</div>` : ''}
                                </div>
                            </div>`;
                        }
                    },
                    {
                        field: 'ims_transaction_id',
                        headerName: 'IMS ID',
                        width: 140,
                        cellRenderer: (params) => {
                            if (!params.value) return `<span class="bob-empty-value">—</span>`;
                            return `<span class="bob-ims-id">${this.escapeHtml(params.value)}</span>`;
                        }
                    },
                    {
                        field: 'aligned_agent_code',
                        headerName: 'Aligned Agent',
                        width: 160,
                        cellRenderer: (params) => {
                            if (!params.value) return `<div class="flex items-center h-full"><span class="bob-empty-value">—</span></div>`;
                            return `<div class="flex items-center h-full">
                                <span class="bob-agent-pill">
                                    <svg class="w-3.5 h-3.5 opacity-80" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                                    ${this.escapeHtml(params.value)}
                                </span>
                            </div>`;
                        }
                    },
                ],
                defaultColDef: {
                    sortable: true,
                    resizable: true,
                    filter: false,
                }
            };

            this.gridApi = agGrid.createGrid(gridDiv, this.gridOptions);
            this.setGridDataSource();

            // Listen to open drawer
            window.addEventListener('open-drawer', (e) => this.openDrawerFor(e.detail));
        },

        setGridDataSource() {
            const pageSize = this.gridOptions.cacheBlockSize || 50;

            const datasource = {
                getRows: (params) => {
                    const startRow = params.startRow ?? 0;
                    const page = Math.floor(startRow / pageSize) + 1;
                    const sortModel = (params.sortModel || [])[0];
                    const sortCol = sortModel ? sortModel.colId : 'created_at';
                    const sortDir = sortModel ? sortModel.sort : 'desc';

                    const url = new URL(window.endpoints.data);
                    url.searchParams.append('page', page);
                    url.searchParams.append('per_page', pageSize);
                    url.searchParams.append('status', this.filterStatus);
                    url.searchParams.append('search', this.searchQuery);
                    url.searchParams.append('sort_by', sortCol);
                    url.searchParams.append('sort_dir', sortDir);

                    fetch(url)
                        .then(response => response.json())
                        .then(data => {
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
                        .catch(error => {
                            console.error(error);
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
                if (typeof this.gridApi.purgeInfiniteCache === 'function') {
                    this.gridApi.purgeInfiniteCache();
                } else if (typeof this.gridApi.refreshInfiniteCache === 'function') {
                    this.gridApi.refreshInfiniteCache();
                }
                this.selectedCount = 0;
            }
        },

        async openDrawerFor(id) {
            let obj = null;
            if (this.gridApi) {
                this.gridApi.forEachNode((node) => {
                    if (node?.data && node.data.id == id) obj = node.data;
                });
            }

            if (!obj) {
                SwalBob.fire('Error', 'Data not found on current page', 'error');
                return;
            }

            try {
                const res = await fetch(window.endpoints.lock(id), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': window.csrfToken, 'Content-Type': 'application/json' }
                });
                const response = await res.json();

                if (!response.success) {
                    SwalBob.fire('Notice', response.message, 'info');
                    return;
                }

                this.activeRecord = {
                    ...obj,
                    scoreContext: this.buildScoreContext(obj),
                };
                this.resolutionData.aligned_agent_code = '';
                this.resolutionData.compensation_type = '';
                this.resolutionData.save_to_locklist = false;
                this.flagValue = obj.flag_value || 'House Open';

                if (this.activeRecord.agent_id) {
                    this.resolutionData.aligned_agent_code = this.activeRecord.agent_id;
                }

                this.drawerOpen = true;
            } catch (err) {
                SwalBob.fire('Error', 'Could not lock record. See console.', 'error');
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
                SwalBob.fire('Warning', 'Please fill out all resolution fields.', 'warning');
                return;
            }

            const recordId = this.activeRecord?.id;
            if (!recordId) return;

            try {
                const res = await fetch(window.endpoints.resolve(recordId), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': window.csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.resolutionData)
                });

                const response = await this.parseApiResponse(res);
                if (res.ok && response.success) {
                    this.activeRecord = null;
                    this.drawerOpen = false;
                    this.refreshGrid();
                } else {
                    SwalBob.fire('Error', this.getApiErrorMessage(res, response, 'Unable to resolve this record.'), 'error');
                }
            } catch (err) {
                console.error(err);
                SwalBob.fire('Error', 'Unable to resolve this record right now. Please try again.', 'error');
            }
        },

        async flagRecord() {
            const recordId = this.activeRecord?.id;
            if (!recordId) return;

            if (!['House Open', 'House Close'].includes(this.flagValue)) {
                SwalBob.fire('Warning', 'Please choose House Open or House Close before flagging.', 'warning');
                return;
            }

            try {
                const res = await fetch(window.endpoints.flag(recordId), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': window.csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ flag_value: this.flagValue })
                });

                const response = await this.parseApiResponse(res);
                if (res.ok && response.success) {
                    this.activeRecord = null;
                    this.drawerOpen = false;
                    this.refreshGrid();
                } else {
                    SwalBob.fire('Error', this.getApiErrorMessage(res, response, 'Unable to flag this record.'), 'error');
                }
            } catch (err) {
                console.error(err);
                SwalBob.fire('Error', 'Unable to flag this record right now. Please try again.', 'error');
            }
        },

        async bulkResolve() {
            if (!window.canBulkApprove) {
                SwalBob.fire('Access Denied', 'You do not have permission to bulk resolve records.', 'error');
                return;
            }

            const confirmRes = await SwalBob.fire({
                title: 'Confirm Bulk Resolution',
                text: `Are you sure you want to resolve ${this.selectedCount} marked records?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, resolve them'
            });
            if (!confirmRes.isConfirmed) return;

            const selectedNodes = this.gridApi.getSelectedNodes();
            const ids = selectedNodes.map(n => n.data.id);

            const { value: code } = await SwalBob.fire({
                title: 'Aligning Agent Code',
                input: 'text',
                inputLabel: 'Enter Aligning Agent Code for all selected:',
                inputPlaceholder: 'Agent Code',
                showCancelButton: true,
                inputValidator: (value) => {
                    if (!value) return 'You need to write something!';
                }
            });
            if (!code) return;

            const { value: compType } = await SwalBob.fire({
                title: 'Compensation Type',
                input: 'radio',
                inputOptions: { 'New': 'New', 'Renewal': 'Renewal' },
                inputValidator: (value) => {
                    if (!value) return 'You need to choose something!';
                },
                showCancelButton: true
            });
            if (!compType) return;

            try {
                const res = await fetch(window.endpoints.bulkResolve, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': window.csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        record_ids: ids,
                        aligned_agent_code: code,
                        compensation_type: compType
                    })
                });

                const response = await this.parseApiResponse(res);
                if (res.ok && response.success) {
                    SwalBob.fire('Success', response.message || 'Done.', 'success');
                    this.refreshGrid();
                } else {
                    SwalBob.fire('Error', this.getApiErrorMessage(res, response, 'Bulk resolution failed.'), 'error');
                }
            } catch (err) {
                console.error(err);
                SwalBob.fire('Error', 'Unable to bulk resolve right now. Please try again.', 'error');
            }
        },

        async bulkPromoteToLocklist() {
            const selectedNodes = this.gridApi.getSelectedNodes();
            const ids = selectedNodes.map(n => n.data.id);

            const confirmRes = await SwalBob.fire({
                title: 'Confirm Bulk Promotion',
                text: `Promote ${ids.length} selected records to the Lock List? This will permanently override future ETL runs for these Contract IDs.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, promote them'
            });
            if (!confirmRes.isConfirmed) return;

            try {
                const res = await fetch(window.endpoints.bulkPromoteToLocklist, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': window.csrfToken, 'Content-Type': 'application/json' },
                    body: JSON.stringify({ record_ids: ids })
                });

                const response = await res.json();
                SwalBob.fire('Success', response.message || 'Done.', 'success');
                this.refreshGrid();
            } catch (err) {
                console.error(err);
                SwalBob.fire('Error', 'Error during bulk promotion.', 'error');
            }
        },

        escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        },

        async parseApiResponse(res) {
            const contentType = String(res.headers.get('content-type') || '').toLowerCase();

            if (contentType.includes('application/json')) {
                return await res.json();
            }

            const text = (await res.text()).trim();
            if (!text) return {};

            try {
                return JSON.parse(text);
            } catch (_e) {
                if (text.startsWith('<!DOCTYPE') || text.startsWith('<html')) {
                    return { message: '' };
                }
                return { message: text };
            }
        },

        getApiErrorMessage(res, payload, fallbackMessage) {
            const status = Number(res?.status || 0);

            if (payload && typeof payload === 'object') {
                if (typeof payload.message === 'string' && payload.message.trim()) {
                    if (payload.message === 'The given data was invalid.' && payload.errors && typeof payload.errors === 'object') {
                        const firstField = Object.keys(payload.errors)[0];
                        const firstError = firstField && Array.isArray(payload.errors[firstField])
                            ? payload.errors[firstField][0]
                            : null;
                        if (firstError) return firstError;
                    }
                    return payload.message;
                }

                if (payload.errors && typeof payload.errors === 'object') {
                    const firstField = Object.keys(payload.errors)[0];
                    const firstError = firstField && Array.isArray(payload.errors[firstField])
                        ? payload.errors[firstField][0]
                        : null;
                    if (firstError) return firstError;
                }
            }

            if (status === 401 || status === 403) {
                return 'You do not have permission to perform this action.';
            }
            if (status === 404) {
                return 'Requested data was not found. Please refresh and try again.';
            }
            if (status === 422) {
                return 'Please correct the highlighted input data and try again.';
            }
            if (status >= 500) {
                return 'Server error occurred while processing your request. Please try again.';
            }

            return fallbackMessage;
        },

        normalizeConfidence(rawScore) {
            const score = Number(rawScore);
            if (!Number.isFinite(score)) return null;
            return Math.max(0, Math.min(100, score));
        },

        humanizeScoreField(field) {
            const key = String(field || '').toLowerCase();
            const map = {
                first_name: 'First Name',
                last_name: 'Last Name',
                full_name: 'Full Name',
                email: 'Email',
                phone: 'Phone',
                dob: 'Date of Birth',
            };

            return map[key] || key.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
        },

        getMethodLabel(methodRaw) {
            const original = String(methodRaw || '').trim();
            if (!original) return 'No deterministic match';

            const method = original.toLowerCase();
            if (method === 'ims:email') return 'IMS Email Exact';
            if (method === 'ims:phone') return 'IMS Phone Exact';
            if (method === 'ims:firstlastname') return 'IMS First + Last Name';
            if (method === 'ims:dob+lastname') return 'IMS DOB + Last Name';
            if (method === 'hs:email') return 'Health Sherpa Email Exact';
            if (method.includes('hs:phone+date')) return 'Health Sherpa Phone + Effective Date';
            if (method === 'locklist override') return 'Lock List Override';
            if (method === 'email') return 'Email Similarity';
            if (method === 'phone') return 'Phone Similarity';
            if (method === 'name') return 'Name Similarity';
            if (method === 'dob') return 'DOB Similarity';

            return original;
        },

        buildDeterministicDiagnostics(record) {
            const checked = [];
            const blocked = [];

            const email = String(record?.member_email || '').trim();
            const phone = String(record?.member_phone || '').replace(/\D/g, '').trim();
            const firstName = String(record?.member_first_name || '').trim();
            const lastName = String(record?.member_last_name || '').trim();
            const dob = String(record?.member_dob || '').trim();
            const effectiveDate = String(record?.effective_date || '').trim();

            if (email) {
                checked.push('Email exact (IMS / Health Sherpa)');
            } else {
                blocked.push('Email is missing');
            }

            if (phone) {
                checked.push('Phone exact (IMS)');
            } else {
                blocked.push('Phone number is missing');
            }

            if (firstName && lastName) {
                checked.push('First + Last Name (IMS)');
            } else if (firstName || lastName) {
                blocked.push('Name rule needs both first and last name');
            } else {
                blocked.push('First and last name are missing');
            }

            if (dob && lastName) {
                checked.push('DOB + Last Name (IMS)');
            } else if (dob && !lastName) {
                blocked.push('DOB is present but last name is missing for DOB+Last Name rule');
            }

            if (phone && effectiveDate) {
                checked.push('Phone + Effective Date (Health Sherpa ±30d)');
            } else if (phone && !effectiveDate) {
                blocked.push('Effective date is missing for Health Sherpa phone+date rule');
            }

            return {
                checked: Array.from(new Set(checked)),
                blocked: Array.from(new Set(blocked)),
            };
        },

        buildSignalSummary(fieldScores) {
            if (!fieldScores || typeof fieldScores !== 'object') {
                return '';
            }

            const signals = Object.entries(fieldScores)
                .map(([field, value]) => {
                    const num = Number(value);
                    if (!Number.isFinite(num)) return null;

                    const pct = Math.max(0, Math.min(100, num <= 1 ? num * 100 : num));
                    return {
                        field: this.humanizeScoreField(field),
                        score: pct,
                    };
                })
                .filter(Boolean)
                .sort((a, b) => b.score - a.score);

            if (!signals.length) {
                return '';
            }

            const strongest = signals[0];
            const weakest = signals[signals.length - 1];

            if (strongest.field === weakest.field) {
                return `Signal strength: ${strongest.field} ${strongest.score.toFixed(2)}%.`;
            }

            return `Strongest signal: ${strongest.field} ${strongest.score.toFixed(2)}%; weakest signal: ${weakest.field} ${weakest.score.toFixed(2)}%.`;
        },

        buildFallbackReasonShort(status, confidence, record) {
            if (status === 'flagged') {
                if (confidence === null) {
                    const diagnostics = this.buildDeterministicDiagnostics(record);
                    if (!diagnostics.checked.length) return 'Flagged: required identifiers are missing for automated matching.';
                    return 'Flagged: no IMS or Health Sherpa record matched the available identifiers.';
                }
                if (confidence < 70) return 'Flagged: low-confidence match needs manual verification.';
                if (confidence < 90) return 'Flagged: below 90% confidence threshold.';
                return 'Flagged by workflow policy for analyst validation.';
            }

            if (status === 'pending') {
                if (confidence === null) {
                    const diagnostics = this.buildDeterministicDiagnostics(record);
                    if (!diagnostics.checked.length) return 'Pending: automated matching could not run due to missing required identifiers.';
                    return 'Pending: no IMS or Health Sherpa record matched the available identifiers.';
                }
                if (confidence < 90) return 'Pending: confidence below auto-review threshold.';
                return 'Pending analyst confirmation before resolution.';
            }

            if (status === 'matched') {
                return 'Matched: confidence is sufficient for suggested alignment.';
            }

            if (status === 'resolved') {
                return 'Resolved after analyst action.';
            }

            return 'Review record details before taking action.';
        },

        buildScoreContext(record) {
            const status = String(record?.status || '').toLowerCase();
            const confidence = this.normalizeConfidence(record?.match_confidence);
            const confidenceText = confidence === null ? 'No confidence score' : `${confidence.toFixed(2)}% Confidence`;
            const diagnostics = this.buildDeterministicDiagnostics(record);

            const bucket = confidence === null
                ? 'none'
                : confidence >= 90
                    ? 'high'
                    : confidence >= 70
                        ? 'mid'
                        : 'low';

            const methodLabel = String(record?.match_method_label || this.getMethodLabel(record?.match_method));
            let signalSummary = String(record?.score_signal_summary || this.buildSignalSummary(record?.field_scores));
            const reasonShort = String(record?.score_reason_short || this.buildFallbackReasonShort(status, confidence, record));

            if (!signalSummary && ['pending', 'flagged'].includes(status)) {
                const parts = [];
                if (diagnostics.checked.length) {
                    parts.push(`Checked keys: ${diagnostics.checked.join(', ')}.`);
                } else {
                    parts.push('No valid deterministic keys were available to run automated matching.');
                }
                if (diagnostics.blocked.length) {
                    parts.push(`Unavailable checks: ${diagnostics.blocked.join('; ')}.`);
                }
                if (record?.contract_id) {
                    parts.push(`Contract ${record.contract_id} has no Lock List override.`);
                }
                signalSummary = parts.join(' ');
            }

            const reason = String(record?.score_reason || [reasonShort, `Method: ${methodLabel}.`, signalSummary].filter(Boolean).join(' '));

            let gradient = 'linear-gradient(90deg, #64748b, #94a3b8)';
            if (status === 'flagged') {
                gradient = 'linear-gradient(90deg, #ef4444, #fb7185)';
            } else if (status === 'pending' && (bucket === 'mid' || bucket === 'low' || bucket === 'none')) {
                gradient = 'linear-gradient(90deg, #f59e0b, #fbbf24)';
            } else if (bucket === 'high') {
                gradient = 'linear-gradient(90deg, #10b981, #34d399)';
            } else if (bucket === 'mid') {
                gradient = 'linear-gradient(90deg, #f59e0b, #fbbf24)';
            } else if (bucket === 'low') {
                gradient = 'linear-gradient(90deg, #ef4444, #fb7185)';
            }

            const progressPercent = confidence === null ? 2 : Math.max(2, confidence);

            return {
                confidence,
                confidenceText,
                bucket,
                methodLabel,
                reasonShort,
                reason,
                signalSummary,
                progressPercent,
                progressStyle: `width: ${progressPercent}%; background: ${gradient};`,
                showScorePanel: confidence !== null || Boolean(methodLabel) || ['pending', 'flagged'].includes(status),
            };
        }
    }));
});
