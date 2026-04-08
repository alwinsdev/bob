<x-reconciliation-layout>
    @push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-grid.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-theme-alpine.css">
    @endpush

    <x-slot name="pageTitle">Batch Results</x-slot>
    <x-slot name="pageSubtitle">
        Run #{{ $batch->id }} &mdash; {{ $batch->created_at->format('M d, Y · h:i A') }}
        &mdash; uploaded by {{ $batch->uploadedBy?->name ?? 'System' }}
    </x-slot>

    @php $canPromote = auth()->user()?->hasAnyRole(['admin', 'operations_manager', 'Operations Manager', 'Admin', 'Manager']); @endphp

    <x-slot name="headerActions">
        <a href="{{ route('reconciliation.home') }}"
           class="bob-btn-ghost flex items-center gap-2 text-xs font-semibold px-4 py-2 rounded-lg">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
            </svg>
            Back to Hub
        </a>
        @can('reconciliation.export.download')
            @if($batch->hasOutput())
            <a href="{{ route('reconciliation.batches.download', $batch) }}"
               class="bob-btn-primary flex items-center gap-2 text-xs font-semibold px-4 py-2 rounded-lg">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                </svg>
                Download Excel Output
            </a>
            @endif
        @endcan
    </x-slot>

    {{-- ── Match summary cards ───────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        {{-- Total --}}
        <div class="bob-metric-card lg:col-span-1">
            <div class="bob-metric-label">Total Records</div>
            <div class="bob-metric-value text-2xl">{{ number_format($totalRecords) }}</div>
        </div>

        {{-- IMS --}}
        <div class="bob-metric-card cursor-pointer filter-card" data-source="ims"
             style="border-left:3px solid #6366f1;">
            <div class="bob-metric-label flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-indigo-400 inline-block"></span>IMS Matched
            </div>
            <div class="bob-metric-value text-indigo-400 text-2xl">{{ number_format($imsMatched) }}</div>
            <div class="text-[10px] mt-1" style="color:var(--bob-text-faint)">
                {{ $processed > 0 ? round(($imsMatched / $processed) * 100) : 0 }}% of processed
            </div>
        </div>

        {{-- Health Sherpa --}}
        <div class="bob-metric-card cursor-pointer filter-card" data-source="hs"
             style="border-left:3px solid #10b981;">
            <div class="bob-metric-label flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-400 inline-block"></span>Health Sherpa
            </div>
            <div class="bob-metric-value text-emerald-400 text-2xl">{{ number_format($hsMatched) }}</div>
            <div class="text-[10px] mt-1" style="color:var(--bob-text-faint)">
                {{ $processed > 0 ? round(($hsMatched / $processed) * 100) : 0 }}% of processed
            </div>
        </div>

        {{-- Locklist --}}
        <div class="bob-metric-card cursor-pointer filter-card" data-source="locklist"
             style="border-left:3px solid #f59e0b;">
            <div class="bob-metric-label flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-amber-400 inline-block"></span>Lock List Override
            </div>
            <div class="bob-metric-value text-amber-400 text-2xl">{{ number_format($locklistCount) }}</div>
            <div class="text-[10px] mt-1" style="color:var(--bob-text-faint)">Final authority applied</div>
        </div>

        {{-- Unmatched --}}
        <div class="bob-metric-card cursor-pointer filter-card" data-source="unmatched"
             style="border-left:3px solid #ef4444;">
            <div class="bob-metric-label flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-red-400 inline-block"></span>Unmatched
            </div>
            <div class="bob-metric-value text-red-400 text-2xl">{{ number_format($unmatched) }}</div>
            <div class="text-[10px] mt-1" style="color:var(--bob-text-faint)">Needs manual review</div>
        </div>
    </div>

    {{-- ── Filter progress bar ──────────────────────────────────────────── --}}
    @if($processed > 0)
    <div class="mb-6 rounded-xl p-4" style="background:var(--bob-bg-card);border:1px solid var(--bob-border-light);">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold" style="color:var(--bob-text-muted)">Match Breakdown</span>
            <span class="text-xs" style="color:var(--bob-text-faint)">{{ number_format($processed) }} processed</span>
        </div>
        <div class="h-2.5 rounded-full overflow-hidden flex gap-0.5" style="background:var(--bob-border-light);">
            @php
                $imsW  = $processed > 0 ? round(($imsMatched / $processed) * 100) : 0;
                $hsW   = $processed > 0 ? round(($hsMatched / $processed) * 100) : 0;
                $llW   = $processed > 0 ? round(($locklistCount / $processed) * 100) : 0;
                $unmW  = max(0, 100 - $imsW - $hsW - $llW);
            @endphp
            @if($imsW > 0)  <div class="h-full rounded-l-full transition-all" style="width:{{ $imsW }}%;background:#6366f1;" title="IMS {{ $imsW }}%"></div> @endif
            @if($hsW > 0)   <div class="h-full transition-all" style="width:{{ $hsW }}%;background:#10b981;" title="HS {{ $hsW }}%"></div> @endif
            @if($llW > 0)   <div class="h-full transition-all" style="width:{{ $llW }}%;background:#f59e0b;" title="Locklist {{ $llW }}%"></div> @endif
            @if($unmW > 0)  <div class="h-full rounded-r-full transition-all" style="width:{{ $unmW }}%;background:#ef4444;" title="Unmatched {{ $unmW }}%"></div> @endif
        </div>
        <div class="flex flex-wrap items-center gap-4 mt-2">
            <span class="text-[10px] flex items-center gap-1"><span class="w-2 h-2 rounded-full inline-block bg-indigo-400"></span>IMS {{ $imsW }}%</span>
            <span class="text-[10px] flex items-center gap-1"><span class="w-2 h-2 rounded-full inline-block bg-emerald-400"></span>HS {{ $hsW }}%</span>
            <span class="text-[10px] flex items-center gap-1"><span class="w-2 h-2 rounded-full inline-block bg-amber-400"></span>Locklist {{ $llW }}%</span>
            <span class="text-[10px] flex items-center gap-1"><span class="w-2 h-2 rounded-full inline-block bg-red-400"></span>Unmatched {{ $unmW }}%</span>
        </div>
    </div>
    @endif

    {{-- ── Toolbar ──────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center gap-3 mb-4">
        <div class="relative flex-1 min-w-0 sm:min-w-48 max-w-sm">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4" style="color:var(--bob-text-faint)" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
            </svg>
            <input id="br-search" type="search" placeholder="Search contract ID, member, agent…"
                   class="w-full pl-9 pr-4 py-2 text-sm rounded-lg"
                   style="background:var(--bob-bg-input);border:1px solid var(--bob-border-light);color:var(--bob-text-primary);outline:none;">
        </div>

        {{-- Source filter pills --}}
        <div class="flex flex-wrap items-center gap-1.5 text-xs">
            <button class="br-source-pill active" data-source="">All Sources</button>
            <button class="br-source-pill" data-source="ims" style="--pill-color:#6366f1">IMS</button>
            <button class="br-source-pill" data-source="hs" style="--pill-color:#10b981">Health Sherpa</button>
            <button class="br-source-pill" data-source="locklist" style="--pill-color:#f59e0b">Lock List</button>
            <button class="br-source-pill" data-source="unmatched" style="--pill-color:#ef4444">Unmatched</button>
        </div>

        <div class="w-full sm:w-auto sm:ml-auto text-xs font-medium" style="color:var(--bob-text-faint)" id="br-row-count">Loading…</div>
    </div>

    {{-- ── AG Grid ──────────────────────────────────────────────────────── --}}
    <div class="ag-theme-alpine ag-theme-bob rounded-xl overflow-hidden" id="br-grid"
         style="height:calc(100vh - 430px); min-height:400px; border:1px solid var(--bob-border-light);"></div>



@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.js"></script>
<style>
.br-source-pill {
    padding: 4px 10px; border-radius: 20px; font-weight: 600; cursor: pointer; transition: all .15s;
    background: var(--bob-bg-input); border: 1px solid var(--bob-border-light); color: var(--bob-text-muted);
}
.br-source-pill.active, .br-source-pill:hover {
    background: var(--pill-color, var(--bob-accent));
    border-color: var(--pill-color, var(--bob-accent));
    color: #fff;
    opacity: 1;
}
.filter-card:hover { transform: translateY(-1px); transition: transform .15s; }
.filter-card.selected { box-shadow: 0 0 0 2px var(--bob-accent); }
</style>
<script>
(function () {
    'use strict';

    const BATCH_ID = '{{ $batch->id }}';
    const DATA_URL = '{{ route('reconciliation.batches.results.data', $batch) }}';
    const PROMOTE_URL = (id) => `/reconciliation/records/${id}/promote-to-locklist`;
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const canPromote = {{ $canPromote ? 'true' : 'false' }};

    let currentSource = '';
    let currentStatus = '';
    let gridApi;

    // Source badge HTML
    function sourceBadge(method) {
        if (!method) return `<span class="bob-badge-flagged" style="font-size:10px">No Match</span>`;
        const m = method.toLowerCase();
        if (m.startsWith('ims:'))             return `<span class="bob-badge-matched" style="background:rgba(99,102,241,.15);color:#818cf8;font-size:10px">IMS · ${method.split(':')[1]}</span>`;
        if (m.startsWith('hs:'))              return `<span class="bob-badge-matched" style="font-size:10px">HS · ${method.split(':')[1]}</span>`;
        if (m.includes('locklist'))           return `<span style="background:rgba(245,158,11,.15);color:#fbbf24;font-size:10px;padding:2px 8px;border-radius:4px;font-weight:600">Lock List</span>`;
        return `<span style="font-size:10px;color:var(--bob-text-faint)">${method}</span>`;
    }

    function confidenceBadge(pct, bucket) {
        if (pct === null || pct === undefined) return `<span class="text-xs" style="color:var(--bob-text-faint)">—</span>`;
        const cls = bucket === 'high' ? 'bob-score-high' : bucket === 'medium' ? 'bob-score-mid' : 'bob-score-low';
        return `<span class="bob-score-value ${cls}">${Math.round(pct)}%</span>`;
    }

    const colDefs = [
        { field: 'contract_id',     headerName: 'Contract ID',   flex: 1.2, minWidth: 130,
          cellRenderer: p => `<span class="font-mono text-xs">${p.value ?? '—'}</span>` },
        { field: 'member_name',     headerName: 'Member',        flex: 1.4, minWidth: 140 },
        { field: 'carrier',         headerName: 'Carrier',       flex: 1,   minWidth: 100 },
        { field: 'effective_date',  headerName: 'Eff. Date',     flex: 0.9, minWidth: 100,
          cellRenderer: p => `<span class="text-xs" style="color:var(--bob-text-faint)">${p.value ?? '—'}</span>` },
        { field: 'match_method_label', headerName: 'Match Source', flex: 1.5, minWidth: 160,
          cellRenderer: p => sourceBadge(p.data?.match_method) },
        { field: 'match_confidence', headerName: 'Confidence',   flex: 0.8, minWidth: 90,
          cellRenderer: p => confidenceBadge(p.value, p.data?.match_bucket) },
        { field: 'aligned_agent',  headerName: 'Agent',          flex: 1.3, minWidth: 130 },
        { field: 'department',     headerName: 'Department',     flex: 1.3, minWidth: 130 },
        { field: 'payee_name',     headerName: 'Payee',          flex: 1.2, minWidth: 120 },
        { field: 'status',         headerName: 'Status',         flex: 0.8, minWidth: 90,
          cellRenderer: p => {
            const cls = {resolved:'bob-badge-matched', pending:'bob-badge-pending', matched:'bob-badge-matched', flagged:'bob-badge-flagged'}[p.value] ?? 'bob-badge-pending';
            return `<span class="${cls}" style="font-size:10px">${p.value ?? 'pending'}</span>`;
          }},
        { headerName: 'Override', flex: 0.7, minWidth: 80, sortable: false,
          cellRenderer: p => {
            if (!p.data?.override_flag) return `<span class="text-[10px]" style="color:var(--bob-text-faint)">—</span>`;
            return `<span title="Overridden by Lock List — final authority" style="
                background:rgba(168,85,247,0.15);color:#c084fc;padding:2px 8px;
                border-radius:5px;font-size:10px;font-weight:700;
                border:1px solid rgba(168,85,247,0.25);
                display:inline-flex;align-items:center;gap:3px;">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                </svg>
                LOCKED
            </span>`;
          }},
        canPromote ? {
            headerName: 'Promote', width: 110, sortable: false,
            cellRenderer: p => {
                if (!p.data?.can_promote) return `<span class="text-[10px]" style="color:var(--bob-text-faint)">N/A</span>`;
                return `<button data-id="${p.data.id}" class="br-btn-promote text-[11px] font-semibold px-2.5 py-1 rounded-lg"
                                style="background:rgba(245,158,11,.12);color:#fbbf24;border:1px solid rgba(245,158,11,.2)">
                            ⭐ Promote
                        </button>`;
            }
        } : null,
    ].filter(Boolean);

    const datasource = {
        getRows(params) {
            const search = document.getElementById('br-search').value;
            const url = `${DATA_URL}?startRow=${params.startRow}&endRow=${params.endRow}`
                      + `&source=${encodeURIComponent(currentSource)}`
                      + `&status=${encodeURIComponent(currentStatus)}`
                      + `&search=${encodeURIComponent(search)}`;
            fetch(url)
                .then(r => r.json())
                .then(d => {
                    document.getElementById('br-row-count').textContent = `${d.totalCount.toLocaleString()} records`;
                    params.successCallback(d.rows, d.totalCount);
                })
                .catch(() => params.failCallback());
        }
    };

    agGrid.createGrid(document.getElementById('br-grid'), {
        columnDefs: colDefs,
        rowModelType: 'infinite',
        datasource,
        cacheBlockSize: 100,
        rowHeight: 48,
        headerHeight: 40,
        defaultColDef: { sortable: false, resizable: true },
        onGridReady(p) { gridApi = p.api; },
        onCellClicked(e) {
            const btn = e.event.target.closest('.br-btn-promote');
            if (!btn) return;
            promoteRecord(btn.dataset.id);
        },
        getRowId: p => String(p.data.id),
    });

    // ── Search ───────────────────────────────────────────────────────────────
    let searchTimer;
    document.getElementById('br-search').addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => gridApi?.refreshInfiniteCache(), 350);
    });

    // ── Source filter pills ───────────────────────────────────────────────────
    document.querySelectorAll('.br-source-pill').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.br-source-pill').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentSource = btn.dataset.source;
            gridApi?.refreshInfiniteCache();
        });
    });

    // ── Metric card click → filter ────────────────────────────────────────────
    document.querySelectorAll('.filter-card').forEach(card => {
        card.addEventListener('click', () => {
            const source = card.dataset.source;
            currentSource = source;
            document.querySelectorAll('.br-source-pill').forEach(b => {
                b.classList.toggle('active', b.dataset.source === source);
            });
            gridApi?.refreshInfiniteCache();
        });
    });

    // ── Promote to Lock List ─────────────────────────────────────────────────
    async function promoteRecord(id) {
        const confirmRes = await SwalBob.fire({
            title: 'Confirm Promotion',
            text: 'Promote this record to the Lock List? Future ETL runs for this Policy ID will use these agent/department/payee values.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, promote it'
        });
        if (!confirmRes.isConfirmed) return;

        try {
            const res  = await fetch(PROMOTE_URL(id), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (res.ok) {
                SwalBob.fire({ title: 'Success', text: data.message, icon: 'success', toast: true, position: 'bottom-end', timer: 3000, showConfirmButton: false });
                gridApi?.refreshInfiniteCache();
            } else {
                SwalBob.fire('Error', data.message, 'error');
            }
        } catch (e) {
            SwalBob.fire('Error', 'Network error. Please try again.', 'error');
        }
    }

})();
</script>
@endpush
</x-reconciliation-layout>
