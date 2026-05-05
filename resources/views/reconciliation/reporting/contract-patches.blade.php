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

        {{-- ── PREMIUM EMPTY STATE ─────────────────────────────────────────── --}}
        <div x-show="parentBatchOptions.length === 0" x-cloak>
            <div class="bob-glass-panel p-10 text-center relative overflow-hidden" style="background: linear-gradient(145deg, rgba(15,23,42,0.65) 0%, rgba(30,41,59,0.4) 100%); border: 1px solid rgba(99,102,241,0.18);">
                <div class="absolute inset-0 pointer-events-none" style="background: radial-gradient(circle at top center, rgba(99,102,241,0.08) 0%, transparent 60%);"></div>
                <div class="relative z-10 max-w-md mx-auto">
                    <div class="w-16 h-16 mx-auto rounded-2xl flex items-center justify-center mb-4" style="background: rgba(99,102,241,0.12); box-shadow: inset 0 2px 12px rgba(99,102,241,0.18);">
                        <svg class="w-7 h-7 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-black tracking-tight text-white">No Commission Adjustment runs yet</h3>
                    <p class="text-xs text-slate-400 mt-2 leading-relaxed">Once a Payee Back-Flow Analysis completes from the Upload page, every cascade decision will land here with a full diagnosis trail.</p>
                    @can('reconciliation.etl.run')
                        <a href="{{ route('reconciliation.upload.index') }}" class="bob-btn-primary inline-flex items-center gap-2 mt-5 text-xs">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            Start a Payee Back-Flow Analysis
                        </a>
                    @endcan
                </div>
            </div>
        </div>

        <div x-show="parentBatchOptions.length > 0" x-cloak class="space-y-4">
                {{-- ── PREMIUM KPI STRIP (4 cards: Resolved + Lock Override + Unresolved + Failed) ─────────── --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <button type="button" @click="setStatusFilter('all')" class="bob-kpi-tile text-left group transition-all duration-200" :class="statusFilter === 'all' ? 'ring-2 ring-indigo-400/50' : 'hover:ring-1 hover:ring-indigo-400/30'" style="background: linear-gradient(145deg, rgba(99,102,241,0.10), rgba(139,92,246,0.06)); border:1px solid rgba(99,102,241,0.22); padding:14px 16px; border-radius:12px; position:relative; overflow:hidden;">
                        <div class="text-[9px] font-black uppercase tracking-widest text-indigo-300/80">Total Rows</div>
                        <div class="text-2xl font-black text-white mt-1.5 tabular-nums" x-text="(metrics.patched + metrics.skipped + metrics.failed).toLocaleString()">0</div>
                        <div class="text-[10px] text-indigo-200/70 mt-0.5">All cascade decisions in scope.</div>
                        <div class="absolute right-3 top-3 text-indigo-300/60 group-hover:text-indigo-300 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M3 12h18M3 18h18"/></svg>
                        </div>
                    </button>

                    <button type="button" @click="setStatusFilter('resolved')" class="bob-kpi-tile text-left group transition-all duration-200" :class="statusFilter === 'resolved' ? 'ring-2 ring-emerald-400/50' : 'hover:ring-1 hover:ring-emerald-400/30'" style="background: linear-gradient(145deg, rgba(16,185,129,0.12), rgba(20,184,166,0.06)); border:1px solid rgba(16,185,129,0.22); padding:14px 16px; border-radius:12px; position:relative; overflow:hidden;">
                        <div class="text-[9px] font-black uppercase tracking-widest text-emerald-300/80">Resolved</div>
                        <div class="text-2xl font-black text-white mt-1.5 tabular-nums" x-text="(statusDistribution.resolved || 0).toLocaleString()">0</div>
                        <div class="text-[10px] text-emerald-200/70 mt-0.5">Payee successfully attributed.</div>
                        <div class="absolute right-3 top-3 text-emerald-300/60 group-hover:text-emerald-300 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </div>
                    </button>

                    <button type="button" @click="setStatusFilter('lock_override')" class="bob-kpi-tile text-left group transition-all duration-200" :class="statusFilter === 'lock_override' ? 'ring-2 ring-rose-400/50' : 'hover:ring-1 hover:ring-rose-400/30'" style="background: linear-gradient(145deg, rgba(244,63,94,0.10), rgba(236,72,153,0.06)); border:1px solid rgba(244,63,94,0.22); padding:14px 16px; border-radius:12px; position:relative; overflow:hidden;">
                        <div class="text-[9px] font-black uppercase tracking-widest text-rose-300/80">Lock Override</div>
                        <div class="text-2xl font-black text-white mt-1.5 tabular-nums" x-text="(statusDistribution.lock_override || 0).toLocaleString()">0</div>
                        <div class="text-[10px] text-rose-200/70 mt-0.5">Lock list bypassed cascade.</div>
                        <div class="absolute right-3 top-3 text-rose-300/60 group-hover:text-rose-300 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                    </button>

                    <button type="button" @click="setStatusFilter('unresolved')" class="bob-kpi-tile text-left group transition-all duration-200" :class="statusFilter === 'unresolved' ? 'ring-2 ring-amber-400/50' : 'hover:ring-1 hover:ring-amber-400/30'" style="background: linear-gradient(145deg, rgba(245,158,11,0.10), rgba(217,119,6,0.06)); border:1px solid rgba(245,158,11,0.22); padding:14px 16px; border-radius:12px; position:relative; overflow:hidden;">
                        <div class="text-[9px] font-black uppercase tracking-widest text-amber-300/80">Unresolved</div>
                        <div class="text-2xl font-black text-white mt-1.5 tabular-nums" x-text="((statusDistribution.unresolved || 0) + (statusDistribution.failed || 0)).toLocaleString()">0</div>
                        <div class="text-[10px] text-amber-200/70 mt-0.5">Identity gap or system error.</div>
                        <div class="absolute right-3 top-3 text-amber-300/60 group-hover:text-amber-300 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                    </button>
                </div>

                {{-- ── SOURCE DISTRIBUTION STRIP — visual breakdown by cascade source ─────── --}}
                <div class="bob-glass-panel p-4" x-show="sourceDistribution.length > 0" x-cloak>
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Cascade Source Distribution</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Where each row was resolved across the 5-source cascade. Click a source to filter.</div>
                        </div>
                        <button type="button" x-show="sourceFilter !== ''" @click="setSourceFilter('')" class="text-[10px] font-bold uppercase tracking-widest text-slate-400 hover:text-white px-2 py-1 rounded transition-colors" style="background: rgba(99,102,241,0.10);">
                            ✕ Clear source filter
                        </button>
                    </div>

                    {{-- Stacked horizontal bar --}}
                    <div class="flex h-3 rounded-full overflow-hidden" style="background: rgba(15,23,42,0.6); border:1px solid rgba(99,102,241,0.12);">
                        <template x-for="entry in sourceDistribution" :key="entry.source">
                            <div class="h-full transition-all duration-300 cursor-pointer hover:brightness-125"
                                :style="`width: ${sourcePercent(entry)}%; background: ${sourceColor(entry.source)};`"
                                @click="setSourceFilter(entry.source)"
                                :title="`${entry.source}: ${entry.count.toLocaleString()} rows (${sourcePercent(entry).toFixed(1)}%)`"></div>
                        </template>
                    </div>

                    {{-- Per-source chips with counts --}}
                    <div class="flex flex-wrap items-center gap-2 mt-3">
                        <template x-for="entry in sourceDistribution" :key="entry.source">
                            <button type="button" @click="setSourceFilter(entry.source)"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-bold transition-all duration-150"
                                :class="sourceFilter === entry.source ? 'ring-2 scale-[1.02]' : 'opacity-90 hover:opacity-100 hover:scale-[1.02]'"
                                :style="`background: ${sourceBg(entry.source)}; color: ${sourceFg(entry.source)}; border: 1px solid ${sourceBorder(entry.source)}; --tw-ring-color: ${sourceFg(entry.source)};`">
                                <span class="w-1.5 h-1.5 rounded-full" :style="`background: ${sourceColor(entry.source)};`"></span>
                                <span class="uppercase tracking-wider" x-text="entry.source"></span>
                                <span class="font-mono opacity-80 tabular-nums" x-text="entry.count.toLocaleString()"></span>
                                <span class="opacity-60 text-[9px]" x-text="`${sourcePercent(entry).toFixed(1)}%`"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="bob-glass-panel bob-grid-shell overflow-hidden">
                    <div class="contract-filter-toolbar bob-grid-toolbar px-4 py-3 border-b border-slate-700/50 space-y-3"
                        style="background: var(--bob-toolbar-bg)">

                        {{-- Row 1: Batch selectors --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Parent Standard Batch</label>
                                <div class="relative">
                                    <select class="bob-search-input contract-filter-select h-10 w-full" x-model="parentBatchId" @change="onParentBatchChange($event)">
                                        <template x-for="option in parentBatchOptions" :key="option.id">
                                            <option :value="option.id" x-text="option.label"></option>
                                        </template>
                                    </select>
                                    <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Adjustment Run</label>
                                <div class="relative">
                                    <select class="bob-search-input contract-filter-select h-10 w-full" x-model="patchBatchId" @change="onPatchBatchChange($event)">
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
                        </div>

                        {{-- Row 2: Search + Refresh --}}
                        <div class="flex items-end gap-3">
                            <div class="relative flex-1 min-w-0">
                                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Contract / Agent / Payee / Source / Diagnosis</label>
                                <input type="text"
                                    x-model="searchQuery"
                                    @input.debounce.350ms="onSearch"
                                    class="bob-search-input h-10 w-full"
                                    placeholder="Filter by contract, agent, payee, source (IMS / Final BOB / etc.) or diagnosis text..." />
                            </div>
                            <button class="bob-btn-ghost h-10 shrink-0" @click="loadData">
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

            {{-- ═══════════════════════════════════════════════════════════════════
                 CASCADE TIMELINE DETAIL DRAWER (slide-in from right on row click)
                 Shows: full diagnosis, before/after diff, cascade trace timeline
                 ═══════════════════════════════════════════════════════════════════ --}}
            <div x-show="detailOpen" x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-50"
                style="background: rgba(2,6,23,0.65); backdrop-filter: blur(4px);"
                @click.self="closeDetail()" @keydown.escape.window="closeDetail()">

                <div x-show="detailOpen" x-cloak
                    x-transition:enter="transition ease-out duration-260"
                    x-transition:enter-start="translate-x-full"
                    x-transition:enter-end="translate-x-0"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="translate-x-0"
                    x-transition:leave-end="translate-x-full"
                    class="absolute right-0 top-0 h-full w-full max-w-[640px] overflow-y-auto"
                    style="background: linear-gradient(170deg, #0b1224 0%, #0f1730 100%); border-left: 1px solid rgba(99,102,241,0.20); box-shadow: -20px 0 60px rgba(0,0,0,0.45);">

                    <template x-if="detailRow">
                        <div class="p-6 space-y-5">
                            {{-- Header --}}
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-500">Commission Adjustment</div>
                                    <div class="text-lg font-black text-white mt-1 tracking-tight font-mono" x-text="detailRow?.contract_id || 'Unknown Contract'"></div>
                                    <div class="text-[11px] text-slate-400 mt-1" x-text="detailRow?.patched_at"></div>
                                </div>
                                <button type="button" @click="closeDetail()" class="shrink-0 w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-white hover:bg-slate-700/40 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            {{-- Source badge + Match Key --}}
                            <div class="flex items-center gap-3 flex-wrap">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-[11px] font-bold uppercase tracking-wider"
                                    :style="`background: ${sourceBg(detailRow?.new_match_source)}; color: ${sourceFg(detailRow?.new_match_source)}; border: 1px solid ${sourceBorder(detailRow?.new_match_source)};`">
                                    <span class="w-2 h-2 rounded-full" :style="`background: ${sourceColor(detailRow?.new_match_source)};`"></span>
                                    <span x-text="detailRow?.new_match_source || 'Unresolved'"></span>
                                </span>
                                <div class="text-[11px] text-slate-400">
                                    <span class="text-slate-500">Match Key:</span>
                                    <span class="font-mono font-bold text-slate-200 ml-1" x-text="detailRow?.match_key || '—'"></span>
                                </div>
                            </div>

                            {{-- Diagnosis card --}}
                            <div class="rounded-xl p-4 border" style="background: rgba(15,23,42,0.55); border-color: rgba(99,102,241,0.18);">
                                <div class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-indigo-300/80 mb-2">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Diagnosis
                                </div>
                                <p class="text-[13px] text-slate-200 leading-relaxed" x-text="detailRow?.diagnosis || 'No diagnosis recorded for this row.'"></p>
                            </div>

                            {{-- Cascade Timeline --}}
                            <div class="rounded-xl p-4 border" style="background: rgba(15,23,42,0.55); border-color: rgba(99,102,241,0.18);">
                                <div class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-indigo-300/80 mb-3">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                    6-Stage Cascade Trace
                                </div>
                                <div class="space-y-1.5">
                                    <template x-for="(stage, idx) in cascadeTimeline(detailRow?.new_match_source)" :key="stage.stage">
                                        <div class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-150"
                                            :class="{
                                                'bg-emerald-500/10 border border-emerald-500/30':            stage.state === 'matched',
                                                'bg-slate-700/20 border border-slate-700/30 opacity-90':     stage.state === 'probed',
                                                'bg-slate-900/30 border border-slate-800/30 opacity-50':     stage.state === 'not_reached' || stage.state === 'skipped',
                                            }">
                                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-black shrink-0"
                                                :class="{
                                                    'bg-emerald-400 text-slate-900':                       stage.state === 'matched',
                                                    'bg-slate-600 text-slate-300':                         stage.state === 'probed',
                                                    'bg-slate-800 text-slate-600':                         stage.state === 'not_reached' || stage.state === 'skipped',
                                                }"
                                                x-text="idx + 1"></div>
                                            <div class="flex-1 flex items-center justify-between gap-2">
                                                <span class="text-[12px] font-bold"
                                                    :class="{
                                                        'text-emerald-200':                                  stage.state === 'matched',
                                                        'text-slate-300':                                    stage.state === 'probed',
                                                        'text-slate-500':                                    stage.state === 'not_reached' || stage.state === 'skipped',
                                                    }"
                                                    x-text="stage.stage"></span>
                                                <span class="text-[9px] uppercase tracking-widest font-black"
                                                    :class="{
                                                        'text-emerald-300':                                  stage.state === 'matched',
                                                        'text-slate-500':                                    stage.state === 'probed',
                                                        'text-slate-700':                                    stage.state === 'not_reached' || stage.state === 'skipped',
                                                    }"
                                                    x-text="{
                                                        'matched':     '✓ Matched',
                                                        'probed':      'Probed',
                                                        'not_reached': 'Not reached',
                                                        'skipped':     'Skipped',
                                                    }[stage.state]"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Resolved Assignment --}}
                            <div class="rounded-xl p-4 border" style="background: rgba(15,23,42,0.55); border-color: rgba(99,102,241,0.18);">
                                <div class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-indigo-300/80 mb-3">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Resolved Assignment
                                </div>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 text-[12px]">
                                    {{-- Agent --}}
                                    <div>
                                        <div class="text-[9px] font-black uppercase tracking-widest text-emerald-400 mb-1">Agent</div>
                                        <div class="text-emerald-300 font-bold break-words" x-text="detailRow?.new_agent_name || '—'"></div>
                                    </div>

                                    {{-- Department --}}
                                    <div>
                                        <div class="text-[9px] font-black uppercase tracking-widest text-indigo-400 mb-1">Department</div>
                                        <div class="text-indigo-300 font-semibold break-words" x-text="detailRow?.new_department || '—'"></div>
                                    </div>

                                    {{-- Payee --}}
                                    <div>
                                        <div class="text-[9px] font-black uppercase tracking-widest text-teal-400 mb-1">Payee</div>
                                        <div class="text-teal-300 font-semibold break-words" x-text="detailRow?.new_payee_name || '—'"></div>
                                    </div>
                                </div>
                            </div>

                            {{-- Footer meta --}}
                            <div class="text-[10px] text-slate-500 flex items-center justify-between border-t border-slate-700/30 pt-3">
                                <span><span class="text-slate-600 uppercase tracking-widest font-black">Patched By</span> <span class="text-slate-300 font-semibold ml-1.5" x-text="detailRow?.updated_by_name || 'System'"></span></span>
                                <span class="font-mono text-[10px] uppercase tracking-widest"
                                    :class="{
                                        'text-emerald-400': detailRow?.change_type === 'analysis_resolved',
                                        'text-rose-400':    detailRow?.change_type === 'analysis_lock_override',
                                        'text-amber-400':   detailRow?.change_type === 'analysis_unresolved',
                                        'text-rose-500':    detailRow?.change_type === 'analysis_failed',
                                    }"
                                    x-text="(detailRow?.change_type || '').replaceAll('_', ' ')"></span>
                            </div>
                        </div>
                    </template>
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

            /* ── Premium polish for adjustment grid ─────────────────────────── */
            .ag-theme-bob .ag-row {
                cursor: pointer;
                transition: background-color 120ms ease;
            }
            .ag-theme-bob .ag-row:hover {
                background-color: rgba(99, 102, 241, 0.06) !important;
            }
            .bob-kpi-tile {
                transition: transform 180ms ease, box-shadow 180ms ease, ring 120ms ease;
                will-change: transform;
            }
            .bob-kpi-tile:hover {
                transform: translateY(-1px);
                box-shadow: 0 6px 18px rgba(99, 102, 241, 0.12);
            }
            .bob-kpi-tile:active {
                transform: translateY(0);
            }
            /* Tabular numbers stay aligned in KPIs */
            .tabular-nums {
                font-variant-numeric: tabular-nums;
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
                    gridOptions: null,

                    // ── Premium filter + distribution state ─────────────────
                    statusFilter: 'all',
                    sourceFilter: '',
                    sourceDistribution: [],
                    statusDistribution: { resolved: 0, unresolved: 0, lock_override: 0, failed: 0 },

                    // ── Detail drawer state ─────────────────────────────────
                    detailRow: null,
                    detailOpen: false,

                    // ── Source palette (kept in sync with PHP CASCADE_PALETTE) ──
                    SOURCE_PALETTE: {
                        'Final BOB':     { fg:'#6ee7b7', bg:'rgba(16,185,129,0.15)', border:'rgba(16,185,129,0.40)', bar:'#10b981' },
                        'IMS':           { fg:'#a5b4fc', bg:'rgba(99,102,241,0.18)', border:'rgba(99,102,241,0.40)', bar:'#6366f1' },
                        'Health Sherpa': { fg:'#5eead4', bg:'rgba(20,184,166,0.18)', border:'rgba(20,184,166,0.40)', bar:'#14b8a6' },
                        'Payee Map':     { fg:'#f9a8d4', bg:'rgba(236,72,153,0.18)', border:'rgba(236,72,153,0.40)', bar:'#ec4899' },
                        'Carrier BOB':   { fg:'#fcd34d', bg:'rgba(245,158,11,0.18)', border:'rgba(245,158,11,0.40)', bar:'#f59e0b' },
                        'Lock List':     { fg:'#fda4af', bg:'rgba(244,63,94,0.20)',  border:'rgba(244,63,94,0.45)',  bar:'#f43f5e' },
                        'Unresolved':    { fg:'#cbd5e1', bg:'rgba(100,116,139,0.18)', border:'rgba(100,116,139,0.40)', bar:'#64748b' },
                        'FAILED':        { fg:'#fecaca', bg:'rgba(127,29,29,0.30)',  border:'rgba(220,38,38,0.45)',  bar:'#dc2626' },
                    },

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

                    sourcePalette(source) {
                        return this.SOURCE_PALETTE[source] || this.SOURCE_PALETTE['Unresolved'];
                    },
                    sourceColor(source) { return this.sourcePalette(source).bar; },
                    sourceFg(source)    { return this.sourcePalette(source).fg; },
                    sourceBg(source)    { return this.sourcePalette(source).bg; },
                    sourceBorder(source){ return this.sourcePalette(source).border; },
                    sourceTotalCount() {
                        return (this.sourceDistribution || []).reduce((sum, e) => sum + Number(e.count || 0), 0);
                    },
                    sourcePercent(entry) {
                        const total = this.sourceTotalCount();
                        if (!total) return 0;
                        return (Number(entry.count || 0) / total) * 100;
                    },

                    setStatusFilter(value) {
                        this.statusFilter = (this.statusFilter === value) ? 'all' : value;
                        this.refreshGrid();
                    },
                    setSourceFilter(value) {
                        this.sourceFilter = (this.sourceFilter === value) ? '' : value;
                        this.refreshGrid();
                    },

                    openDetail(row) {
                        this.detailRow = row;
                        this.detailOpen = true;
                    },
                    closeDetail() {
                        this.detailOpen = false;
                        setTimeout(() => { this.detailRow = null; }, 200);
                    },

                    /** Cascade timeline: ordered list of sources with the matched stage flagged. */
                    cascadeTimeline(matchSource) {
                        const stages = ['Lock List', 'Final BOB', 'IMS', 'Health Sherpa', 'Payee Map', 'Carrier BOB'];
                        const idx = stages.indexOf(matchSource);
                        return stages.map((stage, i) => ({
                            stage,
                            state: idx === -1 ? 'skipped' : (i < idx ? 'probed' : (i === idx ? 'matched' : 'not_reached')),
                        }));
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
                        this.refreshGrid();
                    },

                    requestUrl(page, pageSize, sortModel = []) {
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
                        if (this.statusFilter && this.statusFilter !== 'all') {
                            params.set('status_filter', this.statusFilter);
                        }
                        if (this.sourceFilter) {
                            params.set('source_filter', this.sourceFilter);
                        }

                        params.set('page', page);
                        params.set('limit', pageSize);
                        params.set('sortModel', JSON.stringify(sortModel));

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
                                field: 'new_agent_name',
                                headerName: 'Resolved Agent',
                                minWidth: 170,
                                flex: 1.2,
                                cellRenderer: (params) => `<span class="text-emerald-300 font-bold">${this.escapeHtml(params.value) || '—'}</span>`,
                            },
                            {
                                field: 'new_department',
                                headerName: 'Resolved Dept',
                                minWidth: 160,
                                flex: 1,
                                cellRenderer: (params) => `<span class="text-indigo-300 font-semibold">${this.escapeHtml(params.value) || '—'}</span>`,
                            },
                            {
                                field: 'new_payee_name',
                                headerName: 'Resolved Payee',
                                minWidth: 180,
                                flex: 1.2,
                                cellRenderer: (params) => `<span class="text-teal-300 font-semibold">${this.escapeHtml(params.value) || '—'}</span>`,
                            },
                            {
                                field: 'new_match_source',
                                headerName: 'Resolution Source',
                                minWidth: 150,
                                flex: 0.9,
                                cellRenderer: (params) => {
                                    const value = (params.value || '').toString();
                                    const palette = {
                                        'Final BOB':     { bg: 'rgba(16,185,129,0.15)', fg: '#6ee7b7', border: 'rgba(16,185,129,0.35)' },
                                        'IMS':           { bg: 'rgba(99,102,241,0.18)', fg: '#a5b4fc', border: 'rgba(99,102,241,0.35)' },
                                        'Health Sherpa': { bg: 'rgba(20,184,166,0.18)', fg: '#5eead4', border: 'rgba(20,184,166,0.35)' },
                                        'Payee Map':     { bg: 'rgba(236,72,153,0.18)', fg: '#f9a8d4', border: 'rgba(236,72,153,0.35)' },
                                        'Carrier BOB':   { bg: 'rgba(245,158,11,0.18)', fg: '#fcd34d', border: 'rgba(245,158,11,0.35)' },
                                        'Lock List':     { bg: 'rgba(244,63,94,0.20)',  fg: '#fda4af', border: 'rgba(244,63,94,0.40)'  },
                                        'Unresolved':    { bg: 'rgba(100,116,139,0.18)', fg: '#cbd5e1', border: 'rgba(100,116,139,0.35)' },
                                        'FAILED':        { bg: 'rgba(127,29,29,0.30)',  fg: '#fecaca', border: 'rgba(220,38,38,0.40)' },
                                    };
                                    const c = palette[value] || palette['Unresolved'];
                                    const safe = this.escapeHtml(value || 'n/a');
                                    return `<span class="text-[10px] uppercase font-black tracking-widest px-2 py-0.5 rounded-md" style="background:${c.bg}; color:${c.fg}; border:1px solid ${c.border};">${safe}</span>`;
                                },
                            },
                            {
                                field: 'match_key',
                                headerName: 'Match Key',
                                minWidth: 150,
                                flex: 1,
                                cellRenderer: (params) => `<span class="font-mono text-[11px] text-slate-300">${this.escapeHtml(params.value) || '—'}</span>`,
                            },
                            {
                                field: 'diagnosis',
                                headerName: 'Diagnosis',
                                minWidth: 280,
                                flex: 2,
                                wrapText: true,
                                autoHeight: false,
                                cellRenderer: (params) => {
                                    const safe = this.escapeHtml(params.value);
                                    return `<span class="text-xs text-slate-300 leading-snug" title="${safe}">${safe || '—'}</span>`;
                                },
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

                        this.gridOptions = {
                            columnDefs,
                            // AG Grid v33+ requires explicit theme opt-in. Use 'legacy' so the
                            // existing ag-grid.css styles continue to apply without conflict
                            // (eliminates console error #239).
                            theme: 'legacy',
                            rowModelType: 'infinite',
                            rowHeight: 48,
                            headerHeight: 46,
                            pagination: true,
                            paginationPageSize: 75,
                            // Page-size selector must include the active paginationPageSize
                            // (75) — otherwise AG Grid emits warnings #94/#95.
                            paginationPageSizeSelector: [25, 50, 75, 100, 250],
                            cacheBlockSize: 75,
                            maxBlocksInCache: 10,
                            suppressCellFocus: true,
                            enableCellTextSelection: true,
                            animateRows: true,
                            onRowClicked: (event) => {
                                if (event && event.data) this.openDetail(event.data);
                            },
                            defaultColDef: {
                                sortable: true,
                                filter: false,
                                resizable: true,
                            },
                            overlayLoadingTemplate: '<div class="ag-custom-loading"><div class="w-10 h-10 rounded-full border-4 border-indigo-500/20 border-t-indigo-500 animate-spin"></div></div>',
                            overlayNoRowsTemplate: '<div class="flex flex-col items-center gap-3 pt-16 px-6 text-center"><div class="w-12 h-12 rounded-full flex items-center justify-center" style="background: rgba(99,102,241,0.10);"><svg class="w-5 h-5 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg></div><span class="text-slate-300 font-bold text-sm">No matching adjustments</span><span class="text-slate-500 text-xs">Try clearing the status / source / search filters above.</span></div>',
                        };

                        this.gridApi = agGrid.createGrid(gridElement, this.gridOptions);
                        this.setGridDataSource();
                    },

                    setGridDataSource() {
                        const pageSize = this.gridOptions?.cacheBlockSize || 75;
                        const datasource = {
                            getRows: async (params) => {
                                const startRow = params.startRow ?? 0;
                                const page = Math.floor(startRow / pageSize) + 1;

                                try {
                                    const response = await fetch(this.requestUrl(page, pageSize, params.sortModel || []));
                                    if (!response.ok) {
                                        // Surface the HTTP status so 429 (throttle), 403 (perm),
                                        // 419 (CSRF), and 5xx are distinguishable in the console
                                        // and the no-rows overlay.
                                        const detail = response.status === 429
                                            ? 'Rate limit reached — please wait a moment before refreshing.'
                                            : `HTTP ${response.status} ${response.statusText || ''}`.trim();
                                        throw new Error(detail);
                                    }

                                    const payload = await response.json();
                                    const rows = payload.data || payload.rows || [];
                                    const total = Number(payload.total || payload.totalCount || 0);
                                    const endRow = startRow + rows.length;
                                    const lastRow = endRow >= total ? total : -1;

                                    this.totalRows = total;
                                    this.runSummary = payload.runSummary || null;
                                    this.metrics.patched = Number(this.runSummary?.contract_patched_records ?? 0);
                                    this.metrics.skipped = Number(this.runSummary?.skipped_records ?? 0);
                                    this.metrics.failed = Number(this.runSummary?.failed_records ?? 0);
                                    this.sourceDistribution = Array.isArray(this.runSummary?.source_distribution) ? this.runSummary.source_distribution : [];
                                    this.statusDistribution = this.runSummary?.status_distribution || { resolved: 0, unresolved: 0, lock_override: 0, failed: 0 };

                                    if (typeof params.successCallback === 'function') {
                                        params.successCallback(rows, lastRow);
                                        return;
                                    }

                                    if (typeof params.success === 'function') {
                                        params.success({ rowData: rows, rowCount: total });
                                    }
                                } catch (error) {
                                    console.error('Commission adjustment detail load error:', error);

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

                    loadData() {
                        this.refreshGrid();
                    },

                    refreshGrid() {
                        if (!this.gridApi) {
                            this.initGrid();
                        }

                        if (!this.gridApi) {
                            return;
                        }

                        if (typeof this.gridApi.purgeInfiniteCache === 'function') {
                            this.gridApi.purgeInfiniteCache();
                        } else if (typeof this.gridApi.refreshInfiniteCache === 'function') {
                            this.gridApi.refreshInfiniteCache();
                        }
                    },
                }));
            });
        </script>
    @endpush
</x-reconciliation-layout>
