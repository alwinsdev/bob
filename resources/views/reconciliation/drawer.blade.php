{{-- ── Resolve & Flag Drawer (Dark Premium Theme) ── --}}
<div x-show="drawerOpen" x-cloak
     class="fixed inset-0 overflow-hidden z-50"
     x-transition:enter="transition-opacity ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-[#0b1120]/80 backdrop-blur-sm transition-opacity" @click="closeDrawer()"></div>

    {{-- Panel --}}
    <div class="fixed inset-y-0 right-0 flex max-w-full pl-10">
        <div class="w-screen max-w-md shadow-2xl flex flex-col border-l border-white/5" style="background-color: #111827;"
             x-show="drawerOpen"
             x-transition:enter="transform transition ease-out duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transform transition ease-in duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full">

            {{-- Header --}}
            <div class="px-6 py-5 flex justify-between items-center bg-[#0b1120]/50 border-b border-white/5">
                <div>
                    <h2 class="text-base font-bold text-white tracking-wide">Resolve Exception</h2>
                    <p class="text-xs mt-0.5 font-medium text-slate-400">Review & align this record</p>
                </div>
                <button @click="closeDrawer()" class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-white/10 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-6">
                <template x-if="activeRecord">
                    <div class="space-y-6">

                        {{-- Record Context --}}
                        <div class="rounded-xl p-5 border border-white/5 bg-[#0b1120]">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(99,102,241,0.15);">
                                    <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" /></svg>
                                </div>
                                <span class="text-[11px] font-bold uppercase tracking-widest text-indigo-400">Record Context</span>
                            </div>
                            <div class="grid grid-cols-2 gap-x-4 gap-y-4">
                                <div>
                                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Transaction</div>
                                    <div class="font-mono text-xs mt-1 text-white truncate" x-text="activeRecord.transaction_id" :title="activeRecord.transaction_id"></div>
                                </div>
                                <div>
                                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Contract</div>
                                    <div class="font-mono text-xs mt-1 text-white truncate" x-text="activeRecord.contract_id" :title="activeRecord.contract_id"></div>
                                </div>
                                <div class="col-span-2">
                                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Client Name</div>
                                    <div class="text-sm font-semibold mt-1 text-white" x-text="activeRecord.member_first_name + ' ' + activeRecord.member_last_name"></div>
                                </div>
                                <div class="col-span-2">
                                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Carrier</div>
                                    <div class="text-sm font-semibold mt-1 text-slate-300" x-text="activeRecord.carrier"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Fuzzy Match --}}
                        <div x-show="activeRecord.match_confidence" class="rounded-xl p-5 border border-white/5" style="background: rgba(16,185,129,0.05);">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(16,185,129,0.15);">
                                    <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" /></svg>
                                </div>
                                <span class="text-[11px] font-bold uppercase tracking-widest text-emerald-400">Fuzzy Match</span>
                            </div>
                            <div class="flex items-center justify-between text-xs mb-3 font-semibold">
                                <span class="text-emerald-500/70" x-text="activeRecord.match_method || 'similar_text'"></span>
                                <span class="font-bold text-emerald-400" x-text="activeRecord.match_confidence + '% Confidence'"></span>
                            </div>
                            <div class="w-full h-2 rounded-full overflow-hidden" style="background: rgba(255,255,255,0.1);">
                                <div class="h-full rounded-full transition-all duration-700" style="background: linear-gradient(90deg, #10b981, #34d399);" :style="'width: ' + activeRecord.match_confidence + '%'"></div>
                            </div>
                        </div>

                        {{-- Resolution Form --}}
                        <form @submit.prevent="submitResolution" id="resolutionForm" class="space-y-4">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(245,158,11,0.15);">
                                    <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" /></svg>
                                </div>
                                <span class="text-[11px] font-bold uppercase tracking-widest text-amber-500">Alignment</span>
                            </div>

                            <div>
                                <label class="bob-form-label">Align to Agent (Code)</label>
                                <input x-model="resolutionData.aligned_agent_code" type="text" class="bob-form-input" required placeholder="E.g. AGT-0001">
                                <p class="mt-2 text-[11px] font-semibold text-indigo-400 flex items-center gap-1.5" x-show="activeRecord.agent_id">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" /></svg>
                                    IMS Suggested: <span class="px-2 py-0.5 rounded cursor-pointer transition-colors" style="background: rgba(99,102,241,0.2);" x-text="activeRecord.agent_id" @click="resolutionData.aligned_agent_code = activeRecord.agent_id"></span>
                                </p>
                            </div>

                            <div>
                                <label class="bob-form-label">Compensation Type</label>
                                <select x-model="resolutionData.compensation_type" class="bob-form-input appearance-none" required>
                                    <option value="" disabled>Select Type</option>
                                    <option value="New">New Business</option>
                                    <option value="Renewal">Renewal</option>
                                    <option value="Override">Override</option>
                                </select>
                            </div>
                        </form>
                    </div>
                </template>

                <template x-if="!activeRecord">
                    <div class="flex items-center justify-center h-full">
                        <div class="w-10 h-10 rounded-full border-4 border-indigo-500/30 border-t-indigo-500 animate-spin"></div>
                    </div>
                </template>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-5 bg-[#0b1120]/50 border-t border-white/5 flex justify-end gap-3">
                <button type="button" @click="flagRecord()" class="bob-btn-ghost text-rose-400 hover:bg-rose-500/10" style="color: #fb7185;">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0l2.77-.693a9 9 0 016.208.682l.108.054a9 9 0 006.086.71l3.114-.732a48.524 48.524 0 01-.005-10.499l-3.11.732a9 9 0 01-6.085-.711l-.108-.054a9 9 0 00-6.208-.682L3 4.5M3 15V4.5" /></svg>
                    Flag
                </button>
                <button type="submit" form="resolutionForm" class="bob-btn-primary">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                    Save & Resolve
                </button>
            </div>
        </div>
    </div>
</div>