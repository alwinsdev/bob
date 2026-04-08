<x-reconciliation-layout>
    <div class="max-w-[1600px] mx-auto space-y-8" x-data="homeHub()">
        
        {{-- ── EXECUTIVE HERO SECTION ── --}}
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-2">
            <div>
                <h1 class="text-3xl font-black tracking-tight flex items-center gap-3" style="color: var(--bob-text-primary)">
                    <span class="bg-gradient-to-r from-indigo-400 to-purple-400 bg-clip-text text-transparent">Hello, {{ explode(' ', $user->name)[0] }}</span>
                    <span class="text-xl animate-pulse">👋</span>
                </h1>
                <p class="text-sm mt-1 font-medium flex items-center gap-2" style="color: var(--bob-text-muted)">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]"></span>
                    System Pulse: Operational · Core Engine Active
                </p>
            </div>
            <div class="flex items-center gap-3">
                <div class="px-4 py-2 rounded-xl border flex items-center gap-3" style="background: var(--bob-bg-input); border-color: var(--bob-border-light);">
                    <div class="flex -space-x-2">
                        @foreach(range(1,3) as $i)
                        <div class="w-6 h-6 rounded-full border-2 border-[#0b1120] bg-slate-700 flex items-center justify-center text-[8px] font-bold">U{{ $i }}</div>
                        @endforeach
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-widest" style="color: var(--bob-text-faint)">{{ number_format($pipeline['locked']) }} Records Locked</span>
                </div>
            </div>
        </div>

        {{-- ── ROW 1: Premium Metric Intelligence ── --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-5">

            {{-- Total Records --}}
            <div class="bob-glass-panel relative p-5 group cursor-pointer transition-all hover:scale-[1.02] overflow-hidden" 
                 style="background: var(--bob-metric-indigo)"
                 @click="window.location='{{ route('reconciliation.dashboard') }}'">
                <div class="absolute top-0 right-0 p-3 opacity-20 group-hover:opacity-40 transition-opacity">
                    <svg class="w-12 h-12 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z" /></svg>
                </div>
                <div class="relative z-10">
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] mb-1 block" style="color: var(--bob-text-faint)">Pipeline Capacity</span>
                    <div class="text-3xl font-black mb-1" style="color: var(--bob-text-primary)">{{ number_format($pipeline['total']) }}</div>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="flex items-center justify-center w-4 h-4 rounded bg-emerald-500/10 text-emerald-400">
                             <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                        </span>
                        <span class="text-[10px] font-bold text-emerald-400/80">Active Ingest</span>
                    </div>
                </div>
            </div>

            {{-- Pending --}}
            <div class="bob-glass-panel relative p-5 group cursor-pointer transition-all hover:scale-[1.02] overflow-hidden" 
                 style="background: var(--bob-metric-amber)"
                 @click="window.location='{{ route('reconciliation.dashboard') }}?status=pending'">
                <div class="absolute top-0 right-0 p-3 opacity-20 group-hover:opacity-40 transition-opacity">
                    <svg class="w-12 h-12 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div class="relative z-10">
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] mb-1 block text-amber-400/70">Awaiting Action</span>
                    <div class="text-3xl font-black mb-1" style="color: var(--bob-text-primary)">{{ number_format($pipeline['pending']) }}</div>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="text-[10px] font-bold text-amber-400/80">{{ round(($pipeline['pending'] / max(1, $pipeline['total'])) * 100) }}% of total</span>
                    </div>
                </div>
            </div>

            {{-- Flagged --}}
            <div class="bob-glass-panel relative p-5 group cursor-pointer transition-all hover:scale-[1.02] overflow-hidden" 
                 style="background: var(--bob-metric-rose)"
                 @click="window.location='{{ route('reconciliation.dashboard') }}?status=flagged'">
                <div class="absolute top-0 right-0 p-3 opacity-20 group-hover:opacity-40 transition-opacity">
                    <svg class="w-12 h-12 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0l2.77-.693a9 9 0 016.208.682l.108.054a9 9 0 006.086.71l3.114-.732a48.524 48.524 0 01-.005-10.499l-3.11.732a9 9 0 01-6.085-.711l-.108-.054a9 9 0 00-6.208-.682L3 4.5M3 15V4.5" /></svg>
                </div>
                <div class="relative z-10">
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] mb-1 block text-rose-400/70">Attention Required</span>
                    <div class="text-3xl font-black mb-1" style="color: var(--bob-text-primary)">{{ number_format($pipeline['flagged']) }}</div>
                    <div class="flex items-center gap-1.5 mt-2">
                         <span class="text-[10px] font-bold text-rose-400/80">Action items pending</span>
                    </div>
                </div>
            </div>

            {{-- Resolved --}}
            <div class="bob-glass-panel relative p-5 group cursor-pointer transition-all hover:scale-[1.02] overflow-hidden" 
                 style="background: var(--bob-metric-emerald)"
                 @click="window.location='{{ route('reconciliation.dashboard') }}?status=resolved'">
                <div class="absolute top-0 right-0 p-3 opacity-20 group-hover:opacity-40 transition-opacity">
                    <svg class="w-12 h-12 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div class="relative z-10">
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] mb-1 block text-emerald-400/70">Successfully Reconciled</span>
                    <div class="text-3xl font-black mb-1" style="color: var(--bob-text-primary)">{{ number_format($pipeline['resolved']) }}</div>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="text-[10px] font-bold text-emerald-400/80">Commission Ready</span>
                    </div>
                </div>
            </div>

            {{-- Lock Rules --}}
            <div class="bob-glass-panel relative p-5 group cursor-pointer transition-all hover:scale-[1.02] overflow-hidden" 
                 style="background: var(--bob-info-card-bg)"
                 @click="window.location='{{ route('reconciliation.locklist.index') }}'">
                <div class="absolute top-0 right-0 p-3 opacity-20 group-hover:opacity-40 transition-opacity">
                    <svg class="w-12 h-12 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                </div>
                <div class="relative z-10">
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] mb-1 block text-purple-400/70">Stored Intelligence</span>
                    <div class="text-3xl font-black mb-1" style="color: var(--bob-text-primary)">{{ number_format($lockList['total_rules']) }}</div>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="text-[10px] font-bold text-purple-400/80">{{ number_format($lockList['overrides_applied']) }} overrides applied</span>
                    </div>
                </div>
            </div>

            {{-- Rate --}}
            <div class="bob-glass-panel relative p-5 overflow-hidden">
                <div class="absolute top-0 right-0 p-3 opacity-20 transition-opacity">
                    <svg class="w-12 h-12 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" /></svg>
                </div>
                <div class="relative z-10">
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] mb-1 block text-sky-400/70">Resolution Velocity</span>
                    <div class="text-3xl font-black mb-1" style="color: var(--bob-text-primary)">{{ $pipeline['resolution_rate'] }}%</div>
                    {{-- Mini progress --}}
                    <div class="w-full h-1.5 bg-sky-900/40 rounded-full mt-3 overflow-hidden shadow-inner">
                        <div class="h-full bg-gradient-to-r from-sky-400 to-indigo-500 rounded-full transition-all duration-1000 ease-out" 
                             style="width: {{ $pipeline['resolution_rate'] }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── ROW 2: Command Center + Visual Pipeline ── --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            {{-- Today's Status Tracking --}}
            <div class="lg:col-span-3 space-y-6">
                <div class="bob-glass-panel p-6 h-full">
                    <h2 class="text-[10px] font-black tracking-[0.2em] uppercase mb-6 flex items-center gap-2" style="color: var(--bob-text-muted)">
                        <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" /></svg>
                        Operational Snapshot
                    </h2>
                    <div class="space-y-6">
                        <div class="flex items-center justify-between group">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center transition-transform group-hover:scale-110" style="background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.1);">
                                    <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                </div>
                                <div>
                                    <div class="text-[13px] font-bold" style="color: var(--bob-text-primary)">Resolved Today</div>
                                    <div class="text-[10px]" style="color: var(--bob-text-faint)">Finalized for payout</div>
                                </div>
                            </div>
                            <span class="text-xl font-black text-emerald-400">{{ $today['resolved_today'] }}</span>
                        </div>
                        <div class="flex items-center justify-between group">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center transition-transform group-hover:scale-110" style="background: rgba(99,102,241,0.08); border: 1px solid rgba(99,102,241,0.1);">
                                    <svg class="w-5 h-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg>
                                </div>
                                <div>
                                    <div class="text-[13px] font-bold" style="color: var(--bob-text-primary)">New Ingests</div>
                                    <div class="text-[10px]" style="color: var(--bob-text-faint)">Import batches</div>
                                </div>
                            </div>
                            <span class="text-xl font-black text-indigo-400">{{ $today['imports_today'] }}</span>
                        </div>
                        <div class="flex items-center justify-between group">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center transition-transform group-hover:scale-110" style="background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.1);">
                                    <svg class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                </div>
                                <div>
                                    <div class="text-[13px] font-bold" style="color: var(--bob-text-primary)">System Audit</div>
                                    <div class="text-[10px]" style="color: var(--bob-text-faint)">Log entries created</div>
                                </div>
                            </div>
                            <span class="text-xl font-black text-amber-400">{{ $today['audit_actions_today'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Command Center --}}
            <div class="lg:col-span-5">
                <div class="bob-glass-panel p-6 h-full">
                    <h2 class="text-[10px] font-black tracking-[0.2em] uppercase mb-6 flex items-center gap-2" style="color: var(--bob-text-muted)">
                         <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
                        Command Center
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <a href="{{ route('reconciliation.dashboard') }}" class="group relative flex items-start gap-4 p-5 rounded-2xl border transition-all hover:-translate-y-1 hover:shadow-2xl overflow-hidden" style="background: var(--bob-bg-input); border-color: var(--bob-border-light);">
                            <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 shadow-lg group-hover:scale-110 transition-transform" style="background: linear-gradient(135deg, rgba(99,102,241,0.2), rgba(99,102,241,0.1));">
                                <svg class="w-6 h-6 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                            </div>
                            <div class="relative z-10">
                                <div class="text-[14px] font-black group-hover:text-indigo-400 transition-colors" style="color: var(--bob-text-primary)">Resolver</div>
                                <div class="text-[10px] leading-relaxed mt-1" style="color: var(--bob-text-faint)">Operational grid for manual interventions.</div>
                            </div>
                        </a>
                        @can('reconciliation.etl.run')
                            <a href="{{ route('reconciliation.upload.index') }}" class="group relative flex items-start gap-4 p-5 rounded-2xl border transition-all hover:-translate-y-1 hover:shadow-2xl overflow-hidden" style="background: var(--bob-bg-input); border-color: var(--bob-border-light);">
                                <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 shadow-lg group-hover:scale-110 transition-transform" style="background: linear-gradient(135deg, rgba(16,185,129,0.2), rgba(16,185,129,0.1));">
                                    <svg class="w-6 h-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg>
                                </div>
                                <div class="relative z-10">
                                    <div class="text-[14px] font-black group-hover:text-emerald-400 transition-colors" style="color: var(--bob-text-primary)">Ingest</div>
                                    <div class="text-[10px] leading-relaxed mt-1" style="color: var(--bob-text-faint)">Import new carrier data (XLSX/CSV).</div>
                                </div>
                            </a>
                        @endcan
                        @can('reconciliation.results.view')
                            <a href="{{ route('reconciliation.reporting.final-bob') }}" class="group relative flex items-start gap-4 p-5 rounded-2xl border transition-all hover:-translate-y-1 hover:shadow-2xl overflow-hidden" style="background: var(--bob-bg-input); border-color: var(--bob-border-light);">
                                <div class="absolute inset-0 bg-gradient-to-br from-sky-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 shadow-lg group-hover:scale-110 transition-transform" style="background: linear-gradient(135deg, rgba(56,189,248,0.2), rgba(56,189,248,0.1));">
                                    <svg class="w-6 h-6 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0119.5 16.5h-2.25m-9 0h9l-4.5 5.25L9 16.5z" /></svg>
                                </div>
                                <div class="relative z-10">
                                    <div class="text-[14px] font-black group-hover:text-sky-400 transition-colors" style="color: var(--bob-text-primary)">Payouts</div>
                                    <div class="text-[10px] leading-relaxed mt-1" style="color: var(--bob-text-faint)">Final reconciled data for reporting.</div>
                                </div>
                            </a>
                        @endcan
                        <a href="{{ route('reconciliation.locklist.index') }}" class="group relative flex items-start gap-4 p-5 rounded-2xl border transition-all hover:-translate-y-1 hover:shadow-2xl overflow-hidden" style="background: var(--bob-bg-input); border-color: var(--bob-border-light);">
                            <div class="absolute inset-0 bg-gradient-to-br from-purple-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 shadow-lg group-hover:scale-110 transition-transform" style="background: linear-gradient(135deg, rgba(168,85,247,0.2), rgba(168,85,247,0.1));">
                                <svg class="w-6 h-6 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                            </div>
                            <div class="relative z-10">
                                <div class="text-[14px] font-black group-hover:text-purple-400 transition-colors" style="color: var(--bob-text-primary)">Rules</div>
                                <div class="text-[10px] leading-relaxed mt-1" style="color: var(--bob-text-faint)">Global persistent override logic.</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Visual Pipeline Visualization --}}
            <div class="lg:col-span-4">
                <div class="bob-glass-panel p-6 h-full relative overflow-hidden">
                    {{-- Subtle background pulse --}}
                    <div class="absolute -top-24 -right-24 w-48 h-48 bg-indigo-500/10 blur-[80px] animate-pulse"></div>
                    
                    <h2 class="text-[10px] font-black tracking-[0.2em] uppercase mb-8 flex items-center gap-2" style="color: var(--bob-text-muted)">
                         <svg class="w-4 h-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3C17.5228 3 22 5.01472 22 7.5V7.5C22 9.98528 17.5228 12 12 12C6.47715 12 2 9.98528 2 7.5V7.5C2 5.01472 6.47715 3 12 3Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M22 12C22 14.4853 17.5228 16.5 12 16.5C6.47715 16.5 2 14.4853 2 12" /><path stroke-linecap="round" stroke-linejoin="round" d="M22 16.5C22 18.9853 17.5228 21 12 21C6.47715 21 2 18.9853 2 16.5" /></svg>
                        Flow Optimization
                    </h2>
                    
                    <div class="relative space-y-10">
                        {{-- Vertical connecting line --}}
                        <div class="absolute left-1.5 top-2 bottom-2 w-[1px] bg-indigo-500/20 z-0"></div>

                        @php
                            $funnelTotal = max(1, $pipeline['total']);
                            $stages = [
                                ['label' => 'Source Layer',    'value' => $pipeline['total'],    'pct' => 100, 'color' => '#818cf8', 'sub' => 'Incoming Records'],
                                ['label' => 'Processing',      'value' => $pipeline['pending'],  'pct' => round($pipeline['pending'] / $funnelTotal * 100), 'color' => '#fbbf24', 'sub' => 'In Queue'],
                                ['label' => 'Critical Flags',  'value' => $pipeline['flagged'],  'pct' => round($pipeline['flagged'] / $funnelTotal * 100), 'color' => '#fb7185', 'sub' => 'Blocks Detected'],
                                ['label' => 'Production Ready','value' => $pipeline['resolved'], 'pct' => round($pipeline['resolved'] / $funnelTotal * 100),'color' => '#34d399', 'sub' => 'Final State'],
                            ];
                        @endphp

                        @foreach ($stages as $stage)
                        <div class="relative z-10 flex items-center gap-5">
                            <div class="w-3 h-3 rounded-full border-2 border-[#0b1120] shrink-0 transform shadow-[0_0_8px_rgba(0,0,0,0.5)] transition-all duration-700" 
                                 style="background: {{ $stage['color'] }}; box-shadow: 0 0 10px {{ $stage['color'] }}40;"></div>
                            <div class="flex-1">
                                <div class="flex items-end justify-between mb-2">
                                    <div>
                                        <div class="text-[11px] font-black uppercase tracking-wider" style="color: var(--bob-text-primary)">{{ $stage['label'] }}</div>
                                        <div class="text-[9px] font-medium opacity-50" style="color: var(--bob-text-muted)">{{ $stage['sub'] }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-[12px] font-black" style="color: {{ $stage['color'] }}">{{ number_format($stage['value']) }}</div>
                                        <div class="text-[9px] font-bold opacity-40 uppercase tracking-tighter" style="color: var(--bob-text-faint)">{{ $stage['pct'] }}% Yield</div>
                                    </div>
                                </div>
                                <div class="h-1.5 rounded-full bg-slate-800/60 overflow-hidden shadow-inner">
                                    <div class="h-full rounded-full transition-all duration-1000 ease-out" 
                                         style="width: {{ $stage['pct'] }}%; background: {{ $stage['color'] }}; box-shadow: 0 0 8px {{ $stage['color'] }}30;"></div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- ── ROW 3: System Intelligence & Audit Trail ── --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            {{-- Audit Trail / Activity --}}
            <div class="lg:col-span-4 max-h-[500px]">
                <div class="bob-glass-panel p-6 h-full flex flex-col">
                    <div class="flex items-center justify-between mb-8">
                        <h2 class="text-[10px] font-black tracking-[0.2em] uppercase flex items-center gap-2" style="color: var(--bob-text-muted)">
                            <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Activity Stream
                        </h2>
                        @can('bulkApprove', \App\Models\ReconciliationQueue::class)
                            <a href="{{ route('reconciliation.audit-logs') }}" class="text-[10px] font-bold text-indigo-400 hover:text-indigo-300 transition-colors uppercase tracking-widest">Global Log →</a>
                        @endcan
                    </div>
                    <div class="flex-1 overflow-y-auto pr-2 custom-scrollbar space-y-4">
                        @forelse($recentActivity as $log)
                        <div class="relative pl-6 group">
                            {{-- Timeline line --}}
                            <div class="absolute left-0 top-1 bottom-0 w-[1px] bg-slate-800 group-last:bg-transparent"></div>
                            {{-- Timeline dot --}}
                            <div class="absolute left-[-3px] top-1.5 w-1.5 h-1.5 rounded-full border border-slate-900 z-10" 
                                 style="background: {{ str_contains($log->action, 'resolved') ? '#34d399' : (str_contains($log->action, 'flagged') ? '#fb7185' : '#818cf8') }}"></div>
                            
                            <div class="p-3 rounded-xl border transition-all hover:bg-white/[0.02]" style="background: var(--bob-bg-input); border-color: var(--bob-border-light);">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-[11px] font-black truncate" style="color: var(--bob-text-primary)">{{ optional($log->modifiedBy)->name ?? 'System' }}</span>
                                    <span class="text-[9px] font-bold text-slate-500">{{ $log->created_at->diffForHumans() }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    @php
                                        $actionColors = [
                                            'resolved' => ['text' => '#34d399', 'bg' => 'rgba(52,211,153,0.1)'],
                                            'flagged' => ['text' => '#fb7185', 'bg' => 'rgba(251,113,133,0.1)'],
                                            'lock_acquired' => ['text' => '#fbbf24', 'bg' => 'rgba(245,158,11,0.1)'],
                                        ];
                                        $meta = $actionColors[strtolower($log->action)] ?? ['text' => '#94a3b8', 'bg' => 'rgba(255,255,255,0.05)'];
                                    @endphp
                                    <span class="text-[9px] font-black uppercase tracking-tighter px-1.5 py-0.5 rounded" style="color: {{ $meta['text'] }}; background: {{ $meta['bg'] }};">
                                        {{ str_replace('_', ' ', $log->action) }}
                                    </span>
                                    <span class="text-[10px] font-mono truncate opacity-60" style="color: var(--bob-text-muted)">{{ $log->transaction_id }}</span>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="h-full flex items-center justify-center text-xs opacity-40 italic">Quiet day... no events yet</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Ingest Management --}}
            <div class="lg:col-span-5">
                <div class="bob-glass-panel p-6 h-full flex flex-col">
                    <div class="flex items-center justify-between mb-8">
                        <h2 class="text-[10px] font-black tracking-[0.2em] uppercase flex items-center gap-2" style="color: var(--bob-text-muted)">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
                            Ingest History
                        </h2>
                        @can('reconciliation.etl.run')
                            <a href="{{ route('reconciliation.upload.index') }}" class="text-[10px] font-bold text-emerald-400 hover:text-emerald-300 transition-colors uppercase tracking-widest">View Queue →</a>
                        @endcan
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-[9px] font-black uppercase tracking-[0.1em]" style="color: var(--bob-text-faint)">
                                    <th class="text-left py-3 px-2">Data Source</th>
                                    <th class="text-left py-3 px-2">Integrity</th>
                                    <th class="text-right py-3 px-2">Volume</th>
                                    <th class="text-right py-3 px-2">Temporal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/[0.02]">
                                @forelse($recentBatches as $batch)
                                <tr class="group hover:bg-white/[0.02] transition-colors">
                                    <td class="py-4 px-2">
                                        <div class="text-[12px] font-bold" style="color: var(--bob-text-primary)">{{ Str::limit($batch->carrier_original_name ?: 'Batch '.$batch->id, 24) }}</div>
                                        <div class="text-[9px] flex items-center gap-1.5" style="color: var(--bob-text-muted)">
                                             <div class="w-1 h-1 rounded-full bg-slate-500"></div>
                                             User: {{ optional($batch->uploadedBy)->name ?? 'Auto' }}
                                        </div>
                                    </td>
                                    <td class="py-4 px-2">
                                        @php
                                            $s = [
                                                'completed' => ['c' => '#34d399', 'l' => 'Verified'],
                                                'processing'=> ['c' => '#fbbf24', 'l' => 'Active'],
                                                'failed'    => ['c' => '#fb7185', 'l' => 'Rejected'],
                                            ][$batch->status] ?? ['c' => '#94a3b8', 'l' => 'Queued'];
                                        @endphp
                                        <div class="flex items-center gap-2">
                                            <div class="w-1.5 h-1.5 rounded-full shadow-[0_0_8px_currentColor]" style="background: {{ $s['c'] }}; color: {{ $s['c'] }};"></div>
                                            <span class="text-[10px] font-black uppercase tracking-tight" style="color: {{ $s['c'] }}">{{ $s['l'] }}</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-2 text-right font-mono text-[11px] font-bold" style="color: var(--bob-text-primary)">
                                        {{ number_format($batch->total_records ?? 0) }}
                                    </td>
                                    <td class="py-4 px-2 text-right text-[10px] font-medium" style="color: var(--bob-text-faint)">
                                        {{ $batch->created_at->diffForHumans() }}
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="py-12 text-center text-xs opacity-40">No ingestion data available</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- System Analytics Recap --}}
            <div class="lg:col-span-3">
                <div class="bob-glass-panel p-6 h-full flex flex-col">
                    <h2 class="text-[10px] font-black tracking-[0.2em] uppercase mb-8 flex items-center gap-2" style="color: var(--bob-text-muted)">
                        <svg class="w-4 h-4 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" /></svg>
                        System Intelligence
                    </h2>
                    
                    <div class="space-y-5 flex-1">
                        <div class="p-3 rounded-xl border flex items-center justify-between" style="background: var(--bob-bg-input); border-color: var(--bob-border-light);">
                            <span class="text-[10px] font-bold" style="color: var(--bob-text-faint)">Match Methods</span>
                            <div class="flex gap-1">
                                <div class="w-1.5 h-4 bg-indigo-500 rounded-sm"></div>
                                <div class="w-1.5 h-6 bg-purple-500 rounded-sm"></div>
                                <div class="w-1.5 h-3 bg-emerald-500 rounded-sm"></div>
                            </div>
                        </div>

                        <div class="space-y-4 pt-2">
                            <div class="flex items-center justify-between group cursor-help">
                                <span class="text-[11px] font-bold" style="color: var(--bob-text-secondary)">Automation Rules</span>
                                <span class="text-xs font-black text-purple-400">{{ number_format($lockList['total_rules']) }}</span>
                            </div>
                            <div class="flex items-center justify-between group cursor-help">
                                <span class="text-[11px] font-bold" style="color: var(--bob-text-secondary)">Batch Efficiency</span>
                                <span class="text-xs font-black text-emerald-400">{{ number_format($pipeline['resolution_rate'], 1) }}%</span>
                            </div>
                            <div class="flex items-center justify-between group cursor-help">
                                <span class="text-[11px] font-bold" style="color: var(--bob-text-secondary)">Match Integrity</span>
                                <span class="text-xs font-black text-indigo-400">High</span>
                            </div>
                        </div>

                        <div class="mt-auto pt-6 border-t border-white/[0.04]">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-[10px] font-black bg-indigo-500/20 text-indigo-300">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="text-[10px] font-black" style="color: var(--bob-text-primary)">{{ $user->roles->pluck('name')->first() ?? 'Expert' }}</div>
                                    <div class="text-[9px] opacity-40 uppercase tracking-tighter" style="color: var(--bob-text-muted)">Active Session</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('homeHub', () => ({}));
        });
    </script>
    @endpush
</x-reconciliation-layout>
