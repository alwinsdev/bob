<x-reconciliation-layout>
    <x-slot name="pageTitle">Commission Dashboard</x-slot>
    <x-slot name="pageSubtitle">High-level overview of final reconciliation state and assignment distribution.</x-slot>
    <x-slot name="headerActions">
        <a href="{{ route('reconciliation.reporting.contract-patches') }}" class="bob-btn-ghost">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
            Commission Adjustments
        </a>
        <a href="{{ route('reconciliation.reporting.final-bob') }}" class="bob-btn-primary">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0119.5 16.5h-2.25m-9 0h9l-4.5 5.25L9 16.5z" />
            </svg>
            View Final BOB
        </a>
    </x-slot>

    @php
        $optimalityYield = $metrics['total_records'] > 0
            ? round((($metrics['locklist_overrides'] + $metrics['ims_matches'] + $metrics['hs_matches']) / $metrics['total_records']) * 100, 1)
            : 0;
        $adjustmentSummary = $adjustmentSummary ?? [
            'total_runs' => 0,
            'adjusted_rows' => 0,
            'skipped_rows' => 0,
            'failed_rows' => 0,
            'top_skip_reasons' => [],
            'recent_runs' => [],
            'details_url' => route('reconciliation.reporting.contract-patches'),
        ];
        $adjustmentExceptionRows = ($adjustmentSummary['skipped_rows'] ?? 0) + ($adjustmentSummary['failed_rows'] ?? 0);
        $adjustmentProcessedRows = ($adjustmentSummary['adjusted_rows'] ?? 0) + ($adjustmentSummary['skipped_rows'] ?? 0) + ($adjustmentSummary['failed_rows'] ?? 0);
        $adjustmentSuccessRate = $adjustmentProcessedRows > 0
            ? round((($adjustmentSummary['adjusted_rows'] ?? 0) / $adjustmentProcessedRows) * 100, 1)
            : 0;
        $adjustmentRiskRate = $adjustmentProcessedRows > 0
            ? round(($adjustmentExceptionRows / $adjustmentProcessedRows) * 100, 1)
            : 0;
        $topSkipReasonTotal = array_sum($adjustmentSummary['top_skip_reasons'] ?? []);
    @endphp

    <div class="max-w-[1600px] mx-auto space-y-8" x-data="commissionDashboard()">
        
        {{-- ── Executive Intelligence Snapshots ── --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            {{-- Total Ready --}}
            <div class="bob-metric-card group relative overflow-hidden" style="background: var(--bob-metric-indigo)">
                <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-500/10 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none transition-opacity duration-500 group-hover:opacity-100 opacity-50"></div>
                <div class="flex items-center gap-3 mb-4 relative">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-indigo-500/10 border border-indigo-500/20 shadow-[0_0_15px_rgba(99,102,241,0.1)]">
                        <svg class="w-5 h-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" /></svg>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-[0.1em] text-indigo-300/80">Reconciled Pool</span>
                </div>
                <div class="text-3xl font-black text-white tracking-tight mb-1">{{ number_format($metrics['total_records']) }}</div>
                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Total Production Ready</div>
            </div>

            {{-- Locklist Overrides --}}
            <div class="bob-metric-card group relative overflow-hidden" style="background: var(--bob-metric-rose)">
                <div class="absolute top-0 right-0 w-32 h-32 bg-rose-500/10 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none transition-opacity duration-500 group-hover:opacity-100 opacity-50"></div>
                <div class="flex items-center gap-3 mb-4 relative">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-rose-500/10 border border-rose-500/20 shadow-[0_0_15px_rgba(244,63,94,0.1)]">
                        <svg class="w-5 h-5 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-[0.1em] text-rose-300/80">Rules Applied</span>
                </div>
                <div class="text-3xl font-black text-white tracking-tight mb-1">{{ number_format($metrics['locklist_overrides']) }}</div>
                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Locklist Impact</div>
            </div>

            {{-- IMS Match --}}
            <div class="bob-metric-card group relative overflow-hidden" style="background: var(--bob-metric-indigo)">
                <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-400/10 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none transition-opacity duration-500 group-hover:opacity-100 opacity-50"></div>
                <div class="flex items-center gap-3 mb-4 relative">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-indigo-500/10 border border-indigo-500/20 shadow-[0_0_15px_rgba(99,102,241,0.1)]">
                        <svg class="w-5 h-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z" /></svg>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-[0.1em] text-indigo-300/80">IMS Core</span>
                </div>
                <div class="text-3xl font-black text-white tracking-tight mb-1">{{ number_format($metrics['ims_matches']) }}</div>
                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Internal Matrix Sync</div>
            </div>

            {{-- Sherpa Match --}}
            <div class="bob-metric-card group relative overflow-hidden" style="background: var(--bob-metric-emerald)">
                <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-500/10 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none transition-opacity duration-500 group-hover:opacity-100 opacity-50"></div>
                <div class="flex items-center gap-3 mb-4 relative">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-emerald-500/10 border border-emerald-500/20 shadow-[0_0_15px_rgba(16,185,129,0.1)]">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-[0.1em] text-emerald-300/80">Sherpa Sync</span>
                </div>
                <div class="text-3xl font-black text-white tracking-tight mb-1">{{ number_format($metrics['hs_matches']) }}</div>
                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">External Feed Match</div>
            </div>

            {{-- Manual --}}
            <div class="bob-metric-card group relative overflow-hidden" style="background: var(--bob-metric-amber)">
                <div class="absolute top-0 right-0 w-32 h-32 bg-amber-500/10 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none transition-opacity duration-500 group-hover:opacity-100 opacity-50"></div>
                <div class="flex items-center gap-3 mb-4 relative">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-amber-500/10 border border-amber-500/20 shadow-[0_0_15px_rgba(245,158,11,0.1)]">
                        <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" /></svg>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-[0.1em] text-amber-300/80">Edge Cases</span>
                </div>
                <div class="text-3xl font-black text-white tracking-tight mb-1">{{ number_format($metrics['manual_matches']) }}</div>
                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Manual Resolutions</div>
            </div>
        </div>

        {{-- ── Analytical Deep-Dive ── --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            {{-- Source Yield (Primary Chart) --}}
            <div class="lg:col-span-12 xl:col-span-7">
                <div class="bob-glass-panel p-8 relative overflow-hidden min-h-[520px]">
                    <div class="absolute top-0 right-0 w-1/3 h-full bg-gradient-to-l from-indigo-500/5 to-transparent pointer-events-none"></div>
                    <div class="flex items-center justify-between mb-8 relative">
                        <div>
                            <h2 class="text-lg font-black text-white tracking-tight mb-1">Source Logic Distribution</h2>
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest">Efficiency breakdown of reconciliation engines</p>
                        </div>
                        <div class="px-4 py-2 rounded-xl bg-white/5 border border-white/10">
                            <div class="text-[10px] font-black text-indigo-400 uppercase tracking-widest">Optimality Yield</div>
                            <div class="text-xl font-black text-white">{{ $optimalityYield }}%</div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
                        <div class="md:col-span-7 relative h-[320px] md:h-[340px]">
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                <div class="text-center">
                                    <div class="text-3xl font-black text-white leading-none">{{ number_format($metrics['total_records']) }}</div>
                                    <div class="text-[9px] font-bold text-slate-500 uppercase tracking-[0.2em] mt-1">Units</div>
                                </div>
                            </div>
                            <canvas id="sourceChart"></canvas>
                        </div>
                        
                        <div class="md:col-span-5 w-full md:max-w-[460px] md:justify-self-end space-y-4">
                            <div class="bob-info-card p-4 flex items-center justify-between border-l-4 border-l-purple-500">
                                <span class="text-xs font-bold text-slate-300">Locklist Rules (Advanced)</span>
                                <span class="text-sm font-black text-white">{{ number_format($metrics['locklist_overrides']) }}</span>
                            </div>
                            <div class="bob-info-card p-4 flex items-center justify-between border-l-4 border-l-indigo-500">
                                <span class="text-xs font-bold text-slate-300">Internal Matrix Matches</span>
                                <span class="text-sm font-black text-white">{{ number_format($metrics['ims_matches']) }}</span>
                            </div>
                            <div class="bob-info-card p-4 flex items-center justify-between border-l-4 border-l-emerald-500">
                                <span class="text-xs font-bold text-slate-300">Sherpa Data Synergies</span>
                                <span class="text-sm font-black text-white">{{ number_format($metrics['hs_matches']) }}</span>
                            </div>
                            <div class="bob-info-card p-4 flex items-center justify-between border-l-4 border-l-amber-500 opacity-60">
                                <span class="text-xs font-bold text-slate-400">Exception Handling</span>
                                <span class="text-sm font-black text-slate-200">{{ number_format($metrics['manual_matches']) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Top Agent Performance --}}
            <div class="lg:col-span-12 xl:col-span-5">
                <div class="bob-glass-panel p-8 relative overflow-hidden min-h-[520px]">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h2 class="text-lg font-black text-white tracking-tight mb-1">Contract Allocation</h2>
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest">Portfolio density per assigned agent</p>
                        </div>
                    </div>
                    
                    <div class="relative h-[340px] md:h-[360px] xl:h-[380px]">
                        <canvas id="agentChart"></canvas>
                    </div>
                </div>
            </div>

        </div>

        {{-- ── Commission Adjustment Intelligence ── --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <div class="lg:col-span-8">
                <div class="bob-glass-panel p-7 lg:p-8 relative overflow-hidden">
                    <div class="absolute -top-10 -right-10 w-48 h-48 rounded-full blur-[80px] bg-indigo-500/10 pointer-events-none"></div>
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-6 relative z-10">
                        <div>
                            <h2 class="text-lg font-black text-white tracking-tight mb-1">Commission Adjustment Intelligence</h2>
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest">Associated adjustment runs impacting payout alignment</p>
                        </div>
                        <a href="{{ $adjustmentSummary['details_url'] }}" class="bob-btn-ghost text-xs whitespace-nowrap">Open Full Details</a>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                        <div class="bob-info-card p-4 border-l-4 border-l-indigo-500">
                            <div class="text-[10px] uppercase tracking-widest font-black text-slate-500">Runs</div>
                            <div class="text-2xl font-black text-white mt-1">{{ number_format($adjustmentSummary['total_runs']) }}</div>
                            <div class="text-[10px] text-slate-500 mt-1">Adjustment batches</div>
                        </div>
                        <div class="bob-info-card p-4 border-l-4 border-l-emerald-500">
                            <div class="text-[10px] uppercase tracking-widest font-black text-slate-500">Adjusted</div>
                            <div class="text-2xl font-black text-emerald-300 mt-1">{{ number_format($adjustmentSummary['adjusted_rows']) }}</div>
                            <div class="text-[10px] text-slate-500 mt-1">Rows updated</div>
                        </div>
                        <div class="bob-info-card p-4 border-l-4 border-l-rose-500">
                            <div class="text-[10px] uppercase tracking-widest font-black text-slate-500">Exception</div>
                            <div class="text-2xl font-black text-rose-300 mt-1">{{ number_format($adjustmentExceptionRows) }}</div>
                            <div class="text-[10px] text-slate-500 mt-1">Skipped + failed</div>
                        </div>
                        <div class="bob-info-card p-4 border-l-4 border-l-cyan-500">
                            <div class="text-[10px] uppercase tracking-widest font-black text-slate-500">Success Rate</div>
                            <div class="text-2xl font-black text-cyan-300 mt-1">{{ number_format($adjustmentSuccessRate, 1) }}%</div>
                            <div class="text-[10px] text-slate-500 mt-1">Adjusted / processed</div>
                        </div>
                    </div>

                    @if(($adjustmentSummary['total_runs'] ?? 0) > 0)
                        <div class="rounded-2xl border overflow-hidden" style="background:rgba(255,255,255,0.02);border-color:rgba(255,255,255,0.06);">
                            <div class="grid grid-cols-12 gap-2 px-4 py-3 border-b text-[10px] uppercase tracking-widest font-black text-slate-500"
                                 style="border-color:rgba(255,255,255,0.06);">
                                <div class="col-span-12 sm:col-span-5">Adjustment Run</div>
                                <div class="col-span-9 sm:col-span-5">Performance Mix</div>
                                <div class="col-span-3 sm:col-span-2 text-right">Action</div>
                            </div>

                            @foreach(($adjustmentSummary['recent_runs'] ?? []) as $run)
                                @php
                                    $status = strtolower((string) ($run['status'] ?? 'pending'));
                                    $statusStyle = match ($status) {
                                        'completed' => 'background:rgba(16,185,129,0.12);color:#6ee7b7;border:1px solid rgba(16,185,129,0.24);',
                                        'completed_with_errors' => 'background:rgba(245,158,11,0.12);color:#fbbf24;border:1px solid rgba(245,158,11,0.24);',
                                        'failed' => 'background:rgba(244,63,94,0.12);color:#fb7185;border:1px solid rgba(244,63,94,0.24);',
                                        default => 'background:rgba(99,102,241,0.12);color:#a5b4fc;border:1px solid rgba(99,102,241,0.24);',
                                    };
                                    $runProcessed = (int) ($run['adjusted_rows'] + $run['skipped_rows'] + $run['failed_rows']);
                                    $runAdjustedPct = $runProcessed > 0 ? round(($run['adjusted_rows'] / $runProcessed) * 100, 1) : 0;
                                    $runSkippedPct = $runProcessed > 0 ? round(($run['skipped_rows'] / $runProcessed) * 100, 1) : 0;
                                    $runFailedPct = $runProcessed > 0 ? round(($run['failed_rows'] / $runProcessed) * 100, 1) : 0;
                                @endphp

                                <div class="px-4 py-3 border-t" style="border-color:rgba(255,255,255,0.05);">
                                    <div class="grid grid-cols-12 gap-2 items-start sm:items-center">
                                        <div class="col-span-12 sm:col-span-5">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <p class="text-sm font-bold text-white break-all">{{ $run['name'] }}</p>
                                                <span class="text-[9px] font-black uppercase tracking-widest px-2 py-1 rounded-md" style="{{ $statusStyle }}">{{ $run['status_label'] }}</span>
                                            </div>
                                            <p class="text-[11px] text-slate-500 mt-1">{{ $run['created_at'] }}</p>
                                        </div>

                                        <div class="col-span-9 sm:col-span-5">
                                            <div class="grid grid-cols-3 gap-2 text-center">
                                                <div>
                                                    <div class="text-sm font-black text-emerald-300">{{ number_format($run['adjusted_rows']) }}</div>
                                                    <div class="text-[9px] uppercase tracking-widest font-bold text-slate-500">Adj</div>
                                                </div>
                                                <div>
                                                    <div class="text-sm font-black text-indigo-300">{{ number_format($run['skipped_rows']) }}</div>
                                                    <div class="text-[9px] uppercase tracking-widest font-bold text-slate-500">Skip</div>
                                                </div>
                                                <div>
                                                    <div class="text-sm font-black text-rose-300">{{ number_format($run['failed_rows']) }}</div>
                                                    <div class="text-[9px] uppercase tracking-widest font-bold text-slate-500">Fail</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-span-3 sm:col-span-2 text-right">
                                            <a href="{{ $run['details_url'] }}" class="bob-btn-ghost text-xs whitespace-nowrap">Open</a>
                                        </div>
                                    </div>

                                    <div class="mt-3 h-1.5 rounded-full overflow-hidden flex" style="background:rgba(255,255,255,0.06);">
                                        <div style="width: {{ $runAdjustedPct }}%; background:#34d399;"></div>
                                        <div style="width: {{ $runSkippedPct }}%; background:#818cf8;"></div>
                                        <div style="width: {{ $runFailedPct }}%; background:#fb7185;"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-2xl border p-6 text-center" style="background:rgba(255,255,255,0.02);border-color:rgba(255,255,255,0.06);">
                            <p class="text-sm font-semibold text-slate-300">No commission adjustment runs recorded for this batch yet.</p>
                            <p class="text-xs text-slate-500 mt-1">Once associated adjustment files are processed, detailed run intelligence will appear here.</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="lg:col-span-4">
                <div class="bob-glass-panel p-7 lg:p-8 relative overflow-hidden">
                    <h2 class="text-lg font-black text-white tracking-tight mb-1">Top Skip Reasons</h2>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-6">Most common rule-based exclusions</p>

                    @if(!empty($adjustmentSummary['top_skip_reasons']))
                        <div class="space-y-3">
                            @foreach($adjustmentSummary['top_skip_reasons'] as $reason => $count)
                                @php
                                    $reasonPct = $topSkipReasonTotal > 0 ? round(($count / $topSkipReasonTotal) * 100, 1) : 0;
                                @endphp
                                <div class="rounded-xl border p-3" style="background:rgba(255,255,255,0.02);border-color:rgba(255,255,255,0.06);">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-xs font-semibold text-slate-300 leading-relaxed truncate pr-2">{{ $reason }}</p>
                                        <span class="text-xs font-black text-indigo-300 font-mono">{{ number_format($count) }}</span>
                                    </div>
                                    <div class="mt-2 h-1.5 rounded-full overflow-hidden" style="background:rgba(255,255,255,0.06);">
                                        <div class="h-full rounded-full" style="width: {{ $reasonPct }}%; background: linear-gradient(90deg, #818cf8 0%, #34d399 100%);"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-xl border p-5 text-center" style="background:rgba(255,255,255,0.02);border-color:rgba(255,255,255,0.06);">
                            <p class="text-sm font-semibold text-slate-300">No skipped rows logged.</p>
                            <p class="text-xs text-slate-500 mt-1">Skip reason analytics will appear when a run records exclusions.</p>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-3 mt-6">
                        <div class="bob-info-card p-3 text-center">
                            <div class="text-[9px] uppercase tracking-widest font-black text-slate-500">Risk Rate</div>
                            <div class="text-lg font-black text-rose-300 mt-1">{{ number_format($adjustmentRiskRate, 1) }}%</div>
                        </div>
                        <div class="bob-info-card p-3 text-center">
                            <div class="text-[9px] uppercase tracking-widest font-black text-slate-500">Processed</div>
                            <div class="text-lg font-black text-cyan-300 mt-1">{{ number_format($adjustmentProcessedRows) }}</div>
                        </div>
                    </div>

                    <a href="{{ $adjustmentSummary['details_url'] }}" class="bob-btn-ghost text-xs w-full justify-center mt-4">Open Commission Adjustment Details</a>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('commissionDashboard', () => ({
                metrics: @json($metrics),
                agentDist: @json($agentDistribution),

                init() {
                    Chart.defaults.color = 'rgba(148, 163, 184, 0.8)';
                    Chart.defaults.font.family = "'Inter', sans-serif";
                    
                    this.initSourceChart();
                    this.initAgentChart();
                },

                initSourceChart() {
                    const ctx = document.getElementById('sourceChart').getContext('2d');
                    const compactLayout = window.innerWidth < 1024;
                    new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Locklist Rules', 'IMS Matches', 'Sherpa Matches', 'Manual / Other'],
                            datasets: [{
                                data: [
                                    this.metrics.locklist_overrides,
                                    this.metrics.ims_matches,
                                    this.metrics.hs_matches,
                                    this.metrics.manual_matches
                                ],
                                backgroundColor: [
                                    '#c084fc', // Locklist (Purple)
                                    '#818cf8', // IMS (Indigo)
                                    '#34d399', // HS (Emerald)
                                    '#fbbf24'  // Manual (Amber)
                                ],
                                borderWidth: 0,
                                hoverOffset: 4,
                                radius: compactLayout ? '86%' : '90%'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '72%',
                            layout: {
                                padding: {
                                    top: 6,
                                    bottom: 6,
                                }
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    align: 'center',
                                    labels: {
                                        padding: 14,
                                        usePointStyle: true,
                                        pointStyle: 'circle',
                                        boxWidth: 8,
                                        boxHeight: 8,
                                        font: { size: 11, weight: '600' }
                                    }
                                }
                            }
                        }
                    });
                },

                initAgentChart() {
                    if (this.agentDist.length === 0) return;

                    const compactLayout = window.innerWidth < 768;
                    const labels = this.agentDist.map(a => a.aligned_agent_name);
                    const data = this.agentDist.map(a => a.total);

                    const ctx = document.getElementById('agentChart').getContext('2d');
                    
                    // Create gradient
                    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                    gradient.addColorStop(0, 'rgba(99, 102, 241, 0.8)');
                    gradient.addColorStop(1, 'rgba(99, 102, 241, 0.1)');

                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Assigned Contracts',
                                data: data,
                                backgroundColor: gradient,
                                borderRadius: 4,
                                barPercentage: compactLayout ? 0.78 : 0.62,
                                categoryPercentage: compactLayout ? 0.9 : 0.78,
                                maxBarThickness: compactLayout ? 24 : 30
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: {
                                padding: {
                                    bottom: compactLayout ? 12 : 6,
                                }
                            },
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: { color: 'rgba(255, 255, 255, 0.05)', drawBorder: false },
                                    ticks: { font: { size: 10 } }
                                },
                                x: {
                                    grid: { display: false, drawBorder: false },
                                    ticks: { 
                                        font: { size: 10 },
                                        autoSkip: true,
                                        maxTicksLimit: compactLayout ? 6 : 10,
                                        maxRotation: compactLayout ? 55 : 35,
                                        minRotation: compactLayout ? 55 : 35,
                                        padding: 6
                                    }
                                }
                            }
                        }
                    });
                }
            }));
        });
    </script>
    @endpush
</x-reconciliation-layout>
