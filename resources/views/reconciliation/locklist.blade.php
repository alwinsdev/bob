<x-reconciliation-layout>
    @push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-grid.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-theme-alpine.css">
    <style>
        #ll-grid .ll-cell {
            width: 100%;
            display: inline-flex;
            align-items: center;
            min-height: 100%;
            gap: 8px;
        }

        #ll-grid .ll-cell-contract {
            font-family: 'JetBrains Mono', 'SFMono-Regular', ui-monospace, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
            font-size: 13px;
            font-weight: 700;
            color: var(--bob-text-primary);
            letter-spacing: 0.01em;
            font-variant-numeric: tabular-nums;
        }

        #ll-grid .ll-cell-primary {
            font-size: 13px;
            font-weight: 650;
            color: var(--bob-text-secondary);
        }

        #ll-grid .ll-cell-muted {
            font-size: 13px;
            font-weight: 600;
            color: var(--bob-text-muted);
        }

        #ll-grid .ll-cell-date {
            font-size: 12px;
            font-weight: 600;
            color: var(--bob-text-faint);
            font-variant-numeric: tabular-nums;
        }

        #ll-grid .ll-promoted-chip {
            display: inline-flex;
            align-items: center;
            padding: 2px 6px;
            border-radius: 6px;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 0.03em;
            background: rgba(99, 102, 241, 0.15);
            color: #a5b4fc;
            border: 1px solid rgba(129, 140, 248, 0.25);
            text-transform: uppercase;
        }

        #ll-grid .ll-actions-header .ag-header-cell-label {
            justify-content: center;
        }

        #ll-grid .ll-actions-cell {
            justify-content: center !important;
            padding-left: 8px !important;
            padding-right: 8px !important;
        }

        #ll-grid .ll-action-buttons {
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        #ll-grid .ll-action-btn {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: 1px solid transparent;
            background: transparent;
            cursor: pointer;
            transition: transform 120ms ease, border-color 120ms ease, background-color 120ms ease, color 120ms ease;
        }

        #ll-grid .ll-action-btn:hover {
            transform: translateY(-1px);
        }

        #ll-grid .ll-action-btn:focus-visible {
            outline: none;
            box-shadow: 0 0 0 2px rgba(129, 140, 248, 0.35);
        }

        #ll-grid .ll-action-btn svg {
            width: 14px;
            height: 14px;
            pointer-events: none;
        }

        #ll-grid .ll-btn-edit {
            color: #818cf8;
            background: rgba(99, 102, 241, 0.12);
            border-color: rgba(129, 140, 248, 0.2);
        }

        #ll-grid .ll-btn-edit:hover {
            color: #c7d2fe;
            background: rgba(99, 102, 241, 0.2);
            border-color: rgba(129, 140, 248, 0.4);
        }

        #ll-grid .ll-btn-delete {
            color: #f87171;
            background: rgba(239, 68, 68, 0.12);
            border-color: rgba(248, 113, 113, 0.2);
        }

        #ll-grid .ll-btn-delete:hover {
            color: #fca5a5;
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(248, 113, 113, 0.4);
        }

        html.bob-light #ll-grid .ll-cell-contract {
            color: #0f172a;
        }

        html.bob-light #ll-grid .ll-cell-primary {
            color: #1e293b;
        }

        html.bob-light #ll-grid .ll-cell-muted {
            color: #475569;
        }

        html.bob-light #ll-grid .ll-cell-date {
            color: #64748b;
        }

        html.bob-light #ll-grid .ll-promoted-chip {
            color: #3730a3;
            background: rgba(99, 102, 241, 0.1);
            border-color: rgba(99, 102, 241, 0.25);
        }
    </style>
    @endpush

    <x-slot name="pageTitle">Lock List Manager</x-slot>
    <x-slot name="pageSubtitle">Final-Authority overrides — policy_id matches force agent, department &amp; payee</x-slot>

    @php $canWrite = auth()->user()?->hasAnyRole(['admin', 'operations_manager', 'Operations Manager', 'Admin', 'Manager']); @endphp

    <x-slot name="headerActions">
        @if($canWrite)
        <button id="btn-add-entry"
                class="bob-btn-primary flex items-center gap-2 text-xs font-semibold px-4 py-2 rounded-lg">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add Entry
        </button>
        <button id="btn-import"
                class="bob-btn-secondary flex items-center gap-2 text-xs font-semibold px-4 py-2 rounded-lg">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
            </svg>
            Import CSV/Excel
        </button>
        @endif
        <a href="{{ route('reconciliation.locklist.export') }}"
           class="bob-btn-ghost flex items-center gap-2 text-xs font-semibold px-4 py-2 rounded-lg">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
            </svg>
            Export
        </a>
    </x-slot>

    {{-- ── Stats bar ────────────────────────────────────────────────────── --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bob-metric-card">
            <div class="bob-metric-label">Total Entries</div>
            <div class="bob-metric-value" id="ll-total-count">{{ number_format($totalEntries) }}</div>
        </div>
        <div class="bob-metric-card">
            <div class="bob-metric-label">Import Format</div>
            <div class="text-xs font-mono mt-1" style="color: var(--bob-text-muted)">
                CONTRACT_ID · AGENT_NAME · DEPARTMENT_NAME · PAYEE_NAME
            </div>
        </div>
        <div class="bob-metric-card">
            <div class="bob-metric-label">Override Rule</div>
            <div class="text-xs mt-1" style="color: var(--bob-text-muted)">
                Policy ID match → forces Agent, Department &amp; Payee (final authority)
            </div>
        </div>
    </div>

    {{-- ── Toolbar ──────────────────────────────────────────────────────── --}}
    <div class="flex items-center gap-3 mb-4">
        <div class="relative flex-1 max-w-sm">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4" style="color:var(--bob-text-faint)" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
            </svg>
            <input id="ll-search" type="search" placeholder="Search policy ID, agent, department…"
                   class="w-full pl-9 pr-4 py-2 text-sm rounded-lg"
                   style="background:var(--bob-bg-input);border:1px solid var(--bob-border-light);color:var(--bob-text-primary);outline:none;">
        </div>
        <div class="text-xs font-medium" style="color:var(--bob-text-faint)" id="ll-row-count">Loading…</div>
    </div>

    {{-- ── AG Grid ──────────────────────────────────────────────────────── --}}
    <div class="ag-theme-alpine ag-theme-bob rounded-xl overflow-hidden" id="ll-grid"
         style="height:calc(100vh - 320px); border:1px solid var(--bob-border-light);"></div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- ADD / EDIT MODAL                                                      --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div id="ll-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" id="ll-modal-backdrop"></div>
        <div class="relative w-full max-w-md rounded-2xl shadow-2xl overflow-hidden"
             style="background:var(--bob-bg-card);border:1px solid var(--bob-border-medium);">
            <div class="px-6 py-5" style="border-bottom:1px solid var(--bob-border-light);">
                <h3 class="text-base font-bold" style="color:var(--bob-text-primary)" id="ll-modal-title">Add Lock List Entry</h3>
                <p class="text-xs mt-0.5" style="color:var(--bob-text-muted)">Entries override IMS and Health Sherpa assignments.</p>
            </div>
            <form id="ll-form" class="px-6 py-5 space-y-4">
                @csrf
                <input type="hidden" id="ll-entry-id" value="">
                <div>
                    <label class="block text-xs font-semibold mb-1.5" style="color:var(--bob-text-muted)">CONTRACT ID (Policy ID) <span class="text-red-400">*</span></label>
                    <input id="ll-policy-id" name="policy_id" type="text" required
                           placeholder="e.g. POL-00123456"
                           class="w-full px-3 py-2 rounded-lg text-sm"
                           style="background:var(--bob-bg-input);border:1px solid var(--bob-border-medium);color:var(--bob-text-primary);outline:none;">
                    <p class="text-[10px] mt-1" style="color:var(--bob-text-faint)">Must match BOB CONTRACT_ID exactly (case-insensitive).</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1.5" style="color:var(--bob-text-muted)">Agent Name</label>
                    <input id="ll-agent-name" name="agent_name" type="text" placeholder="Full agent name"
                           class="w-full px-3 py-2 rounded-lg text-sm"
                           style="background:var(--bob-bg-input);border:1px solid var(--bob-border-medium);color:var(--bob-text-primary);outline:none;">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1.5" style="color:var(--bob-text-muted)">Department</label>
                    <input id="ll-department" name="department" type="text" placeholder="Department / Group Team Sales"
                           class="w-full px-3 py-2 rounded-lg text-sm"
                           style="background:var(--bob-bg-input);border:1px solid var(--bob-border-medium);color:var(--bob-text-primary);outline:none;">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1.5" style="color:var(--bob-text-muted)">Payee Name</label>
                    <input id="ll-payee-name" name="payee_name" type="text" placeholder="Payee name"
                           class="w-full px-3 py-2 rounded-lg text-sm"
                           style="background:var(--bob-bg-input);border:1px solid var(--bob-border-medium);color:var(--bob-text-primary);outline:none;">
                </div>
                <div id="ll-form-error" class="hidden text-xs font-medium text-red-400 bg-red-500/10 rounded-lg px-3 py-2"></div>
            </form>
            <div class="px-6 py-4 flex items-center justify-end gap-3" style="border-top:1px solid var(--bob-border-light);">
                <button id="ll-modal-cancel" type="button"
                        class="bob-btn-ghost px-4 py-2 text-xs font-semibold rounded-lg">Cancel</button>
                <button id="ll-modal-save" type="button"
                        class="bob-btn-primary px-5 py-2 text-xs font-semibold rounded-lg">Save Entry</button>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- IMPORT MODAL                                                          --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div id="ll-import-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden"
             style="background:var(--bob-bg-card);border:1px solid var(--bob-border-medium);">
            <div class="px-6 py-5" style="border-bottom:1px solid var(--bob-border-light);">
                <h3 class="text-base font-bold" style="color:var(--bob-text-primary)">Import Lock List</h3>
                <p class="text-xs mt-0.5" style="color:var(--bob-text-muted)">Upload a CSV or Excel file. Existing entries are updated; new ones are created.</p>
            </div>
            <div class="px-6 py-5 space-y-4">
                {{-- Required format --}}
                <div class="rounded-xl p-4" style="background:var(--bob-bg-input);border:1px solid var(--bob-border-light);">
                    <p class="text-xs font-bold mb-2" style="color:var(--bob-text-muted)">Required Column Headers (UPPERCASE)</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach(['CONTRACT_ID', 'AGENT_NAME', 'DEPARTMENT_NAME', 'PAYEE_NAME'] as $h)
                        <span class="font-mono text-[11px] px-2.5 py-1 rounded-lg font-semibold"
                              style="background:rgba(99,102,241,0.12);color:#818cf8;border:1px solid rgba(99,102,241,0.2)">{{ $h }}</span>
                        @endforeach
                    </div>
                    <p class="text-[10px] mt-2" style="color:var(--bob-text-faint)">CONTRACT_ID must match BOB data exactly. Empty fields in other columns retain existing values. Supports 'DEPARTMENT_NAME' or 'DEPARTMENT'.</p>
                </div>
                <form id="ll-import-form" enctype="multipart/form-data">
                    @csrf
                    <label class="block text-xs font-semibold mb-2" style="color:var(--bob-text-muted)">Select File</label>
                    <div class="relative flex items-center justify-center rounded-xl cursor-pointer"
                         style="height:100px;border:2px dashed var(--bob-border-medium);background:var(--bob-bg-input);"
                         id="ll-drop-zone">
                        <div class="text-center pointer-events-none">
                            <svg class="w-7 h-7 mx-auto mb-1" style="color:var(--bob-text-faint)" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                            </svg>
                            <p class="text-xs" style="color:var(--bob-text-faint)" id="ll-drop-label">Drop .xlsx, .xls, or .csv here, or <span class="font-semibold" style="color:var(--bob-accent)">browse</span></p>
                        </div>
                        <input type="file" name="import_file" id="ll-import-file" accept=".xlsx,.xls,.csv"
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                    </div>
                </form>
                <div id="ll-import-result" class="hidden text-xs rounded-lg px-3 py-2"></div>
            </div>
            <div class="px-6 py-4 flex items-center justify-end gap-3" style="border-top:1px solid var(--bob-border-light);">
                <button id="ll-import-cancel" class="bob-btn-ghost px-4 py-2 text-xs font-semibold rounded-lg">Cancel</button>
                <button id="ll-import-submit" class="bob-btn-primary px-5 py-2 text-xs font-semibold rounded-lg">
                    <span id="ll-import-submit-label">Import File</span>
                </button>
            </div>
        </div>
    </div>



@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.js"></script>
<script>
(function () {
    'use strict';

    const ROUTES = {
        data:    '{{ route('reconciliation.locklist.data') }}',
        store:   '{{ route('reconciliation.locklist.store') }}',
        export:  '{{ route('reconciliation.locklist.export') }}',
        import:  '{{ route('reconciliation.locklist.import') }}',
        update:  (id) => `/reconciliation/locklist/${id}`,
        destroy: (id) => `/reconciliation/locklist/${id}`,
        promote: (id) => `/reconciliation/records/${id}/promote-to-locklist`,
    };

    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const canWrite = {{ $canWrite ? 'true' : 'false' }};

    // ── AG Grid ──────────────────────────────────────────────────────────────
    let gridApi;
    const colDefs = [
        {
            field: 'policy_id', headerName: 'CONTRACT ID', flex: 1.5, minWidth: 160,
            cellRenderer: (p) => {
                const promoted = p.data?.is_promoted;
                return `<div class="ll-cell ll-cell-contract">
                    <span>${p.value ?? '—'}</span>
                    ${promoted ? '<span class="ll-promoted-chip">Promoted</span>' : ''}
                </div>`;
            }
        },
        { field: 'agent_name',  headerName: 'Agent Name',  flex: 1.5, minWidth: 140,
          cellRenderer: (p) => `<div class="ll-cell ll-cell-primary">${p.value ?? '—'}</div>` },
        { field: 'department',  headerName: 'Department',  flex: 1.5, minWidth: 140,
          cellRenderer: (p) => `<div class="ll-cell ll-cell-muted">${p.value ?? '—'}</div>` },
        { field: 'payee_name',  headerName: 'Payee Name',  flex: 1.5, minWidth: 140,
          cellRenderer: (p) => `<div class="ll-cell ll-cell-primary">${p.value ?? '—'}</div>` },
        { field: 'updated_at',  headerName: 'Last Updated', flex: 1, minWidth: 130,
          cellRenderer: (p) => `<div class="ll-cell ll-cell-date">${p.value ?? '—'}</div>` },
        canWrite ? {
            headerName: 'Actions', width: 104, sortable: false, filter: false,
            headerClass: 'll-actions-header',
            cellClass: 'll-actions-cell',
            cellRenderer: (p) => {
                if (!p.data) return '';
                const rowStr = JSON.stringify(p.data).replace(/'/g, "&apos;");
                return `<div class="ll-action-buttons">
                    <button data-id="${p.data.id}" data-row='${rowStr}' class="ll-action-btn ll-btn-edit" title="Edit entry" aria-label="Edit entry">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.688-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.805.805-2.685a4.5 4.5 0 011.13-1.897l12.677-12.686z" />
                        </svg>
                    </button>
                    <button data-id="${p.data.id}" class="ll-action-btn ll-btn-delete" title="Delete entry" aria-label="Delete entry">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 7.5h12m-9.75 0v10.125m3-10.125v10.125m3-10.125v10.125M9 7.5V5.625A1.875 1.875 0 0110.875 3.75h2.25A1.875 1.875 0 0115 5.625V7.5m-9 0h12l-.75 12A2.25 2.25 0 0115.007 21h-6.014a2.25 2.25 0 01-2.243-1.5L6 7.5z" />
                        </svg>
                    </button>
                </div>`;
            }
        } : null,
    ].filter(Boolean);

    const datasource = {
        getRows(params) {
            const search = document.getElementById('ll-search').value;
            fetch(`${ROUTES.data}?startRow=${params.startRow}&endRow=${params.endRow}&search=${encodeURIComponent(search)}`)
                .then(r => r.json())
                .then(d => {
                    document.getElementById('ll-row-count').textContent = `${d.totalCount.toLocaleString()} entries`;
                    document.getElementById('ll-total-count').textContent = d.totalCount.toLocaleString();
                    params.successCallback(d.rows, d.totalCount);
                })
                .catch(() => params.failCallback());
        }
    };

    agGrid.createGrid(document.getElementById('ll-grid'), {
        columnDefs: colDefs,
        rowModelType: 'infinite',
        datasource,
        cacheBlockSize: 100,
        maxBlocksInCache: 10,
        rowHeight: 52,
        headerHeight: 42,
        defaultColDef: {
            sortable: true, resizable: true, filter: false,
            headerClass: 'ag-header-cell-label',
        },
        onGridReady(p) { gridApi = p.api; },
        onCellClicked(e) {
            const btn = e.event.target.closest('[data-id]');
            if (!btn) return;
            const id = btn.dataset.id;
            if (btn.classList.contains('ll-btn-edit')) openEdit(JSON.parse(btn.dataset.row));
            if (btn.classList.contains('ll-btn-delete')) confirmDelete(id);
        },
        getRowId: (p) => String(p.data.id),
    });

    // ── Search ───────────────────────────────────────────────────────────────
    let searchTimer;
    document.getElementById('ll-search').addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => gridApi?.refreshInfiniteCache(), 350);
    });

    // ── Add Modal ────────────────────────────────────────────────────────────
    const modal        = document.getElementById('ll-modal');
    const modalTitle   = document.getElementById('ll-modal-title');
    const formError    = document.getElementById('ll-form-error');
    const entryIdInput = document.getElementById('ll-entry-id');

    function openAdd() {
        modalTitle.textContent = 'Add Lock List Entry';
        entryIdInput.value = '';
        document.getElementById('ll-policy-id').value   = '';
        document.getElementById('ll-agent-name').value  = '';
        document.getElementById('ll-department').value  = '';
        document.getElementById('ll-payee-name').value  = '';
        document.getElementById('ll-policy-id').disabled = false;
        hideError();
        modal.classList.remove('hidden');
    }

    function openEdit(row) {
        modalTitle.textContent = 'Edit Lock List Entry';
        entryIdInput.value = row.id;
        document.getElementById('ll-policy-id').value   = row.policy_id;
        document.getElementById('ll-agent-name').value  = row.agent_name  ?? '';
        document.getElementById('ll-department').value  = row.department  ?? '';
        document.getElementById('ll-payee-name').value  = row.payee_name  ?? '';
        document.getElementById('ll-policy-id').disabled = true; // can't change policy_id on edit
        hideError();
        modal.classList.remove('hidden');
    }

    function closeModal() { modal.classList.add('hidden'); }
    function showError(msg) { formError.textContent = msg; formError.classList.remove('hidden'); }
    function hideError()    { formError.classList.add('hidden'); }

    document.getElementById('btn-add-entry')?.addEventListener('click', openAdd);
    document.getElementById('ll-modal-cancel').addEventListener('click', closeModal);
    document.getElementById('ll-modal-backdrop').addEventListener('click', closeModal);

    document.getElementById('ll-modal-save').addEventListener('click', async () => {
        hideError();
        const entryId  = entryIdInput.value;
        const isEdit   = !!entryId;
        const payload  = {
            policy_id:  document.getElementById('ll-policy-id').value.trim(),
            agent_name: document.getElementById('ll-agent-name').value.trim(),
            department: document.getElementById('ll-department').value.trim(),
            payee_name: document.getElementById('ll-payee-name').value.trim(),
        };

        const url    = isEdit ? ROUTES.update(entryId) : ROUTES.store;
        const method = isEdit ? 'PUT' : 'POST';

        try {
            const res  = await fetch(url, {
                method,
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await res.json();
            if (!res.ok) { showError(data.message ?? 'Validation failed.'); return; }
            closeModal();
            gridApi?.refreshInfiniteCache();
            SwalBob.fire({ title: 'Success', text: data.message, icon: 'success', toast: true, position: 'bottom-end', timer: 3000, showConfirmButton: false });
        } catch (e) {
            showError('Network error. Please try again.');
        }
    });

    // ── Delete ───────────────────────────────────────────────────────────────
    async function confirmDelete(id) {
        const confirmRes = await SwalBob.fire({
            title: 'Confirm Deletion',
            text: 'Remove this entry from the Lock List? Future ETL runs will no longer override this contract.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, remove it'
        });
        if (!confirmRes.isConfirmed) return;

        const res  = await fetch(ROUTES.destroy(id), {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        const data = await res.json();
        gridApi?.refreshInfiniteCache();
        SwalBob.fire({ title: res.ok ? 'Success' : 'Error', text: data.message, icon: res.ok ? 'success' : 'error', toast: true, position: 'bottom-end', timer: 3000, showConfirmButton: false });
    }

    // ── Import Modal ─────────────────────────────────────────────────────────
    const importModal  = document.getElementById('ll-import-modal');
    const importResult = document.getElementById('ll-import-result');

    document.getElementById('btn-import')?.addEventListener('click', () => {
        importModal.classList.remove('hidden');
        importResult.classList.add('hidden');
    });
    document.getElementById('ll-import-cancel').addEventListener('click', () => importModal.classList.add('hidden'));

    document.getElementById('ll-import-file').addEventListener('change', (e) => {
        const file = e.target.files[0];
        document.getElementById('ll-drop-label').textContent = file ? file.name : 'Drop .xlsx, .xls, or .csv here';
    });

    document.getElementById('ll-import-submit').addEventListener('click', async () => {
        const file = document.getElementById('ll-import-file').files[0];
        if (!file) { SwalBob.fire({ title: 'Error', text: 'Please select a file first.', icon: 'error', toast: true, position: 'bottom-end', timer: 3000, showConfirmButton: false }); return; }

        const label = document.getElementById('ll-import-submit-label');
        label.textContent = 'Importing…';

        const formData = new FormData(document.getElementById('ll-import-form'));
        try {
            const res  = await fetch(ROUTES.import, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: formData,
            });
            const data = await res.json();
            importResult.textContent = data.message;
            importResult.className   = `text-xs rounded-lg px-3 py-2 ${res.ok ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400'}`;
            importResult.classList.remove('hidden');
            if (res.ok) gridApi?.refreshInfiniteCache();
        } catch (e) {
            importResult.textContent = 'Import failed. Check your file format.';
            importResult.className   = 'text-xs rounded-lg px-3 py-2 bg-red-500/10 text-red-400';
            importResult.classList.remove('hidden');
        } finally {
            label.textContent = 'Import File';
        }
    });



    // Auto-close modal on Escape
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') { closeModal(); importModal.classList.add('hidden'); } });
})();
</script>
@endpush
</x-reconciliation-layout>
