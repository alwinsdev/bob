<x-reconciliation-layout>
    <x-slot name="pageTitle">Import Feeds</x-slot>
    <x-slot name="pageSubtitle">Synchronize Book of Business (Carrier) with Agent & Transaction Data</x-slot>

    <x-slot name="headerActions">
        <a href="{{ route('reconciliation.dashboard') }}" class="bob-btn-ghost">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Back to Grid
        </a>
    </x-slot>

    <div class="space-y-8" x-data="uploadForm()">

        {{-- ══════════════════════════════════════════════════════════
        SECTION 1: UNIFIED UPLOAD ENGINE
        ══════════════════════════════════════════════════════════ --}}
        <div class="bob-glass-panel">
            <form action="{{ route('reconciliation.upload.store') }}" method="POST" enctype="multipart/form-data"
                @submit.prevent="uploadMode === 'contract' ? submitContractPatch($event) : submitSynchronization($event)"
                x-ref="syncForm">
                @csrf

                {{-- ── Panel Header ──────────────────────────────────────── --}}
                <div class="px-6 py-5 flex items-center justify-between border-b border-white/5">
                    <div>
                        <h3 class="text-sm font-bold text-white tracking-wide uppercase">ETL Engine</h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">Upload your Carrier feed and any available source
                            feeds. The engine will cascade-match IMS → Health Sherpa automatically.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="button"
                            @click.prevent="setUploadMode(uploadMode === 'contract' ? 'standard' : 'contract')"
                            class="inline-flex items-center gap-1.5 text-[10px] font-bold tracking-wider px-3 py-1.5 rounded-lg border transition-all"
                            :style="uploadMode === 'contract'
                                    ? 'background:rgba(96,165,250,0.14);color:#93c5fd;border-color:rgba(96,165,250,0.28);'
                                    : 'background:rgba(20,184,166,0.14);color:#5eead4;border-color:rgba(45,212,191,0.28);'">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            <span
                                x-text="uploadMode === 'contract' ? 'Back to Weekly Sync' : 'Upload Contract File'"></span>
                        </button>
                        <select id="duplicate_strategy" name="duplicate_strategy" class="bob-select text-xs">
                            <option value="skip">Skip on Duplicate</option>
                            <option value="update">Force Update</option>
                        </select>
                    </div>
                </div>

                {{-- ── Upload Zones ──────────────────────────────────────── --}}
                <div class="p-6 space-y-5">
                    <div x-show="reanalysisMode && reanalysisTargetBatch" x-cloak
                        class="rounded-xl border p-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2"
                        style="background:rgba(59,130,246,0.08);border-color:rgba(59,130,246,0.22);">
                        <div>
                            <div class="text-[10px] font-black uppercase tracking-widest text-blue-300">In-place
                                Re-analysis Active</div>
                            <div class="text-[11px] text-slate-300 mt-1">
                                Target Run <span class="font-mono text-blue-200"
                                    x-text="`#${reanalysisTargetBatch.id}`"></span>
                                <span class="text-slate-500">·</span>
                                Existing records for this run will be replaced.
                            </div>
                        </div>
                        <button type="button" @click="cancelInlineReanalysis()"
                            class="inline-flex items-center gap-1.5 text-[10px] font-bold tracking-wider px-2.5 py-1.5 rounded-lg"
                            style="background:rgba(15,23,42,0.4);color:#bfdbfe;border:1px solid rgba(96,165,250,0.26);">
                            Cancel Re-analysis
                        </button>
                    </div>

                    <div class="relative overflow-hidden">
                        <div x-show="uploadMode === 'standard'"
                            x-transition:enter="transition transform ease-out duration-300"
                            x-transition:enter-start="translate-x-8 opacity-0"
                            x-transition:enter-end="translate-x-0 opacity-100"
                            x-transition:leave="transition transform ease-in duration-220"
                            x-transition:leave-start="translate-x-0 opacity-100"
                            x-transition:leave-end="-translate-x-8 opacity-0" class="space-y-5">

                            {{-- Row 1: Carrier Feed (always required — full width) --}}
                            <div>
                                <label class="bob-form-label text-[#fbbf24] flex justify-between items-center mb-2">
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-[#fbbf24]"></span>
                                        Carrier Feed (BOB)
                                        <span class="text-[10px] font-bold tracking-wider ml-1 px-1.5 py-0.5 rounded"
                                            style="background:rgba(251,191,36,0.1);color:#fbbf24;">Required</span>
                                    </span>
                                    @error('carrier_file')
                                        <span
                                            class="text-[10px] text-rose-400 font-bold tracking-wider flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                            </svg>
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </label>
                                <p x-show="reanalysisMode" x-cloak class="text-[10px] text-slate-500 mb-2">
                                    Current stored file:
                                    <span class="font-medium text-slate-300"
                                        x-text="storedSourceName('carrier') || 'Not available'"
                                        style="word-break:break-all;"></span>
                                </p>
                                <label for="carrier-dropzone" class="bob-dropzone group transition-all" :class="{
                                   '!border-[#fbbf24]/60 !bg-[#fbbf24]/5': carrierFileName,
                                   '!border-rose-500/50 !bg-rose-500/5': {{ $errors->has('carrier_file') ? 'true' : 'false' }} && !carrierFileName
                               }">
                                    <template x-if="!carrierFileName">
                                        <div class="flex flex-col items-center justify-center py-4 text-slate-400">
                                            <svg class="w-9 h-9 mb-3 text-[#fbbf24]/50 group-hover:text-[#fbbf24] transition-colors duration-300"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                            </svg>
                                            <p class="text-xs"><span class="font-bold text-[#fbbf24]">Browse</span> or
                                                drag & drop the BOB Carrier file</p>
                                            <p class="text-[10px] mt-1 text-slate-500">CSV / XLSX — up to 50 MB</p>
                                        </div>
                                    </template>
                                    <template x-if="carrierFileName">
                                        <div class="flex items-center gap-3 py-3 w-full">
                                            <div class="w-9 h-9 shrink-0 rounded-lg flex items-center justify-center"
                                                style="background:rgba(251,191,36,0.12);">
                                                <svg class="w-4 h-4 text-[#fbbf24]" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-xs font-bold text-white truncate"
                                                    x-text="carrierFileName"></div>
                                                <div class="text-[10px] text-slate-400" x-text="carrierFileSize"></div>
                                            </div>
                                            <button type="button" @click.prevent="clearFile('carrier')"
                                                class="shrink-0 text-slate-500 hover:text-rose-400 transition-colors">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </template>
                                    <input id="carrier-dropzone" type="file" name="carrier_file" class="hidden"
                                        accept=".csv,.xls,.xlsx" @change="handleFile($event,'carrier')" />
                                </label>
                            </div>

                            {{-- Divider with hint --}}
                            <div class="flex items-center gap-4">
                                <div class="flex-1 h-px" style="background:rgba(255,255,255,0.05);"></div>
                                <span class="text-[10px] font-bold tracking-widest text-slate-500 uppercase">Source
                                    Feeds — at least one required</span>
                                <div class="flex-1 h-px" style="background:rgba(255,255,255,0.05);"></div>
                            </div>

                            {{-- Row 2: IMS + Payee + Health Sherpa (3-column grid) --}}
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                                {{-- IMS Agent Data --}}
                                <div>
                                    <label class="bob-form-label text-[#60a5fa] flex justify-between items-center mb-2">
                                        <span class="inline-flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full bg-[#60a5fa]"></span>
                                            Agent Data (IMS)
                                            <span
                                                class="text-[10px] tracking-wider ml-1 px-1.5 py-0.5 rounded text-slate-500"
                                                style="background:rgba(255,255,255,0.04);">Optional</span>
                                        </span>
                                        @error('ims_file')
                                            <span class="text-[10px] text-rose-400 font-bold">{{ $message }}</span>
                                        @enderror
                                    </label>
                                    <p x-show="reanalysisMode" x-cloak class="text-[10px] text-slate-500 mb-2">
                                        Current stored file:
                                        <span class="font-medium text-slate-300"
                                            x-text="storedSourceName('ims') || 'Not provided in source run'"
                                            style="word-break:break-all;"></span>
                                    </p>
                                    <label for="ims-dropzone" class="bob-dropzone group transition-all" :class="{
                                       '!border-[#60a5fa]/60 !bg-[#60a5fa]/5': imsFileName,
                                       '!border-rose-500/50 !bg-rose-500/5': {{ $errors->has('ims_file') ? 'true' : 'false' }} && !imsFileName
                                   }">
                                        <template x-if="!imsFileName">
                                            <div class="flex flex-col items-center justify-center py-3 text-slate-400">
                                                <svg class="w-8 h-8 mb-3 text-[#60a5fa]/50 group-hover:text-[#60a5fa] transition-colors duration-300"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                                </svg>
                                                <p class="text-xs"><span class="font-bold text-[#60a5fa]">Browse</span>
                                                    or drop IMS file</p>
                                                <p class="text-[10px] mt-1 text-slate-500">Transaction export</p>
                                            </div>
                                        </template>
                                        <template x-if="imsFileName">
                                            <div class="flex items-center gap-3 py-3 w-full">
                                                <div class="w-9 h-9 shrink-0 rounded-lg flex items-center justify-center"
                                                    style="background:rgba(96,165,250,0.12);">
                                                    <svg class="w-4 h-4 text-[#60a5fa]" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="text-xs font-bold text-white truncate"
                                                        x-text="imsFileName"></div>
                                                    <div class="text-[10px] text-slate-400" x-text="imsFileSize"></div>
                                                </div>
                                                <button type="button" @click.prevent="clearFile('ims')"
                                                    class="shrink-0 text-slate-500 hover:text-rose-400 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </template>
                                        <input id="ims-dropzone" type="file" name="ims_file" class="hidden"
                                            accept=".csv,.xls,.xlsx" @change="handleFile($event,'ims')" />
                                    </label>
                                </div>

                                {{-- Agency Payee Details --}}
                                <div>
                                    <label class="bob-form-label text-[#c084fc] flex justify-between items-center mb-2">
                                        <span class="inline-flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full bg-[#c084fc]"></span>
                                            Agency Payee Details
                                            <span
                                                class="text-[10px] tracking-wider ml-1 px-1.5 py-0.5 rounded text-slate-500"
                                                style="background:rgba(255,255,255,0.04);">Optional</span>
                                        </span>
                                        @error('payee_file')
                                            <span class="text-[10px] text-rose-400 font-bold">{{ $message }}</span>
                                        @enderror
                                    </label>
                                    <p x-show="reanalysisMode" x-cloak class="text-[10px] text-slate-500 mb-2">
                                        Current stored file:
                                        <span class="font-medium text-slate-300"
                                            x-text="storedSourceName('payee') || 'Not provided in source run'"
                                            style="word-break:break-all;"></span>
                                    </p>
                                    <label for="payee-dropzone" class="bob-dropzone group transition-all"
                                        :class="{ '!border-[#c084fc]/60 !bg-[#c084fc]/5': payeeFileName }">
                                        <template x-if="!payeeFileName">
                                            <div class="flex flex-col items-center justify-center py-3 text-slate-400">
                                                <svg class="w-8 h-8 mb-3 text-[#c084fc]/50 group-hover:text-[#c084fc] transition-colors duration-300"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                                                </svg>
                                                <p class="text-xs"><span class="font-bold text-[#c084fc]">Browse</span>
                                                    or drop Payee file</p>
                                                <p class="text-[10px] mt-1 text-slate-500">Department → Payee map</p>
                                            </div>
                                        </template>
                                        <template x-if="payeeFileName">
                                            <div class="flex items-center gap-3 py-3 w-full">
                                                <div class="w-9 h-9 shrink-0 rounded-lg flex items-center justify-center"
                                                    style="background:rgba(192,132,252,0.12);">
                                                    <svg class="w-4 h-4 text-[#c084fc]" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="text-xs font-bold text-white truncate"
                                                        x-text="payeeFileName"></div>
                                                    <div class="text-[10px] text-slate-400" x-text="payeeFileSize">
                                                    </div>
                                                </div>
                                                <button type="button" @click.prevent="clearFile('payee')"
                                                    class="shrink-0 text-slate-500 hover:text-rose-400 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </template>
                                        <input id="payee-dropzone" type="file" name="payee_file" class="hidden"
                                            accept=".csv,.xls,.xlsx" @change="handleFile($event,'payee')" />
                                    </label>
                                </div>

                                {{-- Health Sherpa --}}
                                <div>
                                    <label class="bob-form-label text-[#34d399] flex justify-between items-center mb-2">
                                        <span class="inline-flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full bg-[#34d399]"></span>
                                            Health Sherpa
                                            <span
                                                class="text-[10px] tracking-wider ml-1 px-1.5 py-0.5 rounded text-slate-500"
                                                style="background:rgba(255,255,255,0.04);">Optional</span>
                                        </span>
                                        @error('health_sherpa_file')
                                            <span class="text-[10px] text-rose-400 font-bold">{{ $message }}</span>
                                        @enderror
                                    </label>
                                    <p x-show="reanalysisMode" x-cloak class="text-[10px] text-slate-500 mb-2">
                                        Current stored file:
                                        <span class="font-medium text-slate-300"
                                            x-text="storedSourceName('hs') || 'Not provided in source run'"
                                            style="word-break:break-all;"></span>
                                    </p>
                                    <label for="hs-dropzone" class="bob-dropzone group transition-all" :class="{
                                       '!border-[#34d399]/60 !bg-[#34d399]/5': hsFileName,
                                       '!border-rose-500/50 !bg-rose-500/5': {{ $errors->has('health_sherpa_file') ? 'true' : 'false' }} && !hsFileName
                                   }">
                                        <template x-if="!hsFileName">
                                            <div class="flex flex-col items-center justify-center py-3 text-slate-400">
                                                <svg class="w-8 h-8 mb-3 text-[#34d399]/50 group-hover:text-[#34d399] transition-colors duration-300"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                                                </svg>
                                                <p class="text-xs"><span class="font-bold text-[#34d399]">Browse</span>
                                                    or drop HS file</p>
                                                <p class="text-[10px] mt-1 text-slate-500">Health Sherpa export</p>
                                            </div>
                                        </template>
                                        <template x-if="hsFileName">
                                            <div class="flex items-center gap-3 py-3 w-full">
                                                <div class="w-9 h-9 shrink-0 rounded-lg flex items-center justify-center"
                                                    style="background:rgba(52,211,153,0.12);">
                                                    <svg class="w-4 h-4 text-[#34d399]" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="text-xs font-bold text-white truncate"
                                                        x-text="hsFileName"></div>
                                                    <div class="text-[10px] text-slate-400" x-text="hsFileSize"></div>
                                                </div>
                                                <button type="button" @click.prevent="clearFile('hs')"
                                                    class="shrink-0 text-slate-500 hover:text-rose-400 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </template>
                                        <input id="hs-dropzone" type="file" name="health_sherpa_file" class="hidden"
                                            accept=".csv,.xls,.xlsx" @change="handleFile($event,'hs')" />
                                    </label>
                                </div>
                            </div>

                            {{-- Readiness indicator bar --}}
                            <div class="flex items-center gap-3 p-3 rounded-lg"
                                style="background:rgba(255,255,255,0.025); border:1px solid rgba(255,255,255,0.05);">
                                <div class="flex items-center gap-2 flex-1">
                                    <span class="text-[10px] font-bold tracking-widest text-slate-500 uppercase">Engine
                                        Readiness</span>
                                    <div class="flex items-center gap-1.5">
                                        <span class="flex items-center gap-1 text-[10px]"
                                            :class="sourceReady('carrier') ? 'text-emerald-400' : 'text-slate-600'">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2.5">
                                                <path x-show="sourceReady('carrier')" stroke-linecap="round"
                                                    stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                <path x-show="!sourceReady('carrier')" stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                            </svg>
                                            BOB
                                        </span>
                                        <span class="text-slate-700">·</span>
                                        <span class="flex items-center gap-1 text-[10px]"
                                            :class="sourceReady('ims') ? 'text-[#60a5fa]' : 'text-slate-600'">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2.5">
                                                <path x-show="sourceReady('ims')" stroke-linecap="round"
                                                    stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                <path x-show="!sourceReady('ims')" stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            IMS
                                        </span>
                                        <span class="text-slate-700">·</span>
                                        <span class="flex items-center gap-1 text-[10px]"
                                            :class="sourceReady('hs') ? 'text-[#34d399]' : 'text-slate-600'">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2.5">
                                                <path x-show="sourceReady('hs')" stroke-linecap="round"
                                                    stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                <path x-show="!sourceReady('hs')" stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Health Sherpa
                                        </span>
                                    </div>
                                </div>
                                <div class="shrink-0 text-[10px] font-medium"
                                    :class="isReady ? 'text-emerald-400' : 'text-amber-400'"
                                    x-text="isReady ? (reanalysisMode ? '✓ Ready to re-analyze' : '✓ Ready to run') : 'Upload required files'">
                                </div>
                            </div>

                        </div>

                        {{-- ══ CONTRACT PATCH ZONE ══════════════════════════════════════ --}}
                        <div x-show="uploadMode === 'contract'" x-cloak
                            x-transition:enter="transition transform ease-out duration-320"
                            x-transition:enter-start="-translate-x-8 opacity-0"
                            x-transition:enter-end="translate-x-0 opacity-100"
                            x-transition:leave="transition transform ease-in duration-220"
                            x-transition:leave-start="translate-x-0 opacity-100"
                            x-transition:leave-end="translate-x-8 opacity-0" class="space-y-5">
                            <div class="rounded-xl border p-4"
                                style="background:rgba(20,184,166,0.05);border-color:rgba(45,212,191,0.2);">
                                <div class="flex items-center justify-between gap-3 mb-4">
                                    <div>
                                        <h4 class="text-xs font-bold uppercase tracking-[0.12em] text-teal-300">Contract
                                            Patch Engine</h4>
                                        <p class="text-[11px] text-slate-400 mt-1">Upload a contract file to patch only
                                            flagged House Open / House Close queue records by Contract ID.</p>
                                    </div>
                                    <span class="text-[10px] font-bold tracking-widest px-2 py-1 rounded"
                                        style="background:rgba(20,184,166,0.14);color:#5eead4;">Mid-Week Patch</span>
                                </div>

                                {{-- ── Step 1: Active Final BOB Run ────────────────── --}}
                                <div class="mb-4">
                                    <label class="bob-form-label text-teal-300 flex justify-between items-center mb-2">
                                        <span class="inline-flex items-center gap-1.5">
                                            <span
                                                class="w-5 h-5 rounded-full text-[9px] font-black flex items-center justify-center shrink-0"
                                                style="background:rgba(45,212,191,0.14);color:#5eead4;">1</span>
                                            Active Target (Final BOB)
                                        </span>
                                    </label>
                                    <template x-if="latestParentBatch">
                                        <div class="flex items-center gap-3 p-3 rounded-lg border relative overflow-hidden shadow-sm"
                                            style="background: linear-gradient(145deg, rgba(15,23,42,0.6) 0%, rgba(30,41,59,0.4) 100%); border-color: rgba(45,212,191,0.2);">
                                            <div class="absolute inset-0 pointer-events-none"
                                                style="background: radial-gradient(circle at top right, rgba(45,212,191,0.05) 0%, transparent 60%);">
                                            </div>
                                            <div class="w-9 h-9 rounded-md flex items-center justify-center shrink-0 z-10"
                                                style="background:rgba(45,212,191,0.12); box-shadow: 0 2px 8px rgba(45,212,191,0.15) inset;">
                                                <svg class="w-4.5 h-4.5 text-teal-400" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                                </svg>
                                            </div>
                                            <div class="flex-1 min-w-0 z-10">
                                                <div class="flex items-center justify-between">
                                                    <div class="text-[12px] font-bold text-slate-100 truncate"
                                                        x-text="`Final BOB Output (${latestParentBatch.date_short})`">
                                                    </div>
                                                    <span
                                                        class="text-[9px] font-bold uppercase tracking-widest px-1.5 py-0.5 rounded text-teal-300"
                                                        style="background: rgba(20,184,166,0.15);">Target Valid</span>
                                                </div>
                                                <div
                                                    class="text-[10px] text-teal-400/80 mt-1 font-medium flex items-center gap-1.5">
                                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <span
                                                        x-text="`Auto-targeting latest processed Run (#${latestParentBatch.id})`"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="!latestParentBatch">
                                        <div class="p-3.5 border rounded-lg shadow-sm"
                                            style="background:rgba(225,29,72,0.06);border-color:rgba(225,29,72,0.18);">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-rose-400" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                                <div class="text-[11px] font-bold text-rose-300">No Final BOB Runs
                                                    Available (<span x-text="debugInfo"></span>)</div>
                                            </div>
                                            <div
                                                class="text-[10px] text-rose-400/80 mt-1.5 leading-relaxed font-medium">
                                                You must complete a standard synchronization first. If a sync is
                                                currently running, wait for it to finish.</div>
                                        </div>
                                    </template>
                                    <template x-if="isStandardSyncRunning">
                                        <div class="mt-3 p-2.5 border rounded-lg shadow-sm flex items-start gap-2.5"
                                            style="background:rgba(245,158,11,0.06);border-color:rgba(245,158,11,0.2);">
                                            <svg class="w-4 h-4 text-amber-500 mt-0.5 shrink-0 animate-spin" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                            </svg>
                                            <div>
                                                <div class="text-[10.5px] font-bold text-amber-400">Standard Sync in
                                                    Progress</div>
                                                <div
                                                    class="text-[9px] text-amber-500/80 mt-0.5 font-medium leading-relaxed">
                                                    A main synchronization is currently running. You cannot apply a
                                                    contract patch until it generates the Final BOB output.</div>
                                            </div>
                                        </div>
                                    </template>
                                    <p class="text-[10px] text-slate-500 mt-2.5">
                                        The patch run automatically processes against the most recent successful Final
                                        BOB run.
                                    </p>
                                </div>

                                {{-- ── Step 2: Upload Contract File ─────────────────────── --}}
                                <label class="bob-form-label text-teal-300 flex justify-between items-center mb-2">
                                    <span class="inline-flex items-center gap-1.5">
                                        <span
                                            class="w-5 h-5 rounded-full text-[9px] font-black flex items-center justify-center shrink-0"
                                            style="background:rgba(45,212,191,0.14);color:#5eead4;">2</span>
                                        Contract File
                                        <span class="text-[10px] font-bold tracking-wider ml-1 px-1.5 py-0.5 rounded"
                                            style="background:rgba(45,212,191,0.14);color:#5eead4;"
                                            x-text="reanalysisMode ? 'Optional' : 'Required'"></span>
                                    </span>
                                </label>

                                <p x-show="reanalysisMode" x-cloak class="text-[10px] text-slate-500 mb-2">
                                    Current stored file:
                                    <span class="font-medium text-slate-300"
                                        x-text="storedSourceName('contract') || 'No contract source found on this run'"
                                        style="word-break:break-all;"></span>
                                </p>

                                <label for="contract-dropzone" class="bob-dropzone group transition-all"
                                    :class="{ '!border-teal-300/60 !bg-teal-400/5': contractFileName }">
                                    <template x-if="!contractFileName">
                                        <div class="flex flex-col items-center justify-center py-5 text-slate-400">
                                            <svg class="w-9 h-9 mb-3 text-teal-300/60 group-hover:text-teal-300 transition-colors duration-300"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                            </svg>
                                            <p class="text-xs"><span class="font-bold text-teal-300">Browse</span> or
                                                drag &amp; drop contract patch file</p>
                                            <p class="text-[10px] mt-1 text-slate-500">CSV / XLSX — up to 30 MB</p>
                                        </div>
                                    </template>
                                    <template x-if="contractFileName">
                                        <div class="flex items-center gap-3 py-4 w-full">
                                            <div class="w-9 h-9 shrink-0 rounded-lg flex items-center justify-center"
                                                style="background:rgba(45,212,191,0.14);">
                                                <svg class="w-4 h-4 text-teal-300" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-xs font-bold text-white truncate"
                                                    x-text="contractFileName"></div>
                                                <div class="text-[10px] text-slate-400" x-text="contractFileSize"></div>
                                            </div>
                                            <button type="button" @click.prevent="clearFile('contract')"
                                                class="shrink-0 text-slate-500 hover:text-rose-400 transition-colors">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </template>
                                    <input id="contract-dropzone" type="file" name="contract_file" class="hidden"
                                        accept=".csv,.xls,.xlsx" @change="handleFile($event,'contract')" />
                                </label>

                                <div class="mt-3 rounded-lg border px-3 py-2"
                                    style="background:rgba(15,23,42,0.36);border-color:rgba(45,212,191,0.2);">
                                    <div class="text-[10px] font-bold uppercase tracking-widest text-teal-300">Patch
                                        Scope</div>
                                    <p class="text-[11px] text-slate-400 mt-1">Only records currently flagged as House
                                        Open
                                        or House Close will be updated. The patch workbook is always attached to the
                                        latest
                                        processed Final BOB run.</p>
                                </div>

                                {{-- Engine Readiness for Contract --}}
                                <div class="mt-4 flex items-center gap-3 p-3 rounded-lg"
                                    style="background:rgba(20,184,166,0.03); border:1px solid rgba(45,212,191,0.1);">
                                    <div class="flex items-center gap-2 flex-1">
                                        <span
                                            class="text-[10px] font-bold tracking-widest text-slate-500 uppercase">Engine
                                            Readiness</span>
                                        <div class="flex items-center gap-2.5">
                                            <span class="flex items-center gap-1 text-[10px]"
                                                :class="hasLatestParentBatch ? 'text-teal-400' : 'text-slate-600'">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" stroke-width="2.5">
                                                    <path x-show="hasLatestParentBatch && !isStandardSyncRunning"
                                                        stroke-linecap="round" stroke-linejoin="round"
                                                        d="M4.5 12.75l6 6 9-13.5" />
                                                    <path x-show="!(hasLatestParentBatch && !isStandardSyncRunning)"
                                                        stroke-linecap="round" stroke-linejoin="round"
                                                        d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                                </svg>
                                                Latest Final BOB
                                            </span>
                                            <span class="flex items-center gap-1 text-[10px]"
                                                :class="sourceReady('contract') ? 'text-teal-400' : 'text-slate-600'">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" stroke-width="2.5">
                                                    <path x-show="sourceReady('contract')" stroke-linecap="round"
                                                        stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                    <path x-show="!sourceReady('contract')" stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                                </svg>
                                                Contract File
                                            </span>
                                        </div>
                                    </div>
                                    <div class="shrink-0 text-[10px] font-medium"
                                        :class="isContractReady ? 'text-teal-400' : 'text-amber-400'" x-text="isContractReady
                                            ? (reanalysisMode ? '✓ Ready to re-analyze' : '✓ Ready to patch')
                                            : (reanalysisMode
                                                ? 'Upload replacement contract file or keep existing source'
                                                : 'Need latest Final BOB + contract file')">
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- Validation Errors --}}
                        @if($errors->any())
                            <div class="rounded-xl border p-4 relative overflow-hidden"
                                style="background:rgba(225,29,72,0.05);border-color:rgba(225,29,72,0.15);">
                                <div class="absolute top-0 left-0 w-1 h-full bg-rose-500 rounded-l-xl"></div>
                                <div class="flex gap-3 pl-2">
                                    <svg class="w-5 h-5 text-rose-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <div>
                                        <h4 class="text-xs font-bold text-rose-500 tracking-wide uppercase mb-1.5">Import
                                            Configuration Error</h4>
                                        <div class="space-y-1">
                                            @foreach($errors->all() as $error)
                                                <p class="text-[11px] text-rose-400 font-medium flex items-start gap-1.5">
                                                    <span class="w-1 h-1 rounded-full bg-rose-500/60 mt-1.5 shrink-0"></span>
                                                    {{ $error }}
                                                </p>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Submit --}}
                        <div class="flex items-center justify-between pt-4 border-t border-white/5">
                            <p class="text-[11px] text-slate-600" x-text="uploadMode === 'contract'
                            ? (reanalysisMode
                                ? 'This contract patch run will be reprocessed in place. Existing contract file is reused unless you upload a replacement.'
                                : 'Contract patch runs resolve flagged House Open/House Close records and generate a separate patch workbook.')
                            : (reanalysisMode
                                ? 'This run will be reprocessed in place. Existing files are reused unless you upload replacements.'
                                : 'The engine will try IMS first, then Health Sherpa for any unmatched rows.')"></p>
                            <button type="submit" class="bob-btn-primary"
                                :class="{ 'opacity-40 cursor-not-allowed pointer-events-none': !activeReady || isSubmitting }"
                                :disabled="!activeReady || isSubmitting">
                                <svg class="w-4 h-4" :class="isSubmitting ? 'animate-spin' : ''" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                                </svg>
                                <span x-text="isSubmitting
                                ? (uploadMode === 'contract'
                                    ? (reanalysisMode ? 'Starting Re-analysis...' : 'Starting Contract Patch...')
                                    : (reanalysisMode ? 'Starting Re-analysis...' : 'Starting ETL Flow...'))
                                : (uploadMode === 'contract'
                                    ? (reanalysisMode ? 'Run In-place Re-analysis' : 'Run Contract Patch')
                                    : (reanalysisMode ? 'Run In-place Re-analysis' : 'Run Synchronization'))"></span>
                            </button>
                        </div>

                        <div x-show="submitError" x-cloak
                            class="mt-3 rounded-lg border px-3 py-2 text-xs font-medium text-rose-300"
                            style="background:rgba(225,29,72,0.08);border-color:rgba(225,29,72,0.2);"
                            x-text="submitError">
                        </div>
                        <div x-show="submitSuccess" x-cloak
                            class="mt-3 rounded-lg border px-3 py-2 text-xs font-medium text-emerald-300"
                            style="background:rgba(16,185,129,0.08);border-color:rgba(16,185,129,0.2);"
                            x-text="submitSuccess"></div>

                        <div x-show="showLiveEtl && liveEtlBatch" x-cloak x-ref="liveEtlPanel"
                            class="mt-4 rounded-xl border bob-etl-live-card p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                                <div>
                                    <div class="text-[10px] font-black uppercase tracking-[0.14em] text-indigo-300"
                                        x-text="isContractLiveBatch() ? 'Live Contract Patch' : 'Live ETL Flow'"></div>
                                    <div class="text-xs font-semibold text-slate-200 mt-1">
                                        Run <span class="font-mono" x-text="liveFlowRunText()"></span>
                                        <span class="mx-1 text-slate-500">·</span>
                                        <span x-text="liveEtlBatch.status_label || 'Pending'"></span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2 mt-2 text-[10px] font-semibold">
                                        <span x-show="!isContractLiveBatch()"
                                            class="bob-badge bob-badge-resolved text-[10px] tracking-wider">COMBINED</span>
                                        <span x-show="isContractLiveBatch()"
                                            class="text-[10px] font-bold tracking-widest px-2 py-0.5 rounded"
                                            style="background:rgba(20,184,166,0.14);color:#5eead4;">CONTRACT
                                            PATCH</span>
                                        <span class="text-slate-500" x-text="liveFlowDateText()"></span>
                                        <span class="text-slate-600">·</span>
                                        <span class="text-slate-500">by <span
                                                x-text="liveFlowUploaderText()"></span></span>
                                        <span x-show="hasLiveSource('ims')"
                                            class="text-[9px] font-bold tracking-widest px-1.5 py-0.5 rounded"
                                            style="background:rgba(96,165,250,0.1);color:#60a5fa;">IMS</span>
                                        <span x-show="hasLiveSource('hs')"
                                            class="text-[9px] font-bold tracking-widest px-1.5 py-0.5 rounded"
                                            style="background:rgba(52,211,153,0.1);color:#34d399;">HS</span>
                                        <span x-show="hasLiveSource('contract')"
                                            class="text-[9px] font-bold tracking-widest px-1.5 py-0.5 rounded"
                                            style="background:rgba(20,184,166,0.12);color:#5eead4;">CONTRACT FILE</span>
                                    </div>
                                </div>
                                <button type="button" @click="scrollToEtlFlow"
                                    class="text-[10px] font-bold tracking-wider px-2.5 py-1.5 rounded-lg text-indigo-300 border"
                                    style="background:rgba(99,102,241,0.12);border-color:rgba(129,140,248,0.2);">
                                    Open Full ETL Panel
                                </button>
                            </div>

                            <template x-if="!isContractLiveBatch()">
                                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-2.5">
                                    <div class="bob-etl-stage" :class="stageClass('upload')">
                                        <div class="bob-etl-stage-dot" :class="stageDotClass('upload')"></div>
                                        <div>
                                            <div class="bob-etl-stage-title">Upload Accepted</div>
                                            <div class="bob-etl-stage-state" x-text="stageStateText('upload')"></div>
                                        </div>
                                    </div>
                                    <div class="bob-etl-stage" :class="stageClass('ims')">
                                        <div class="bob-etl-stage-dot" :class="stageDotClass('ims')"></div>
                                        <div>
                                            <div class="bob-etl-stage-title">IMS Matching</div>
                                            <div class="bob-etl-stage-state" x-text="stageStateText('ims')"></div>
                                        </div>
                                    </div>
                                    <div class="bob-etl-stage" :class="stageClass('hs')">
                                        <div class="bob-etl-stage-dot" :class="stageDotClass('hs')"></div>
                                        <div>
                                            <div class="bob-etl-stage-title">Health Sherpa Matching</div>
                                            <div class="bob-etl-stage-state" x-text="stageStateText('hs')"></div>
                                        </div>
                                    </div>
                                    <div class="bob-etl-stage" :class="stageClass('finalize')">
                                        <div class="bob-etl-stage-dot" :class="stageDotClass('finalize')"></div>
                                        <div>
                                            <div class="bob-etl-stage-title">Final Workbook</div>
                                            <div class="bob-etl-stage-state" x-text="stageStateText('finalize')"></div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <template x-if="isContractLiveBatch()">
                                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-2.5">
                                    <div class="bob-etl-stage" :class="stageClass('upload')">
                                        <div class="bob-etl-stage-dot" :class="stageDotClass('upload')"></div>
                                        <div>
                                            <div class="bob-etl-stage-title">Contract Upload</div>
                                            <div class="bob-etl-stage-state" x-text="stageStateText('upload')"></div>
                                        </div>
                                    </div>
                                    <div class="bob-etl-stage" :class="stageClass('contract_scan')">
                                        <div class="bob-etl-stage-dot" :class="stageDotClass('contract_scan')"></div>
                                        <div>
                                            <div class="bob-etl-stage-title">Flagged Scan</div>
                                            <div class="bob-etl-stage-state" x-text="stageStateText('contract_scan')">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bob-etl-stage" :class="stageClass('contract_apply')">
                                        <div class="bob-etl-stage-dot" :class="stageDotClass('contract_apply')"></div>
                                        <div>
                                            <div class="bob-etl-stage-title">Patch Apply</div>
                                            <div class="bob-etl-stage-state" x-text="stageStateText('contract_apply')">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bob-etl-stage" :class="stageClass('contract_finalize')">
                                        <div class="bob-etl-stage-dot" :class="stageDotClass('contract_finalize')">
                                        </div>
                                        <div>
                                            <div class="bob-etl-stage-title">Patch Workbook</div>
                                            <div class="bob-etl-stage-state"
                                                x-text="stageStateText('contract_finalize')">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <template x-if="!isContractLiveBatch()">
                                <div class="grid grid-cols-2 lg:grid-cols-5 gap-2.5 mt-3">
                                    <div class="bob-etl-metric-card">
                                        <div class="bob-etl-metric-label">Scanned</div>
                                        <div class="bob-etl-metric-value"
                                            x-text="`${Number(liveEtlBatch.processed_records || 0)}/${Number(liveEtlBatch.total_records || 0)}`">
                                        </div>
                                    </div>
                                    <div class="bob-etl-metric-card">
                                        <div class="bob-etl-metric-label">IMS Matched</div>
                                        <div class="bob-etl-metric-value"
                                            x-text="Number(liveEtlBatch.ims_matched_records || 0)"></div>
                                    </div>
                                    <div class="bob-etl-metric-card">
                                        <div class="bob-etl-metric-label">HS Matched</div>
                                        <div class="bob-etl-metric-value"
                                            x-text="Number(liveEtlBatch.hs_matched_records || 0)"></div>
                                    </div>
                                    <div class="bob-etl-metric-card">
                                        <div class="bob-etl-metric-label">Rate</div>
                                        <div class="bob-etl-metric-value" x-text="liveRateText()"></div>
                                    </div>
                                    <div class="bob-etl-metric-card">
                                        <div class="bob-etl-metric-label">ETA</div>
                                        <div class="bob-etl-metric-value" x-text="liveEtaText()"></div>
                                    </div>
                                </div>
                            </template>

                            <template x-if="isContractLiveBatch()">
                                <div class="grid grid-cols-2 lg:grid-cols-5 gap-2.5 mt-3">
                                    <div class="bob-etl-metric-card">
                                        <div class="bob-etl-metric-label">Scanned</div>
                                        <div class="bob-etl-metric-value"
                                            x-text="`${Number(liveEtlBatch.processed_records || 0)}/${Number(liveEtlBatch.total_records || 0)}`">
                                        </div>
                                    </div>
                                    <div class="bob-etl-metric-card">
                                        <div class="bob-etl-metric-label">Patched</div>
                                        <div class="bob-etl-metric-value text-indigo-400"
                                            x-text="Number(liveEtlBatch.contract_patched_records || 0)"></div>
                                    </div>
                                    <div class="bob-etl-metric-card">
                                        <div class="bob-etl-metric-label">Skipped</div>
                                        <div class="bob-etl-metric-value text-slate-400"
                                            x-text="Number(liveEtlBatch.skipped_records || 0)"></div>
                                    </div>
                                    <div class="bob-etl-metric-card">
                                        <div class="bob-etl-metric-label">Failed</div>
                                        <div class="bob-etl-metric-value text-rose-400"
                                            x-text="Number(liveEtlBatch.failed_records || 0)">
                                        </div>
                                    </div>
                                    <div class="bob-etl-metric-card">
                                        <div class="bob-etl-metric-label">Rate</div>
                                        <div class="bob-etl-metric-value" x-text="liveRateText()"></div>
                                    </div>
                                    <div class="bob-etl-metric-card">
                                        <div class="bob-etl-metric-label">ETA</div>
                                        <div class="bob-etl-metric-value" x-text="liveEtaText()"></div>
                                    </div>
                                </div>
                            </template>

                            <div class="mt-3">
                                <div class="w-full h-1.5 rounded-full overflow-hidden"
                                    style="background:rgba(99,102,241,0.14);">
                                    <div class="h-full rounded-full transition-all duration-700"
                                        :class="isLiveBatchProcessing() ? 'bob-processing-fill bob-processing-fill-ims' : ''"
                                        :style="etlProgressStyle()"></div>
                                </div>
                                <div class="flex items-center justify-between mt-1.5 text-[10px] font-semibold">
                                    <span class="text-slate-400" x-text="etlSummaryText()"></span>
                                    <span class="text-indigo-300"
                                        x-text="`${Math.round(etlProgressPercent())}%`"></span>
                                </div>
                            </div>

                            <div class="mt-3 rounded-lg border bob-etl-event-box">
                                <div class="flex items-center justify-between px-3 py-2 border-b bob-etl-event-head">
                                    <span class="text-[10px] font-black uppercase tracking-[0.1em] text-slate-300">Live
                                        Checks</span>
                                    <span class="text-[10px] font-semibold text-slate-500"
                                        x-text="`Updated ${liveLastUpdateText()}`"></span>
                                </div>
                                <div class="px-3 py-2 max-h-28 overflow-y-auto space-y-1.5">
                                    <template x-for="(entry, idx) in liveEvents" :key="entry.key + '-' + idx">
                                        <div class="flex items-start justify-between gap-2 text-[10px]">
                                            <span class="font-semibold" :class="eventToneClass(entry.tone)"
                                                x-text="entry.message"></span>
                                            <span class="text-slate-500 font-mono" x-text="entry.time"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
            </form>
        </div>


        {{-- ══════════════════════════════════════════════════════════
        SECTION 2: RECENT RECONCILIATION RUNS — Premium Pipeline
        ══════════════════════════════════════════════════════════ --}}
        @php
            $statusStyles = [
                'completed' => ['class' => 'bob-badge-matched', 'label' => 'Completed'],
                'processing' => ['class' => 'bob-badge-pending', 'label' => 'Processing'],
                'failed' => ['class' => 'bob-badge-flagged', 'label' => 'Failed'],
                'completed_with_errors' => ['class' => 'bob-badge-flagged', 'label' => 'Partial'],
                'pending' => ['class' => 'bob-badge-pending', 'label' => 'Pending'],
            ];
            $canViewReporting = auth()->user()?->can('reconciliation.results.view');
            $canRerun = auth()->user()?->can('reconciliation.reanalysis.run');
            $canDownload = auth()->user()?->can('reconciliation.export.download');
            $canDelete = auth()->user()?->can('reconciliation.delete');
            $initialBatches = $batches->map(function ($batch) use ($statusStyles, $canViewReporting, $canDownload) {
                $isDone = in_array($batch->status, ['completed', 'completed_with_errors']);
                $statusInfo = $statusStyles[$batch->status] ?? $statusStyles['pending'];
                $isContractPatch = $batch->isContractPatch();

                // Serialize nested contract patch children
                $childPatches = $batch->childPatches->map(function ($child) use ($statusStyles, $batch, $canViewReporting, $canDownload) {
                    $childStatus = $statusStyles[$child->status] ?? $statusStyles['pending'];
                    $childDone = in_array($child->status, ['completed', 'completed_with_errors']);
                    return [
                        'id' => $child->id,
                        'batch_type' => 'contract_patch',
                        'parent_batch_id' => $batch->id,
                        'status' => $child->status,
                        'status_label' => $childStatus['label'],
                        'status_class' => $childStatus['class'],
                        'is_done' => $childDone,
                        'has_output' => $child->hasOutput(),
                        'download_url' => $child->hasOutput()
                            ? ($canDownload ? route('reconciliation.contract-patch.download', $child) : null)
                            : null,
                        'contract_file_path' => $child->contract_file_path,
                        'contract_original_name' => $child->contract_original_name,
                        'contract_patched_records' => (int) $child->contract_patched_records,
                        'skipped_records' => (int) $child->skipped_records,
                        'failed_records' => (int) $child->failed_records,
                        'skipped_summary' => $child->skipped_summary ?? [],
                        'failure_summary' => $child->failure_summary ?? [],
                        'total_records' => (int) $child->total_records,
                        'processed_records' => (int) $child->processed_records,
                        'progress_pct' => $child->total_records
                            ? round(($child->processed_records / $child->total_records) * 100)
                            : 0,
                        'error_message' => $child->error_message,
                        'formatted_date' => $child->created_at->format('M d, Y · h:i A'),
                        'uploader_name' => $child->uploadedBy?->name ?? 'System',
                        'created_at_iso' => optional($child->created_at)->toIso8601String(),
                        'updated_at_iso' => optional($child->updated_at)->toIso8601String(),
                        'ledger_url' => $canViewReporting
                            ? route('reconciliation.reporting.contract-patches', [
                                'parent_batch_id' => $batch->id,
                                'batch_id' => $child->id,
                            ])
                            : null,
                    ];
                })->values()->toArray();

                return [
                    'id' => $batch->id,
                    'batch_type' => $batch->batch_type ?: 'standard',
                    'retry_of_batch_id' => $batch->retry_of_batch_id,
                    'retry_group_id' => $batch->retry_group_id,
                    'attempt_no' => (int) ($batch->attempt_no ?? 1),
                    'retry_reason' => $batch->retry_reason,
                    'duplicate_strategy' => $batch->duplicate_strategy,
                    'status' => $batch->status,
                    'status_label' => $statusInfo['label'],
                    'status_class' => $statusInfo['class'],
                    'is_done' => $isDone,
                    'has_output' => $batch->hasOutput(),
                    'download_url' => $batch->hasOutput()
                        ? ($canDownload
                            ? ($isContractPatch
                                ? route('reconciliation.contract-patch.download', $batch)
                                : route('reconciliation.batches.download', $batch))
                            : null)
                        : null,
                    'results_url' => !$isContractPatch && in_array($batch->status, ['completed', 'completed_with_errors'])
                        ? route('reconciliation.batches.show', $batch)
                        : null,
                    'processed_records' => (int) $batch->processed_records,
                    'total_records' => (int) $batch->total_records,
                    'ims_matched_records' => (int) $batch->ims_matched_records,
                    'hs_matched_records' => (int) $batch->hs_matched_records,
                    'contract_patched_records' => (int) $batch->contract_patched_records,
                    'skipped_records' => (int) $batch->skipped_records,
                    'failed_records' => (int) $batch->failed_records,
                    'skipped_summary' => $batch->skipped_summary ?? [],
                    'failure_summary' => $batch->failure_summary ?? [],
                    'progress_pct' => $batch->total_records ? round(($batch->processed_records / $batch->total_records) * 100) : 0,
                    'ims_pct' => $batch->processed_records ? round(($batch->ims_matched_records / $batch->processed_records) * 100) : 0,
                    'hs_pct' => $batch->processed_records ? round(($batch->hs_matched_records / $batch->processed_records) * 100) : 0,
                    'error_message' => $batch->error_message,
                    'formatted_date' => $batch->created_at->format('M d, Y · h:i A'),
                    'uploader_name' => $batch->uploadedBy?->name ?? 'System',
                    'carrier_original_name' => $batch->carrier_original_name,
                    'ims_file_path' => $batch->ims_file_path,
                    'ims_original_name' => $batch->ims_original_name,
                    'payee_file_path' => $batch->payee_file_path,
                    'payee_original_name' => $batch->payee_original_name,
                    'health_sherpa_file_path' => $batch->health_sherpa_file_path,
                    'health_sherpa_original_name' => $batch->health_sherpa_original_name,
                    'contract_file_path' => $batch->contract_file_path,
                    'contract_original_name' => $batch->contract_original_name,
                    'child_patches' => $childPatches,
                ];
            })->values();
        @endphp
        <div id="etlFlowSection" class="bob-glass-panel"
            x-data="batchPoller(@js($initialBatches), @js(['can_rerun' => $canRerun, 'can_download' => $canDownload, 'can_delete' => $canDelete]))">
            <div class="px-6 py-5 flex items-center justify-between border-b border-white/5">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z" />
                    </svg>
                    <h3 class="text-sm font-bold text-white tracking-wide">Recent Reconciliation Runs</h3>
                </div>
                <span class="text-[11px] font-bold px-2.5 py-1 rounded-md text-indigo-400"
                    style="background:rgba(99,102,241,0.1);">{{ $batches->total() }} total</span>
            </div>

            <div class="divide-y divide-white/5">
                <template x-for="(batch, index) in batches" :key="batch.id">

                    <div class="px-6 py-5 hover:bg-white/[0.012] transition-colors group/run">

                        {{-- ── Row header ───────────────────────────────── --}}
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between mb-4">
                            <div class="flex items-center gap-3 flex-wrap">
                                <span x-show="batch.batch_type !== 'contract_patch'"
                                    class="bob-badge bob-badge-resolved text-[10px] tracking-wider">COMBINED</span>
                                <span x-show="batch.batch_type === 'contract_patch'"
                                    class="text-[10px] font-bold tracking-wider px-2.5 py-1 rounded-md"
                                    style="background:rgba(20,184,166,0.14);color:#5eead4;border:1px solid rgba(45,212,191,0.24);">CONTRACT
                                    PATCH</span>
                                <span class="text-[11px] text-slate-500 font-medium">
                                    <span x-text="batch.formatted_date"></span>
                                </span>
                                <span class="text-[11px] text-slate-600">·</span>
                                <span class="text-[11px] text-slate-500">by <span
                                        x-text="batch.uploader_name"></span></span>
                                {{-- Source badges --}}
                                <template x-if="batch.batch_type !== 'contract_patch' && batch.ims_file_path">
                                    <span class="text-[9px] font-bold tracking-widest px-1.5 py-0.5 rounded"
                                        style="background:rgba(96,165,250,0.1);color:#60a5fa;">IMS</span>
                                </template>
                                <template x-if="batch.batch_type !== 'contract_patch' && batch.health_sherpa_file_path">
                                    <span class="text-[9px] font-bold tracking-widest px-1.5 py-0.5 rounded"
                                        style="background:rgba(52,211,153,0.1);color:#34d399;">HS</span>
                                </template>
                                <template x-if="batch.batch_type === 'contract_patch'">
                                    <span class="text-[9px] font-bold tracking-widest px-1.5 py-0.5 rounded"
                                        style="background:rgba(20,184,166,0.12);color:#5eead4;">CONTRACT FILE</span>
                                </template>
                                <template x-if="Number(batch.attempt_no || 1) > 1">
                                    <span class="text-[9px] font-bold tracking-widest px-1.5 py-0.5 rounded"
                                        style="background:rgba(245,158,11,0.12);color:#fbbf24;border:1px solid rgba(245,158,11,0.2);"
                                        x-text="`Attempt ${batch.attempt_no}`"></span>
                                </template>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                                {{-- Download button --}}
                                <a x-show="batch.has_output && canDownload" :href="batch.download_url"
                                    class="inline-flex items-center gap-1.5 text-[10px] font-bold tracking-wider px-3 py-1.5 rounded-lg transition-all hover:scale-105"
                                    style="background:rgba(16,185,129,0.1);color:#34d399;border:1px solid rgba(16,185,129,0.2);"
                                    :title="batch.batch_type === 'contract_patch' ? 'Download Contract Patch Excel' : 'Download Final BOB Excel'">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                    </svg>
                                    <span
                                        x-text="batch.batch_type === 'contract_patch' ? 'Contract Patch Excel' : 'Final BOB Excel'"></span>
                                </a>
                                <button type="button" x-show="canRerunBatch(batch)" @click="requestInlineRerun(batch)"
                                    class="inline-flex items-center gap-1.5 text-[10px] font-bold tracking-wider px-3 py-1.5 rounded-lg transition-all hover:scale-105"
                                    style="background:rgba(59,130,246,0.12);color:#93c5fd;border:1px solid rgba(59,130,246,0.24);"
                                    title="Re-upload affected files and rerun analysis">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" />
                                    </svg>
                                    Re-analyze
                                </button>
                                <span class="bob-badge" :class="batch.status_class" x-text="batch.status_label"></span>
                                {{-- Delete button --}}
                                <form :id="'delete-batch-' + batch.id" method="POST"
                                    :action="'/reconciliation/batches/' + batch.id" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" x-show="canDelete" @click="deleteBatch(batch.id)"
                                        class="opacity-0 group-hover/run:opacity-100 transition-opacity text-slate-600 hover:text-rose-400 p-1.5 rounded-lg hover:bg-rose-500/10"
                                        title="Delete this run">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>


                        <template x-if="batch.batch_type !== 'contract_patch'">
                            <div class="flex flex-col xl:flex-row items-stretch gap-3 xl:gap-0">

                                {{-- Stage 1: Source Files --}}
                                <div class="w-full xl:w-48 xl:shrink-0 rounded-xl xl:rounded-l-xl xl:rounded-r-none border border-white/5 p-4"
                                    style="background:rgba(255,255,255,0.015);">
                                    <div class="text-[9px] uppercase tracking-widest font-bold text-slate-500 mb-3">
                                        Source Files</div>
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#fbbf24] shrink-0"></span>
                                            <span class="text-[10px] text-slate-300 truncate font-medium"
                                                :title="batch.carrier_original_name"
                                                x-text="batch.carrier_original_name"></span>
                                            <svg class="w-3 h-3 text-emerald-400 ml-auto shrink-0" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M4.5 12.75l6 6 9-13.5" />
                                            </svg>
                                        </div>
                                        <template x-if="batch.ims_file_path">
                                            <div class="flex items-center gap-2">
                                                <span class="w-1.5 h-1.5 rounded-full bg-[#60a5fa] shrink-0"></span>
                                                <span class="text-[10px] text-slate-300 truncate font-medium"
                                                    :title="batch.ims_original_name"
                                                    x-text="batch.ims_original_name"></span>
                                                <svg class="w-3 h-3 text-emerald-400 ml-auto shrink-0" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M4.5 12.75l6 6 9-13.5" />
                                                </svg>
                                            </div>
                                        </template>
                                        <template x-if="batch.payee_file_path">
                                            <div class="flex items-center gap-2">
                                                <span class="w-1.5 h-1.5 rounded-full bg-[#c084fc] shrink-0"></span>
                                                <span class="text-[10px] text-slate-300 truncate font-medium"
                                                    :title="batch.payee_original_name"
                                                    x-text="batch.payee_original_name"></span>
                                                <svg class="w-3 h-3 text-emerald-400 ml-auto shrink-0" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M4.5 12.75l6 6 9-13.5" />
                                                </svg>
                                            </div>
                                        </template>
                                        <template x-if="batch.health_sherpa_file_path">
                                            <div class="flex items-center gap-2">
                                                <span class="w-1.5 h-1.5 rounded-full bg-[#34d399] shrink-0"></span>
                                                <span class="text-[10px] text-slate-300 truncate font-medium"
                                                    :title="batch.health_sherpa_original_name"
                                                    x-text="batch.health_sherpa_original_name"></span>
                                                <svg class="w-3 h-3 text-emerald-400 ml-auto shrink-0" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M4.5 12.75l6 6 9-13.5" />
                                                </svg>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                {{-- Connector --}}
                                <div class="hidden xl:flex items-center px-1 z-10">
                                    <div class="w-5 h-5 rounded-full flex items-center justify-center transition-colors duration-500"
                                        :style="batch.is_done ? 'background:rgba(16,185,129,0.15)' : 'background:rgba(245,158,11,0.15)'">
                                        <svg class="w-3 h-3 transition-colors duration-500"
                                            :class="batch.is_done ? 'text-emerald-400' : 'text-amber-400'" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                        </svg>
                                    </div>
                                </div>

                                {{-- Stage 2: Split matching engine --}}
                                <div class="flex-1 flex flex-col gap-2 border border-white/5 p-4"
                                    style="background:rgba(255,255,255,0.015);">
                                    <div class="text-[9px] uppercase tracking-widest font-bold text-slate-500 mb-1">
                                        Matching Engine</div>

                                    {{-- IMS Match branch --}}
                                    <template x-if="batch.ims_file_path || batch.ims_matched_records > 0">
                                        <div class="rounded-lg p-2.5"
                                            style="background:rgba(96,165,250,0.06);border:1px solid rgba(96,165,250,0.12);">
                                            <div class="flex items-center justify-between mb-1.5">
                                                <span
                                                    class="text-[9px] font-bold tracking-widest text-[#60a5fa] uppercase">IMS</span>
                                                <span class="text-[10px] font-mono font-bold text-white"
                                                    x-text="branchLabel(batch, 'ims')"></span>
                                            </div>
                                            <div class="w-full h-1 rounded-full overflow-hidden"
                                                style="background:rgba(96,165,250,0.1);">
                                                <div class="h-full rounded-full transition-all duration-700"
                                                    :class="isBatchProcessing(batch) ? 'bob-processing-fill bob-processing-fill-ims' : ''"
                                                    :style="branchBarStyle(batch, 'ims')"></div>
                                            </div>
                                            <div class="flex items-center justify-between mt-1.5">
                                                <div class="text-[9px] text-slate-500">Email → Phone → Name → DOB</div>
                                                <span x-show="isBatchProcessing(batch)"
                                                    class="bob-processing-chip bob-processing-chip-ims">
                                                    <span class="bob-processing-chip-dot"></span>
                                                    Processing
                                                </span>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="!batch.ims_file_path">
                                        <div class="rounded-lg p-2.5"
                                            style="background:rgba(255,255,255,0.02);border:1px dashed rgba(255,255,255,0.06);">
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="text-[9px] font-bold tracking-widest text-slate-600 uppercase">IMS</span>
                                                <span class="text-[10px] text-slate-600 italic">Not included in this
                                                    run</span>
                                            </div>
                                        </div>
                                    </template>

                                    {{-- Health Sherpa Match branch --}}
                                    <template x-if="batch.health_sherpa_file_path || batch.hs_matched_records > 0">
                                        <div class="rounded-lg p-2.5"
                                            style="background:rgba(52,211,153,0.06);border:1px solid rgba(52,211,153,0.12);">
                                            <div class="flex items-center justify-between mb-1.5">
                                                <span
                                                    class="text-[9px] font-bold tracking-widest text-[#34d399] uppercase">Health
                                                    Sherpa</span>
                                                <span class="text-[10px] font-mono font-bold text-white"
                                                    x-text="branchLabel(batch, 'hs')"></span>
                                            </div>
                                            <div class="w-full h-1 rounded-full overflow-hidden"
                                                style="background:rgba(52,211,153,0.1);">
                                                <div class="h-full rounded-full transition-all duration-700"
                                                    :class="isBatchProcessing(batch) ? 'bob-processing-fill bob-processing-fill-hs' : ''"
                                                    :style="branchBarStyle(batch, 'hs')"></div>
                                            </div>
                                            <div class="flex items-center justify-between mt-1.5">
                                                <div class="text-[9px] text-slate-500">Email → Phone + Date (±30d)</div>
                                                <span x-show="isBatchProcessing(batch)"
                                                    class="bob-processing-chip bob-processing-chip-hs">
                                                    <span class="bob-processing-chip-dot"></span>
                                                    Processing
                                                </span>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="!batch.health_sherpa_file_path">
                                        <div class="rounded-lg p-2.5"
                                            style="background:rgba(255,255,255,0.02);border:1px dashed rgba(255,255,255,0.06);">
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="text-[9px] font-bold tracking-widest text-slate-600 uppercase">Health
                                                    Sherpa</span>
                                                <span class="text-[10px] text-slate-600 italic">Not included in this
                                                    run</span>
                                            </div>
                                        </div>
                                    </template>

                                    {{-- Failed rows alert --}}
                                    <template x-if="batch.failed_records > 0">
                                        <div class="flex items-center gap-1.5 mt-1">
                                            <svg class="w-3 h-3 text-rose-400 shrink-0" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                            </svg>
                                            <span class="text-[10px] font-bold text-rose-400"><span
                                                    x-text="batch.failed_records"></span> rows could not be
                                                matched</span>
                                        </div>
                                    </template>
                                </div>

                                {{-- Connector --}}
                                <div class="hidden xl:flex items-center px-1 z-10">
                                    <div class="w-5 h-5 rounded-full flex items-center justify-center transition-colors duration-500"
                                        :style="batch.is_done ? 'background:rgba(16,185,129,0.15)' : 'background:rgba(245,158,11,0.15)'">
                                        <svg class="w-3 h-3 transition-colors duration-500"
                                            :class="batch.is_done ? 'text-emerald-400' : 'text-amber-400'" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                        </svg>
                                    </div>
                                </div>

                                {{-- Stage 3: Final Output --}}
                                <div class="w-full xl:w-56 xl:shrink-0 rounded-xl xl:rounded-r-xl xl:rounded-l-none border border-white/5 p-4 flex flex-col justify-between"
                                    style="background:rgba(255,255,255,0.015);">
                                    <div>
                                        <div class="text-[9px] uppercase tracking-widest font-bold text-slate-500 mb-3">
                                            Final BOB Output</div>

                                        <div class="flex items-center gap-3">
                                            {{-- Success State --}}
                                            <template x-if="batch.status === 'completed'">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                                        style="background:rgba(16,185,129,0.15);">
                                                        <svg class="w-4 h-4 text-emerald-400" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor"
                                                            stroke-width="2.5">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M4.5 12.75l6 6 9-13.5" />
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <div class="text-xs font-bold text-emerald-400">Fully Synced
                                                        </div>
                                                        <div class="text-[10px] text-slate-500 mt-0.5"><span
                                                                x-text="batch.processed_records"></span> records updated
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>

                                            {{-- Partial Sync State --}}
                                            <template x-if="batch.status === 'completed_with_errors'">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                                        style="background:rgba(245,158,11,0.15);">
                                                        <svg class="w-4 h-4 text-amber-400" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <div class="text-xs font-bold text-amber-400">Partial Sync</div>
                                                        <div class="text-[10px] text-slate-500 mt-0.5"><span
                                                                x-text="batch.failed_records"></span> rows need review
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>

                                            {{-- Failed State --}}
                                            <template x-if="batch.status === 'failed'">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                                        style="background:rgba(225,29,72,0.15);">
                                                        <svg class="w-4 h-4 text-rose-400" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <div class="text-xs font-bold text-rose-400">Process Failed
                                                        </div>
                                                        <div class="text-[10px] text-slate-500 mt-0.5">Check detailed
                                                            report below</div>
                                                    </div>
                                                </div>
                                            </template>

                                            {{-- Pending/Processing State --}}
                                            <template x-if="['pending', 'processing'].includes(batch.status)">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                                        style="background:rgba(245,158,11,0.15);">
                                                        <svg class="w-4 h-4 text-amber-400 animate-spin" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" />
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <div class="text-xs font-bold text-amber-400"
                                                            x-text="(batch.status.charAt(0).toUpperCase() + batch.status.slice(1)) + '…'">
                                                        </div>
                                                        <div class="text-[10px] text-slate-500 mt-0.5">Awaiting
                                                            completion</div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    <div class="mt-3 space-y-1.5">
                                        <template x-if="canDownload && batch.has_output && hasPatchData(batch)">
                                            <a :href="batch.download_url"
                                                class="flex items-center justify-center gap-1.5 w-full py-1.5 rounded-lg text-[10px] font-bold tracking-wider transition-all hover:scale-105 active:scale-95"
                                                style="background:rgba(16,185,129,0.12);color:#34d399;border:1px solid rgba(16,185,129,0.2);">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                                </svg>
                                                Download Excel
                                            </a>
                                        </template>
                                        <template x-if="canDownload && batch.has_output && !hasPatchData(batch)">
                                            <div class="flex items-center justify-center gap-1.5 w-full py-1.5 rounded-lg text-[9px] font-bold tracking-wider opacity-60 cursor-not-allowed"
                                                style="background:rgba(255,255,255,0.03);color:#475569;border:1px dashed rgba(255,255,255,0.1);"
                                                title="No changes were made to the file.">
                                                <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                                </svg>
                                                Empty Result
                                            </div>
                                        </template>
                                        {{-- View Results drill-down --}}
                                        <a x-show="batch.results_url" :href="batch.results_url"
                                            class="flex items-center justify-center gap-1.5 w-full py-1.5 rounded-lg text-[10px] font-bold tracking-wider transition-all hover:scale-105 active:scale-95"
                                            style="background:rgba(99,102,241,0.12);color:#818cf8;border:1px solid rgba(99,102,241,0.2);">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                            </svg>
                                            View Results →
                                        </a>
                                        <div x-show="!batch.has_output"
                                            class="flex items-center justify-center gap-1.5 w-full py-1.5 rounded-lg text-[10px] font-medium"
                                            style="background:rgba(255,255,255,0.02);color:#475569;border:1px dashed rgba(255,255,255,0.05);">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                            </svg>
                                            <span
                                                x-text="['pending','processing'].includes(batch.status) ? 'Generating…' : 'Unavailable'"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        {{-- ── Associated Contract Patches (Nested Section) ──── --}}
                        <template
                            x-if="batch.batch_type !== 'contract_patch' && batch.child_patches && batch.child_patches.length > 0">
                            <div class="mt-4 mx-1 rounded-2xl overflow-hidden border border-white/5 shadow-2xl relative"
                                style="background: linear-gradient(135deg, rgba(15,23,42,0.6) 0%, rgba(30,41,59,0.3) 100%); backdrop-filter: blur(12px);">

                                {{-- Section Glow Effects --}}
                                <div
                                    class="absolute -top-24 -right-24 w-48 h-48 bg-teal-500/5 blur-[80px] pointer-events-none">
                                </div>
                                <div
                                    class="absolute -bottom-24 -left-24 w-48 h-48 bg-indigo-500/5 blur-[80px] pointer-events-none">
                                </div>

                                <div class="px-6 py-4 flex items-center justify-between border-b border-white/5 relative z-10"
                                    style="background: rgba(255,255,255,0.02);">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 shadow-inner"
                                            style="background:rgba(45,212,191,0.08); border: 1px solid rgba(45,212,191,0.12);">
                                            <svg class="w-4 h-4 text-teal-400" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <span
                                                class="text-[10px] uppercase tracking-[0.2em] font-black text-slate-400">Associated
                                                Contract Patches</span>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span class="text-[9px] font-bold text-teal-500/80"
                                                    x-text="batch.child_patches.length + ' active patches'"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 text-[10px] font-bold">
                                        <span
                                            class="px-2.5 py-1 rounded-md bg-teal-500/10 text-teal-400 border border-teal-500/20"
                                            x-text="batch.child_patches.reduce((acc, p) => acc + (p.contract_patched_records || 0), 0) + ' Patched Total'"></span>
                                    </div>
                                </div>

                                <div class="divide-y divide-white/5 relative z-10">
                                    <template x-for="patch in batch.child_patches" :key="patch.id">
                                        <div class="group/patch transition-all duration-300"
                                            :class="patch._show_error ? 'bg-rose-500/[0.03]' : 'hover:bg-white/[0.02]'">

                                            <div class="flex items-center gap-5 px-6 py-4 cursor-pointer"
                                                @click="patch.status === 'failed' || patch.failed_records > 0 ? patch._show_error = !patch._show_error : null">

                                                <div class="relative shrink-0">
                                                    <div class="w-2.5 h-2.5 rounded-full shadow-[0_0_8px_rgba(20,184,166,0.2)] transition-all duration-500"
                                                        :class="{
                                                            'bg-emerald-500 shadow-emerald-500/40': patch.status === 'completed',
                                                            'bg-amber-500 shadow-amber-500/40 animate-pulse': ['pending', 'processing'].includes(patch.status),
                                                            'bg-rose-500 shadow-rose-500/40': patch.status === 'failed',
                                                            'bg-orange-500 shadow-orange-500/40': patch.status === 'completed_with_errors'
                                                        }">
                                                    </div>
                                                </div>

                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2.5">
                                                        <span
                                                            class="text-[13px] font-bold text-slate-100 truncate group-hover/patch:text-teal-300 transition-colors"
                                                            x-text="patch.contract_original_name || 'Contract Patch'"></span>
                                                        <template
                                                            x-if="patch.status === 'failed' || patch.failed_records > 0">
                                                            <div class="px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-wider bg-rose-500/10 text-rose-400 border border-rose-500/20"
                                                                x-text="patch._show_error ? 'Hide Report' : 'View Report'">
                                                            </div>
                                                        </template>
                                                    </div>
                                                    <div
                                                        class="flex items-center gap-3 mt-1.5 text-[10px] text-slate-500 font-medium italic">
                                                        <span x-text="patch.formatted_date"></span>
                                                        <span class="w-0.5 h-0.5 rounded-full bg-slate-700"></span>
                                                        <span x-text="'Agent: ' + patch.uploader_name"></span>
                                                    </div>
                                                </div>

                                                <div class="hidden md:flex items-center gap-8 shrink-0">
                                                    <div class="w-32">
                                                        <div class="flex items-center justify-between mb-1.5 px-0.5">
                                                            <span
                                                                class="text-[9px] font-black uppercase tracking-tighter text-slate-500"
                                                                x-text="isBatchProcessing(patch) ? 'Processing' : (patch.status === 'failed' ? 'Failed' : 'Completed')"></span>
                                                            <span class="text-[10px] font-mono font-bold text-teal-400"
                                                                x-text="Math.round(patch.progress_pct || 0) + '%'"></span>
                                                        </div>
                                                        <div class="h-1 w-full bg-white/5 rounded-full overflow-hidden">
                                                            <div class="h-full rounded-full transition-all duration-1000"
                                                                :class="isBatchProcessing(patch) ? 'bg-gradient-to-r from-teal-500 to-indigo-500' : (patch.status === 'failed' ? 'bg-rose-500' : 'bg-teal-500')"
                                                                :style="'width: ' + Math.max(4, patch.progress_pct || 0) + '%'"
                                                                style="background-size: 200% 100%;"></div>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-5">
                                                        <div class="text-right">
                                                            <div class="text-[11px] font-black text-slate-100"
                                                                x-text="Number(patch.contract_patched_records || 0).toLocaleString()">
                                                            </div>
                                                            <div
                                                                class="text-[8px] font-bold text-slate-500 uppercase tracking-widest">
                                                                Patched</div>
                                                        </div>
                                                        <div class="text-right">
                                                            <div class="text-[11px] font-black"
                                                                :class="patch.failed_records > 0 ? 'text-rose-400' : 'text-slate-400'"
                                                                x-text="Number(patch.failed_records || 0).toLocaleString()">
                                                            </div>
                                                            <div
                                                                class="text-[8px] font-bold text-slate-500 uppercase tracking-widest">
                                                                Failed</div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="flex items-center justify-end gap-2">
                                                    <button type="button" x-show="canRerunBatch(patch)"
                                                        @click.stop="requestInlineRerun(patch)"
                                                        class="w-8 h-8 rounded-lg flex items-center justify-center text-blue-300 hover:bg-blue-500/10 border border border-indigo-500/20 transition-all shadow-sm"
                                                        title="Re-analyze this contract patch run">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                            stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" />
                                                        </svg>
                                                    </button>
                                                    <a x-show="patch.ledger_url" :href="patch.ledger_url" @click.stop
                                                        class="w-8 h-8 rounded-lg flex items-center justify-center text-indigo-300 hover:bg-indigo-500/10 border border-indigo-500/20 transition-all shadow-sm"
                                                        title="Open Commission Adjustment Details">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                            stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M3.75 3v4.5h16.5V3m-16.5 0h16.5M3.75 7.5v13.5h16.5V7.5M8.25 12h7.5M8.25 15.75h7.5" />
                                                        </svg>
                                                    </a>
                                                    <template
                                                        x-if="canDownload && patch.has_output && hasPatchData(patch)">
                                                        <a :href="patch.download_url" @click.stop
                                                            class="w-8 h-8 rounded-lg flex items-center justify-center text-teal-400 hover:bg-teal-500/10 border border-teal-500/20 transition-all shadow-sm"
                                                            title="Download Result">
                                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                                stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                                            </svg>
                                                        </a>
                                                    </template>
                                                    <template
                                                        x-if="canDownload && patch.has_output && !hasPatchData(patch)">
                                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-600 border border-white/5 opacity-40 cursor-not-allowed"
                                                            title="No records were patched or failed.">
                                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                                stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                                            </svg>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>

                                            {{-- Expandable Error Drawer for Patch --}}
                                            <template x-if="patch._show_error">
                                                <div
                                                    class="px-6 pb-6 pt-2 animate-in slide-in-from-top-2 fade-in duration-300">
                                                    <div
                                                        class="rounded-xl border border-rose-500/20 bg-rose-500/[0.02] p-4 shadow-inner">
                                                        <div class="flex items-center gap-2 mb-3">
                                                            <svg class="w-4 h-4 text-rose-400" fill="none"
                                                                viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                                            </svg>
                                                            <span
                                                                class="text-xs font-black uppercase tracking-wider text-rose-400">Detailed
                                                                Issue Report</span>
                                                        </div>
                                                        <div class="space-y-4">
                                                            <p class="text-[11px] text-slate-300 leading-relaxed font-medium"
                                                                x-text="patch.error_message || 'The patch engine encountered records that could not be matched.'">
                                                            </p>
                                                            <div class="grid grid-cols-2 gap-3">
                                                                <div
                                                                    class="p-2.5 rounded-lg bg-white/[0.02] border border-white/5">
                                                                    <div
                                                                        class="text-[9px] font-bold text-slate-500 uppercase mb-1">
                                                                        Impacted Records</div>
                                                                    <div class="text-xs font-mono font-bold text-rose-300"
                                                                        x-text="patch.failed_records"></div>
                                                                </div>
                                                                <div
                                                                    class="p-2.5 rounded-lg bg-white/[0.02] border border-white/5">
                                                                    <div
                                                                        class="text-[9px] font-bold text-slate-500 uppercase mb-1">
                                                                        Engine Mode</div>
                                                                    <div
                                                                        class="text-xs font-mono font-bold text-teal-300 uppercase">
                                                                        Contract ID Match</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        {{-- Toggleable Parent Error Report --}}
                        <template x-if="batch.status === 'failed' || batch.failed_records > 0">
                            <div class="mt-4 px-1">
                                <button @click="batch._show_error = !batch._show_error"
                                    class="w-full h-10 px-4 rounded-xl border border-white/5 flex items-center justify-between transition-all hover:bg-white/[0.03] group/error"
                                    style="background: rgba(244,63,94,0.02);">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-6 h-6 rounded-full flex items-center justify-center bg-rose-500/10 text-rose-400 group-hover/error:scale-110 transition-transform">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                            </svg>
                                        </div>
                                        <span
                                            class="text-[10px] font-black uppercase tracking-widest text-rose-400">Detailed
                                            Issue Report</span>
                                    </div>
                                    <svg class="w-4 h-4 text-slate-600 transition-transform duration-300"
                                        :class="batch._show_error ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </button>

                                <div x-show="batch._show_error" x-collapse>
                                    <div class="mt-2 rounded-xl border border-rose-500/20 p-4 relative overflow-hidden"
                                        style="background: linear-gradient(135deg, rgba(244,63,94,0.08) 0%, rgba(244,63,94,0.03) 100%); backdrop-filter: blur(8px);">
                                        <p class="text-[13px] text-slate-200 leading-relaxed font-medium"
                                            x-text="batch.error_message || 'An unknown system error occurred.'"></p>

                                        {{-- Fix Suggestions INSIDE the drawer --}}
                                        <div x-show="batch.error_message?.includes('missing the following required columns')"
                                            class="mt-3 pt-3 border-t border-rose-500/10">
                                            <div
                                                class="text-[10px] font-bold text-rose-300 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                                <svg class="w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                                                </svg>
                                                Troubleshooting Steps
                                            </div>
                                            <ul class="text-[11px] text-slate-400 space-y-1 ml-1 list-disc list-inside">
                                                <li>Check the Source Files box for the exact filename.</li>
                                                <li>Ensure headers match the required columns exactly.</li>
                                                <li>Save and re-upload the corrected file.</li>
                                            </ul>
                                        </div>

                                        <div x-show="batch.error_message && (batch.error_message.includes('not flagged') || batch.error_message.includes('No flagged queue records matched'))"
                                            class="mt-3 pt-3 border-t border-rose-500/10">
                                            <div
                                                class="text-[10px] font-bold text-rose-300 uppercase tracking-wider mb-2">
                                                Required Action</div>
                                            <ul class="text-[11px] text-slate-400 space-y-1 ml-1 list-disc list-inside">
                                                <li>Open Reconciliation Grid and filter to matching Contract IDs.</li>
                                                <li>Flag records and set to House Open / House Close.</li>
                                                <li>Restart the synchronization.</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                @if($batches->isEmpty())
                    <div class="px-6 py-20 text-center">
                        <svg class="w-14 h-14 mx-auto mb-4 text-slate-700" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                        </svg>
                        <p class="text-sm font-semibold text-slate-500">No reconciliation runs yet</p>
                        <p class="text-xs text-slate-600 mt-1">Upload the required files above to begin the synchronization
                            engine.</p>
                    </div>
                @endif
            </div>

            @if($batches->hasPages())
                <div class="px-6 py-4 border-t border-white/5">
                    {{ $batches->links() }}
                </div>
            @endif

        </div>

    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('uploadForm', () => ({
                    uploadMode: 'standard',
                    carrierFileName: '', carrierFileSize: '',
                    imsFileName: '', imsFileSize: '',
                    hsFileName: '', hsFileSize: '',
                    payeeFileName: '', payeeFileSize: '',
                    contractFileName: '', contractFileSize: '',
                    reanalysisMode: false,
                    reanalysisTargetBatch: null,
                    defaultParentBatchId: null,
                    latestParentBatch: @json($recentStandardBatches->isNotEmpty() ? [
                        'id' => $recentStandardBatches->first()?->id,
                        'date_short' => $recentStandardBatches->first()?->created_at->format('M d, Y')
                    ] : null),
                    hasLatestParentBatch: @json($recentStandardBatches->isNotEmpty()),
                    isStandardSyncRunning: false,
                    selectedParentBatchId: null,
                    isSubmitting: false,
                    submitError: '',
                    submitSuccess: '',
                    showLiveEtl: false,
                    liveEtlBatch: null,
                    currentUserName: @json(auth()->user()?->name ?? 'System'),
                    contractPatchEndpoint: @json(route('reconciliation.contract-patch.store')),
                    rerunEndpointTemplate: @json(route('reconciliation.batches.rerun', ['batch' => '__BATCH__'])),
                    liveEvents: [],
                    lastLiveSnapshot: null,
                    debugInfo: 'Init',

                    init() {
                        const syncFromPoller = (e) => {
                            let rawBatches = null;
                            if (e && e.detail && e.detail.batches) {
                                rawBatches = e.detail.batches;
                            } else {
                                const pollerElement = document.querySelector('[x-data="batchPoller"]');
                                if (pollerElement) {
                                    const alpineData = typeof Alpine.$data === 'function' ? Alpine.$data(pollerElement) : (pollerElement.__x ? pollerElement.__x.$data : null);
                                    rawBatches = alpineData ? (alpineData.batches || []) : [];
                                }
                            }

                            if (!rawBatches) return;
                            const batchesArray = Array.isArray(rawBatches) ? rawBatches : Object.values(rawBatches);
                            if (batchesArray.length === 0) return;

                            const standardBatches = batchesArray.filter(b => String(b.batch_type) === 'standard');

                            const running = standardBatches.some(b => String(b.status) === 'pending' || String(b.status) === 'processing');
                            this.isStandardSyncRunning = running;

                            const latestCompleted = standardBatches.find(b => ['completed', 'completed_with_errors'].includes(String(b.status)));

                            this.debugInfo = `T:${batchesArray.length},S:${standardBatches.length},L:${latestCompleted ? latestCompleted.id : 'nx'}`;

                            if (latestCompleted) {
                                this.latestParentBatch = {
                                    id: latestCompleted.id,
                                    date_short: new Date(latestCompleted.updated_at_iso || latestCompleted.created_at_iso).toLocaleString('en-US', { month: 'short', day: '2-digit', year: 'numeric' })
                                };
                                this.hasLatestParentBatch = true;
                            } else {
                                this.latestParentBatch = null;
                                this.hasLatestParentBatch = false;
                            }
                        };

                        const handleBatchUpdate = (event) => {
                            const batch = event.detail;
                            if (!batch || !batch.id) return;

                            if (this.liveEtlBatch && this.liveEtlBatch.id === batch.id) {
                                this.liveEtlBatch = {
                                    ...this.liveEtlBatch,
                                    ...batch,
                                };
                                this.syncLiveSnapshot(this.liveEtlBatch);
                            }
                        };

                        const handleInlineRerunRequest = (event) => {
                            const batch = event?.detail?.batch || null;
                            this.beginInlineReanalysis(batch);
                        };

                        window.addEventListener('batch-sync-state', syncFromPoller);
                        window.addEventListener('batch-updated', syncFromPoller);
                        window.addEventListener('batch-created', syncFromPoller);
                        window.addEventListener('batch-updated', handleBatchUpdate);
                        window.addEventListener('start-inline-rerun', handleInlineRerunRequest);
                    },

                    beginInlineReanalysis(batch) {
                        if (!batch || !batch.id) {
                            return;
                        }

                        const batchType = String(batch.batch_type || '').toLowerCase();
                        if (!['standard', 'contract_patch'].includes(batchType)) {
                            return;
                        }

                        if (batchType === 'contract_patch') {
                            if (this.uploadMode !== 'contract') {
                                this.setUploadMode('contract');
                            }
                            this.clearFile('contract');
                        } else {
                            if (this.uploadMode !== 'standard') {
                                this.setUploadMode('standard');
                            }
                            this.clearStandardFiles();
                        }

                        this.reanalysisMode = true;
                        this.reanalysisTargetBatch = { ...batch };
                        this.submitError = '';
                        this.submitSuccess = batchType === 'contract_patch'
                            ? `Re-analysis mode enabled for Contract Patch Run #${batch.id}. Keep the existing contract file or upload a replacement.`
                            : `Re-analysis mode enabled for Run #${batch.id}. Keep existing source files or upload replacements.`;

                        if (batchType === 'standard') {
                            const duplicateStrategySelect = document.getElementById('duplicate_strategy');
                            if (duplicateStrategySelect) {
                                duplicateStrategySelect.value = batch.duplicate_strategy || duplicateStrategySelect.value || 'skip';
                            }
                        }

                        this.$nextTick(() => {
                            const form = this.$refs?.syncForm;
                            if (form) {
                                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            }
                        });
                    },

                    cancelInlineReanalysis(showMessage = true) {
                        const targetBatchType = String(this.reanalysisTargetBatch?.batch_type || '').toLowerCase();
                        this.reanalysisMode = false;
                        this.reanalysisTargetBatch = null;

                        if (targetBatchType === 'contract_patch') {
                            this.clearFile('contract');
                        } else {
                            this.clearStandardFiles();
                        }

                        if (showMessage) {
                            this.submitError = '';
                            this.submitSuccess = 'In-place re-analysis has been cancelled.';
                        }
                    },

                    storedSourceName(source) {
                        if (!this.reanalysisTargetBatch) return '';

                        if (source === 'carrier') {
                            return this.reanalysisTargetBatch.carrier_original_name || '';
                        }

                        if (source === 'ims') {
                            return this.reanalysisTargetBatch.ims_original_name || '';
                        }

                        if (source === 'payee') {
                            return this.reanalysisTargetBatch.payee_original_name || '';
                        }

                        if (source === 'hs') {
                            return this.reanalysisTargetBatch.health_sherpa_original_name || '';
                        }

                        if (source === 'contract') {
                            return this.reanalysisTargetBatch.contract_original_name || '';
                        }

                        return '';
                    },

                    sourceReady(source) {
                        if (source === 'carrier') {
                            return Boolean(this.carrierFileName)
                                || (this.reanalysisMode && Boolean(this.reanalysisTargetBatch?.carrier_original_name));
                        }

                        if (source === 'ims') {
                            return Boolean(this.imsFileName)
                                || (this.reanalysisMode && Boolean(this.reanalysisTargetBatch?.ims_file_path || this.reanalysisTargetBatch?.ims_original_name));
                        }

                        if (source === 'hs') {
                            return Boolean(this.hsFileName)
                                || (this.reanalysisMode && Boolean(this.reanalysisTargetBatch?.health_sherpa_file_path || this.reanalysisTargetBatch?.health_sherpa_original_name));
                        }

                        if (source === 'contract') {
                            return Boolean(this.contractFileName)
                                || (this.reanalysisMode && Boolean(this.reanalysisTargetBatch?.contract_file_path || this.reanalysisTargetBatch?.contract_original_name));
                        }

                        return false;
                    },

                    get isReady() {
                        return this.sourceReady('carrier') && (this.sourceReady('ims') || this.sourceReady('hs'));
                    },

                    get isContractReady() {
                        const isContractRerun = this.reanalysisMode
                            && String(this.reanalysisTargetBatch?.batch_type || '').toLowerCase() === 'contract_patch'
                            && Boolean(this.reanalysisTargetBatch?.id);

                        if (isContractRerun) {
                            return this.sourceReady('contract');
                        }

                        return this.sourceReady('contract') && Boolean(this.hasLatestParentBatch) && !this.isStandardSyncRunning;
                    },

                    get activeReady() {
                        return this.uploadMode === 'contract' ? this.isContractReady : this.isReady;
                    },

                    handleFile(e, type) {
                        const file = e.target.files[0];
                        if (file) {
                            this[`${type}FileName`] = file.name;
                            this[`${type}FileSize`] = (file.size / 1024 / 1024).toFixed(2) + ' MB';
                        }
                    },

                    setUploadMode(mode) {
                        const target = mode === 'contract' ? 'contract' : 'standard';
                        if (this.uploadMode === target || this.isSubmitting) return;

                        this.uploadMode = target;
                        this.submitError = '';
                        this.submitSuccess = '';

                        if (this.reanalysisMode) {
                            const rerunType = String(this.reanalysisTargetBatch?.batch_type || '').toLowerCase();
                            const switchedAwayFromTarget = (target === 'contract' && rerunType !== 'contract_patch')
                                || (target === 'standard' && rerunType === 'contract_patch');

                            if (switchedAwayFromTarget) {
                                this.cancelInlineReanalysis(false);
                            }
                        }

                        if (target === 'contract' && !this.selectedParentBatchId && this.defaultParentBatchId) {
                            this.selectedParentBatchId = this.defaultParentBatchId;
                        }

                        if (target === 'contract') {
                            this.clearStandardFiles();
                        } else {
                            this.clearFile('contract');
                        }
                    },

                    clearFile(type) {
                        this[`${type}FileName`] = '';
                        this[`${type}FileSize`] = '';
                        // Reset the actual file input
                        const idMap = {
                            carrier: 'carrier-dropzone',
                            ims: 'ims-dropzone',
                            hs: 'hs-dropzone',
                            payee: 'payee-dropzone',
                            contract: 'contract-dropzone',
                        };
                        const input = document.getElementById(idMap[type]);
                        if (input) input.value = '';
                    },

                    clearStandardFiles() {
                        ['carrier', 'ims', 'hs', 'payee'].forEach((type) => this.clearFile(type));
                    },

                    clearSelectedFiles() {
                        ['carrier', 'ims', 'hs', 'payee', 'contract'].forEach((type) => this.clearFile(type));
                    },

                    scrollToEtlFlow() {
                        const section = document.getElementById('etlFlowSection');
                        if (section) {
                            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    },

                    scrollToLiveEtlPanel() {
                        const panel = this.$refs?.liveEtlPanel;
                        if (panel) {
                            panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            return;
                        }

                        this.scrollToEtlFlow();
                    },

                    hasLiveSource(source) {
                        if (!this.liveEtlBatch) return false;
                        if (source === 'ims') return Boolean(this.liveEtlBatch.ims_file_path);
                        if (source === 'hs') return Boolean(this.liveEtlBatch.health_sherpa_file_path);
                        if (source === 'contract') return Boolean(this.liveEtlBatch.contract_file_path || this.liveEtlBatch.contract_original_name);
                        return false;
                    },

                    isContractLiveBatch() {
                        return String(this.liveEtlBatch?.batch_type || '').toLowerCase() === 'contract_patch';
                    },

                    liveFlowRunText() {
                        if (!this.liveEtlBatch) return 'Initializing...';
                        if (this.liveEtlBatch.id && this.liveEtlBatch.id !== 'local-starting') {
                            return `#${this.liveEtlBatch.id}`;
                        }
                        return 'Initializing...';
                    },

                    liveFlowDateText() {
                        if (!this.liveEtlBatch) return '—';
                        return this.liveEtlBatch.formatted_date || new Date().toLocaleString('en-US', {
                            month: 'short',
                            day: '2-digit',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    },

                    liveFlowUploaderText() {
                        if (!this.liveEtlBatch) return this.currentUserName;
                        return this.liveEtlBatch.uploader_name || this.currentUserName;
                    },

                    safeNumber(value) {
                        const parsed = Number(value ?? 0);
                        return Number.isFinite(parsed) ? parsed : 0;
                    },

                    formatDuration(totalSeconds) {
                        const seconds = Math.max(0, Math.floor(this.safeNumber(totalSeconds)));

                        const hours = Math.floor(seconds / 3600);
                        const minutes = Math.floor((seconds % 3600) / 60);
                        const secs = seconds % 60;

                        if (hours > 0) {
                            return `${hours}h ${minutes}m`;
                        }
                        if (minutes > 0) {
                            return `${minutes}m ${secs}s`;
                        }
                        return `${secs}s`;
                    },

                    liveRateText() {
                        const rate = this.safeNumber(this.liveEtlBatch?.processed_rate_per_min || 0);
                        if (rate <= 0) return '0/min';
                        const precision = rate < 10 ? 2 : 1;
                        return `${rate.toFixed(precision)}/min`;
                    },

                    liveEtaText() {
                        if (!this.liveEtlBatch) return '—';

                        const status = String(this.liveEtlBatch.status || '').toLowerCase();
                        if (status === 'failed') return '—';
                        if (['completed', 'completed_with_errors'].includes(status)) return 'Done';

                        const etaSeconds = this.liveEtlBatch.eta_seconds;
                        if (etaSeconds === null || etaSeconds === undefined) {
                            return 'Calculating';
                        }

                        return this.formatDuration(etaSeconds);
                    },

                    liveLastUpdateText() {
                        const iso = this.liveEtlBatch?.updated_at_iso;
                        if (!iso) return 'just now';

                        const ts = new Date(iso).getTime();
                        if (!Number.isFinite(ts)) return 'just now';

                        const delta = Math.max(0, Math.floor((Date.now() - ts) / 1000));
                        if (delta <= 3) return 'just now';
                        if (delta < 60) return `${delta}s ago`;

                        const mins = Math.floor(delta / 60);
                        if (mins < 60) return `${mins}m ago`;

                        const hours = Math.floor(mins / 60);
                        return `${hours}h ago`;
                    },

                    eventToneClass(tone) {
                        if (tone === 'success') return 'bob-live-event-success';
                        if (tone === 'warning') return 'bob-live-event-warning';
                        if (tone === 'error') return 'bob-live-event-error';
                        if (tone === 'active') return 'bob-live-event-active';
                        return 'bob-live-event-info';
                    },

                    pushLiveEvent(message, tone = 'info') {
                        if (!message) return;

                        const time = new Date().toLocaleTimeString('en-US', {
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit',
                        });

                        const key = `${tone}:${message}`;
                        if (this.liveEvents.length > 0 && this.liveEvents[0].key === key) {
                            return;
                        }

                        this.liveEvents.unshift({ key, message, tone, time });
                        this.liveEvents = this.liveEvents.slice(0, 8);
                    },

                    pushAnalysisEvent(batch) {
                        const skipEntries = Object.entries(batch.skipped_summary || {});
                        const failEntries = Object.entries(batch.failure_summary || {});

                        if (skipEntries.length === 0 && failEntries.length === 0) return;

                        let msg = 'Analysis: ';
                        if (skipEntries.length > 0) {
                            msg += skipEntries.map(([reason, count]) => `${count} skipped (${reason})`).join(', ') + '. ';
                        }
                        if (failEntries.length > 0) {
                            msg += failEntries.map(([reason, count]) => `${count} failed (${reason})`).join(', ') + '.';
                        }
                        this.pushLiveEvent(msg.trim(), failEntries.length > 0 ? 'warning' : 'info');
                    },

                    hasPatchData(batch) {
                        if (!batch) return false;
                        if (batch.batch_type !== 'contract_patch') return true;

                        // For contract patches, we need at least one success or error to justify a download
                        const patched = Number(batch.contract_patched_records || 0);
                        const failed = Number(batch.failed_records || 0);
                        return patched > 0 || failed > 0;
                    },

                    syncLiveSnapshot(batch) {
                        if (!batch) return;

                        const batchType = String(batch.batch_type || 'standard').toLowerCase();
                        const isContract = batchType === 'contract_patch';

                        const snapshot = {
                            batchType,
                            status: String(batch.status || '').toLowerCase(),
                            processed: this.safeNumber(batch.processed_records),
                            total: this.safeNumber(batch.total_records),
                            ims: this.safeNumber(batch.ims_matched_records),
                            hs: this.safeNumber(batch.hs_matched_records),
                            patched: this.safeNumber(batch.contract_patched_records),
                            failed: this.safeNumber(batch.failed_records),
                        };

                        if (!this.lastLiveSnapshot) {
                            this.lastLiveSnapshot = snapshot;
                            this.pushLiveEvent('Realtime monitoring connected.', 'active');
                            return;
                        }

                        const previous = this.lastLiveSnapshot;

                        if (snapshot.status !== previous.status) {
                            const label = batch.status_label || snapshot.status || 'processing';
                            this.pushLiveEvent(`Batch status changed to ${label}.`, snapshot.status === 'failed' ? 'error' : 'active');
                        }

                        if (snapshot.processed > previous.processed) {
                            if (snapshot.total > 0) {
                                this.pushLiveEvent(`Scanned ${snapshot.processed}/${snapshot.total} records.`, 'info');
                            } else {
                                this.pushLiveEvent(`Scanned ${snapshot.processed} records.`, 'info');
                            }
                        }

                        if (isContract) {
                            const patchedDelta = snapshot.patched - previous.patched;
                            if (patchedDelta > 0) {
                                this.pushLiveEvent(`Contract patch resolved +${patchedDelta} flagged records.`, 'success');
                            }
                        } else {
                            const imsDelta = snapshot.ims - previous.ims;
                            if (imsDelta > 0) {
                                this.pushLiveEvent(`IMS matched +${imsDelta} records.`, 'success');
                            }

                            const hsDelta = snapshot.hs - previous.hs;
                            if (hsDelta > 0) {
                                this.pushLiveEvent(`Health Sherpa matched +${hsDelta} records.`, 'success');
                            }
                        }

                        const failedDelta = snapshot.failed - previous.failed;
                        if (failedDelta > 0) {
                            this.pushLiveEvent(`${failedDelta} records failed validation.`, 'warning');
                        }

                        if (snapshot.status === 'completed' && previous.status !== 'completed') {
                            this.pushLiveEvent(
                                isContract
                                    ? 'Contract patch completed successfully. Patch workbook generated.'
                                    : 'ETL flow completed successfully. Final workbook generated.',
                                'success'
                            );
                            this.pushAnalysisEvent(batch);
                        }

                        if (snapshot.status === 'completed_with_errors' && previous.status !== 'completed_with_errors') {
                            this.pushLiveEvent(
                                isContract
                                    ? 'Contract patch finished with partial errors. Review failed rows.'
                                    : 'ETL finished with partial errors. Review failed rows.',
                                'warning'
                            );
                            this.pushAnalysisEvent(batch);
                        }

                        if (snapshot.status === 'failed' && previous.status !== 'failed') {
                            this.pushLiveEvent(
                                isContract
                                    ? 'Contract patch failed. Check the error details below.'
                                    : 'ETL flow failed. Check the error details below.',
                                'error'
                            );
                            this.pushAnalysisEvent(batch);
                        }

                        this.lastLiveSnapshot = snapshot;
                    },

                    clampPercent(value) {
                        const numeric = Number(value || 0);
                        if (!Number.isFinite(numeric)) return 0;
                        return Math.max(0, Math.min(100, numeric));
                    },

                    isLiveBatchProcessing() {
                        const status = String(this.liveEtlBatch?.status || '').toLowerCase();
                        return status === 'pending' || status === 'processing';
                    },

                    etlProgressPercent() {
                        if (!this.liveEtlBatch) return 0;

                        const settled = this.clampPercent(this.liveEtlBatch.progress_pct || 0);
                        if (this.isLiveBatchProcessing()) {
                            return Math.max(12, settled || 14);
                        }

                        if (['completed', 'completed_with_errors'].includes(String(this.liveEtlBatch.status || '').toLowerCase())) {
                            return 100;
                        }

                        if (String(this.liveEtlBatch.status || '').toLowerCase() === 'failed') {
                            return Math.max(8, settled || 8);
                        }

                        return settled;
                    },

                    etlProgressStyle() {
                        const status = String(this.liveEtlBatch?.status || '').toLowerCase();
                        const gradient = status === 'failed'
                            ? 'linear-gradient(90deg,#ef4444,#fb7185)'
                            : (this.isContractLiveBatch()
                                ? 'linear-gradient(90deg,#14b8a6,#06b6d4,#22d3ee)'
                                : 'linear-gradient(90deg,#4f46e5,#3b82f6,#34d399)');
                        return `width:${this.etlProgressPercent()}%; background:${gradient};`;
                    },

                    etlSummaryText() {
                        if (!this.liveEtlBatch) return 'Waiting to start';

                        const isContract = this.isContractLiveBatch();
                        const processed = Number(this.liveEtlBatch.processed_records || 0);
                        const total = Number(this.liveEtlBatch.total_records || 0);
                        const ims = Number(this.liveEtlBatch.ims_matched_records || 0);
                        const hs = Number(this.liveEtlBatch.hs_matched_records || 0);
                        const patched = Number(this.liveEtlBatch.contract_patched_records || 0);
                        const failed = Number(this.liveEtlBatch.failed_records || 0);
                        const rate = this.liveRateText();
                        const eta = this.liveEtaText();

                        if (isContract) {
                            if (total > 0) {
                                const extra = this.isLiveBatchProcessing() ? ` · ${rate} · ETA ${eta}` : '';
                                return `${processed}/${total} scanned · Patched ${patched} · Failed ${failed}${extra}`;
                            }
                            if (processed > 0 || patched > 0) {
                                const extra = this.isLiveBatchProcessing() ? ` · ${rate}` : '';
                                return `${processed} scanned · Patched ${patched} · Failed ${failed}${extra}`;
                            }

                            return 'Initializing contract patch workers...';
                        }

                        if (total > 0) {
                            const extra = this.isLiveBatchProcessing() ? ` · ${rate} · ETA ${eta}` : '';
                            return `${processed}/${total} scanned · IMS ${ims} · HS ${hs}${extra}`;
                        }
                        if (processed > 0) {
                            const extra = this.isLiveBatchProcessing() ? ` · ${rate}` : '';
                            return `${processed} scanned · IMS ${ims} · HS ${hs}${extra}`;
                        }

                        return 'Initializing ETL workers...';
                    },

                    stageState(stage) {
                        const batch = this.liveEtlBatch;
                        if (!batch) return 'idle';

                        const status = String(batch.status || '').toLowerCase();
                        const active = status === 'pending' || status === 'processing';
                        const failed = status === 'failed';
                        const isContract = this.isContractLiveBatch();

                        if (stage === 'upload') return 'done';

                        if (isContract) {
                            if (stage === 'contract_scan' || stage === 'contract_apply') {
                                if (failed) return 'error';
                                return active ? 'active' : 'done';
                            }

                            if (stage === 'contract_finalize') {
                                if (failed) return 'error';
                                if (batch.has_output || ['completed', 'completed_with_errors'].includes(status)) return 'done';
                                return active ? 'active' : 'idle';
                            }

                            return 'idle';
                        }

                        if (stage === 'ims') {
                            if (!batch.ims_file_path) return 'skipped';
                            if (failed) return 'error';
                            return active ? 'active' : 'done';
                        }

                        if (stage === 'hs') {
                            if (!batch.health_sherpa_file_path) return 'skipped';
                            if (failed) return 'error';
                            return active ? 'active' : 'done';
                        }

                        if (stage === 'finalize') {
                            if (failed) return 'error';
                            if (batch.has_output || ['completed', 'completed_with_errors'].includes(status)) return 'done';
                            return active ? 'active' : 'idle';
                        }

                        return 'idle';
                    },

                    stageClass(stage) {
                        const state = this.stageState(stage);
                        if (state === 'done') return 'is-done';
                        if (state === 'active') return 'is-active';
                        if (state === 'error') return 'is-error';
                        if (state === 'skipped') return 'is-skipped';
                        return 'is-idle';
                    },

                    stageDotClass(stage) {
                        const state = this.stageState(stage);
                        if (state === 'done') return 'is-done';
                        if (state === 'active') return 'is-active';
                        if (state === 'error') return 'is-error';
                        if (state === 'skipped') return 'is-skipped';
                        return 'is-idle';
                    },

                    stageStateText(stage) {
                        const state = this.stageState(stage);

                        if (state === 'done') {
                            if (stage === 'finalize' || stage === 'contract_finalize') return 'Generated';
                            return 'Complete';
                        }
                        if (state === 'active') return 'Processing';
                        if (state === 'error') return 'Failed';
                        if (state === 'skipped') return 'Skipped';
                        return 'Queued';
                    },

                    normalizeApiError(payload, fallbackMessage) {
                        if (payload && typeof payload === 'object') {
                            if (payload.errors && typeof payload.errors === 'object') {
                                const firstField = Object.keys(payload.errors)[0];
                                const firstError = firstField && Array.isArray(payload.errors[firstField])
                                    ? payload.errors[firstField][0]
                                    : null;
                                if (firstError) return firstError;
                            }

                            if (typeof payload.message === 'string' && payload.message.trim()) {
                                return payload.message;
                            }
                        }

                        return fallbackMessage;
                    },

                    async submitSynchronization(event) {
                        if (this.uploadMode !== 'standard' || !this.isReady || this.isSubmitting) return;

                        const isInlineRerun = Boolean(
                            this.reanalysisMode
                            && this.reanalysisTargetBatch?.id
                            && String(this.reanalysisTargetBatch?.batch_type || '').toLowerCase() === 'standard'
                        );
                        const targetBatch = this.reanalysisTargetBatch;

                        this.isSubmitting = true;
                        this.submitError = '';
                        this.submitSuccess = isInlineRerun
                            ? `Re-analysis started for Run #${targetBatch.id}. Existing results are being replaced in place.`
                            : 'Synchronization started. The ETL flow is now running in the background.';

                        const form = event.target;
                        const formData = new FormData(form);
                        const submitUrl = isInlineRerun
                            ? this.rerunEndpointTemplate.replace('__BATCH__', encodeURIComponent(String(targetBatch.id)))
                            : form.action;

                        // Show process panel immediately and move user into ETL visibility.
                        this.liveEtlBatch = {
                            id: isInlineRerun ? targetBatch.id : 'local-starting',
                            batch_type: 'standard',
                            status: 'processing',
                            status_label: isInlineRerun ? 'Re-analyzing' : 'Starting',
                            has_output: false,
                            processed_records: 0,
                            total_records: 0,
                            ims_matched_records: 0,
                            hs_matched_records: 0,
                            contract_patched_records: 0,
                            failed_records: 0,
                            progress_pct: 6,
                            ims_pct: 0,
                            hs_pct: 0,
                            processed_rate_per_min: 0,
                            eta_seconds: null,
                            elapsed_seconds: 0,
                            ims_file_path: this.sourceReady('ims')
                                ? (this.imsFileName || targetBatch?.ims_file_path || targetBatch?.ims_original_name || 'source-ims')
                                : null,
                            health_sherpa_file_path: this.sourceReady('hs')
                                ? (this.hsFileName || targetBatch?.health_sherpa_file_path || targetBatch?.health_sherpa_original_name || 'source-hs')
                                : null,
                            contract_file_path: null,
                            contract_original_name: null,
                            formatted_date: new Date().toLocaleString('en-US', {
                                month: 'short',
                                day: '2-digit',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            }),
                            created_at_iso: new Date().toISOString(),
                            updated_at_iso: new Date().toISOString(),
                            uploader_name: this.currentUserName,
                            carrier_original_name: this.carrierFileName || targetBatch?.carrier_original_name || null,
                            payee_original_name: this.payeeFileName || targetBatch?.payee_original_name || null,
                        };
                        this.showLiveEtl = true;
                        this.liveEvents = [];
                        this.lastLiveSnapshot = null;
                        if (isInlineRerun) {
                            this.pushLiveEvent(`In-place re-analysis accepted for Run #${targetBatch.id}.`, 'active');
                            this.pushLiveEvent('Existing run artifacts will be replaced before processing.', 'warning');
                        } else {
                            this.pushLiveEvent('Upload accepted. ETL workers are starting.', 'active');
                            this.pushLiveEvent('Queued: IMS and Health Sherpa source checks.', 'info');
                        }
                        this.syncLiveSnapshot(this.liveEtlBatch);
                        this.$nextTick(() => {
                            this.scrollToLiveEtlPanel();
                            setTimeout(() => this.scrollToLiveEtlPanel(), 160);
                        });

                        try {
                            const response = await fetch(submitUrl, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': formData.get('_token') || '',
                                },
                                body: formData,
                            });

                            const contentType = String(response.headers.get('content-type') || '').toLowerCase();
                            const payload = contentType.includes('application/json')
                                ? await response.json()
                                : { message: (await response.text()).trim() };

                            if (!response.ok) {
                                this.submitSuccess = '';
                                this.submitError = this.normalizeApiError(
                                    payload,
                                    isInlineRerun
                                        ? 'Unable to start re-analysis. Please review your source files and try again.'
                                        : 'Unable to start synchronization. Please check your files and try again.'
                                );
                                this.pushLiveEvent(
                                    isInlineRerun
                                        ? 'Re-analysis request was rejected by server validation.'
                                        : 'Upload rejected by server validation.',
                                    'error'
                                );
                                if (String(this.liveEtlBatch?.id || '').startsWith('local-') || isInlineRerun) {
                                    this.liveEtlBatch = null;
                                    this.showLiveEtl = false;
                                }
                                return;
                            }

                            const createdBatch = payload?.batch || null;
                            if (createdBatch && createdBatch.id) {
                                this.liveEtlBatch = createdBatch;
                                this.showLiveEtl = true;
                                this.pushLiveEvent(
                                    isInlineRerun
                                        ? `Run #${createdBatch.id} moved to processing in-place.`
                                        : `Batch #${createdBatch.id} registered and queued.`,
                                    'active'
                                );
                                this.syncLiveSnapshot(this.liveEtlBatch);
                                window.dispatchEvent(new CustomEvent('batch-created', { detail: createdBatch }));
                            }

                            this.submitSuccess = payload?.message || (isInlineRerun
                                ? 'Re-analysis started. Existing run data is being replaced in place.'
                                : 'Synchronization started. The ETL flow is now running in the background.');

                            if (isInlineRerun) {
                                this.cancelInlineReanalysis(false);
                            }

                            this.clearSelectedFiles();
                            form.reset();
                            this.$nextTick(() => this.scrollToLiveEtlPanel());
                        } catch (error) {
                            console.error(error);
                            this.submitError = isInlineRerun
                                ? 'Network error while starting re-analysis. Please try again.'
                                : 'Network error while starting synchronization. Please try again.';
                            this.pushLiveEvent(
                                isInlineRerun
                                    ? 'Network error while starting re-analysis run.'
                                    : 'Network error while starting ETL run.',
                                'error'
                            );
                            if (String(this.liveEtlBatch?.id || '').startsWith('local-') || isInlineRerun) {
                                this.liveEtlBatch = null;
                                this.showLiveEtl = false;
                            }
                        } finally {
                            this.isSubmitting = false;
                        }
                    },

                    async submitContractPatch(event) {
                        if (this.uploadMode !== 'contract' || !this.isContractReady || this.isSubmitting) return;

                        const isInlineRerun = Boolean(
                            this.reanalysisMode
                            && this.reanalysisTargetBatch?.id
                            && String(this.reanalysisTargetBatch?.batch_type || '').toLowerCase() === 'contract_patch'
                        );
                        const targetBatch = isInlineRerun ? this.reanalysisTargetBatch : null;

                        if (!isInlineRerun && this.isStandardSyncRunning) {
                            this.submitError = 'A standard synchronization is currently in progress. Please wait for it to generate the Final BOB before patching.';
                            return;
                        }

                        if (!isInlineRerun && (!this.hasLatestParentBatch || !this.latestParentBatch)) {
                            this.submitError = 'No processed Final BOB run is available yet. Run a standard synchronization first.';
                            return;
                        }

                        const contractInput = document.getElementById('contract-dropzone');
                        const file = contractInput?.files?.[0] || null;
                        if (!file && !this.sourceReady('contract')) {
                            this.submitError = isInlineRerun
                                ? 'Please upload a replacement contract file or keep the existing source file.'
                                : 'Please upload a contract patch file.';
                            return;
                        }

                        this.isSubmitting = true;
                        this.submitError = '';
                        this.submitSuccess = isInlineRerun
                            ? `Re-analysis started for Contract Patch Run #${targetBatch.id}. Existing patch results are being replaced in place.`
                            : 'Contract patch run started. The patch engine is now running in the background.';

                        const form = event?.target || document.querySelector('form[x-ref="syncForm"]');
                        const formData = new FormData();
                        if (file) {
                            formData.append('contract_file', file);
                        }

                        // Optional hint only. Backend auto-targets the latest processed Final BOB run.
                        if (!isInlineRerun && this.latestParentBatch) {
                            formData.append('parent_batch_id', this.latestParentBatch.id);
                        }

                        // Add CSRF token manually since we're creating a clean FormData object
                        const token = form.querySelector('input[name="_token"]')?.value;
                        if (token) {
                            formData.append('_token', token);
                        }

                        const submitUrl = isInlineRerun
                            ? this.rerunEndpointTemplate.replace('__BATCH__', encodeURIComponent(String(targetBatch.id)))
                            : this.contractPatchEndpoint;

                        this.liveEtlBatch = {
                            id: isInlineRerun ? targetBatch.id : 'local-starting',
                            batch_type: 'contract_patch',
                            status: 'processing',
                            status_label: isInlineRerun ? 'Re-analyzing' : 'Starting',
                            has_output: false,
                            processed_records: 0,
                            total_records: 0,
                            ims_matched_records: 0,
                            hs_matched_records: 0,
                            contract_patched_records: 0,
                            failed_records: 0,
                            progress_pct: 4,
                            ims_pct: 0,
                            hs_pct: 0,
                            processed_rate_per_min: 0,
                            eta_seconds: null,
                            elapsed_seconds: 0,
                            ims_file_path: null,
                            health_sherpa_file_path: null,
                            contract_file_path: this.contractFileName || targetBatch?.contract_file_path || targetBatch?.contract_original_name || file?.name || null,
                            contract_original_name: this.contractFileName || targetBatch?.contract_original_name || file?.name || null,
                            formatted_date: new Date().toLocaleString('en-US', {
                                month: 'short',
                                day: '2-digit',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            }),
                            created_at_iso: new Date().toISOString(),
                            updated_at_iso: new Date().toISOString(),
                            uploader_name: this.currentUserName,
                        };

                        this.showLiveEtl = true;
                        this.liveEvents = [];
                        this.lastLiveSnapshot = null;
                        if (isInlineRerun) {
                            this.pushLiveEvent(`In-place re-analysis accepted for Patch Run #${targetBatch.id}.`, 'active');
                            this.pushLiveEvent('Previous patch mutations are being reset before processing.', 'warning');
                        } else {
                            this.pushLiveEvent('Contract patch accepted. Engine starting.', 'active');
                            this.pushLiveEvent('Targeting latest Final BOB dataset.', 'info');
                        }
                        this.syncLiveSnapshot(this.liveEtlBatch);
                        this.$nextTick(() => {
                            this.scrollToLiveEtlPanel();
                            setTimeout(() => this.scrollToLiveEtlPanel(), 160);
                        });

                        try {
                            const response = await fetch(submitUrl, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': token || '',
                                },
                                body: formData,
                            });

                            const contentType = String(response.headers.get('content-type') || '').toLowerCase();
                            const payload = contentType.includes('application/json')
                                ? await response.json()
                                : { message: (await response.text()).trim() };

                            if (!response.ok) {
                                this.submitSuccess = '';
                                this.submitError = this.normalizeApiError(
                                    payload,
                                    isInlineRerun
                                        ? 'Unable to start contract re-analysis. Please review your file and try again.'
                                        : 'Unable to start contract patch run. Please check your file and try again.'
                                );
                                this.pushLiveEvent(
                                    isInlineRerun
                                        ? 'Re-analysis request was rejected by server validation.'
                                        : 'Upload rejected by server validation.',
                                    'error'
                                );
                                if (String(this.liveEtlBatch?.id || '').startsWith('local-') || isInlineRerun) {
                                    this.liveEtlBatch = null;
                                    this.showLiveEtl = false;
                                }
                                return;
                            }

                            const createdBatch = payload?.batch || null;
                            if (createdBatch && createdBatch.id) {
                                this.liveEtlBatch = createdBatch;
                                this.showLiveEtl = true;
                                this.pushLiveEvent(
                                    isInlineRerun
                                        ? `Patch Run #${createdBatch.id} moved to processing in-place.`
                                        : `Patch Run #${createdBatch.id} registered and queued.`,
                                    'active'
                                );
                                this.syncLiveSnapshot(this.liveEtlBatch);
                                window.dispatchEvent(new CustomEvent('batch-created', { detail: createdBatch }));
                            }

                            this.submitSuccess = payload?.message || (isInlineRerun
                                ? 'Contract patch re-analysis started. Existing patch results are being replaced in place.'
                                : 'Contract patch run started. The patch engine is now running in the background.');

                            if (isInlineRerun) {
                                this.cancelInlineReanalysis(false);
                            }

                            this.clearFile('contract');
                            this.$nextTick(() => this.scrollToLiveEtlPanel());
                        } catch (error) {
                            console.error(error);
                            this.submitError = isInlineRerun
                                ? 'Network error while starting contract re-analysis. Please try again.'
                                : 'Network error while starting contract patch. Please try again.';
                            this.pushLiveEvent(
                                isInlineRerun
                                    ? 'Network error while starting contract re-analysis.'
                                    : 'Network error while starting patch run.',
                                'error'
                            );
                            if (String(this.liveEtlBatch?.id || '').startsWith('local-') || isInlineRerun) {
                                this.liveEtlBatch = null;
                                this.showLiveEtl = false;
                            }
                        } finally {
                            this.isSubmitting = false;
                        }
                    },


                }));

                // ── Real-time Batch Status Poller ─────────────────────────────────
                Alpine.data('batchPoller', (initialBatches, authz = {}) => ({
                    batches: (initialBatches || []).map(b => {
                        b._show_error = b._show_error ?? false;
                        if (b.child_patches && Array.isArray(b.child_patches)) {
                            b.child_patches.forEach(cp => cp._show_error = cp._show_error ?? false);
                        }
                        return b;
                    }),
                    canRerun: !!authz.can_rerun,
                    canDownload: !!authz.can_download,
                    canDelete: !!authz.can_delete,
                    pollingToken: null,
                    pollIntervalMs: 1800,

                    init() {
                        window.addEventListener('batch-created', (event) => {
                            const incomingBatch = event.detail;
                            if (!incomingBatch || !incomingBatch.id) return;

                            if (incomingBatch.parent_batch_id) {
                                const parentIdx = this.batches.findIndex(b => b.id == incomingBatch.parent_batch_id);
                                if (parentIdx !== -1) {
                                    if (!Array.isArray(this.batches[parentIdx].child_patches)) {
                                        this.batches[parentIdx].child_patches = [];
                                    }
                                    const existingChildIdx = this.batches[parentIdx].child_patches.findIndex(cp => cp.id == incomingBatch.id);
                                    if (existingChildIdx !== -1) {
                                        this.batches[parentIdx].child_patches.splice(existingChildIdx, 1);
                                    }
                                    this.batches[parentIdx].child_patches.unshift(incomingBatch);
                                }
                            } else {
                                const existingIndex = this.batches.findIndex(batch => batch.id == incomingBatch.id);
                                if (existingIndex !== -1) {
                                    this.batches.splice(existingIndex, 1);
                                }
                                this.batches.unshift(incomingBatch);
                            }

                            this.broadcastBatchUpdate(incomingBatch);
                            this.startPollingIfNecessary();
                        });

                        this.startPollingIfNecessary();
                        this.$nextTick(() => window.dispatchEvent(new CustomEvent('batch-sync-state', { detail: { batches: this.batches } })));
                    },

                    async deleteBatch(batchId) {
                        const confirmRes = await window.SwalBob.fire({
                            title: 'Confirm Permanent Deletion',
                            text: 'Are you sure you want to delete this reconciliation run? This action cannot be undone and will permanently remove all associated audit trails and files.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Delete Run'
                        });
                        if (confirmRes.isConfirmed) {
                            document.getElementById('delete-batch-' + batchId).submit();
                        }
                    },

                    canRerunBatch(batch) {
                        if (!this.canRerun) {
                            return false;
                        }

                        const status = String(batch?.status || '').toLowerCase();
                        const batchType = String(batch?.batch_type || '').toLowerCase();

                        if (batchType === 'standard') {
                            return ['failed', 'completed_with_errors'].includes(status);
                        }

                        if (batchType === 'contract_patch') {
                            return ['failed', 'completed_with_errors', 'completed'].includes(status);
                        }

                        return false;
                    },

                    requestInlineRerun(batch) {
                        if (!this.canRerunBatch(batch)) {
                            return;
                        }

                        window.dispatchEvent(new CustomEvent('start-inline-rerun', {
                            detail: { batch },
                        }));

                        const uploadPanel = document.querySelector('[x-data="uploadForm()"]');
                        if (uploadPanel) {
                            uploadPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }

                        if (window.SwalBob) {
                            const isContractPatch = String(batch?.batch_type || '').toLowerCase() === 'contract_patch';
                            window.SwalBob.fire({
                                title: 'Re-analysis Mode Enabled',
                                text: isContractPatch
                                    ? `Patch Run #${batch.id} is ready for in-place re-analysis in the ETL Engine panel.`
                                    : `Run #${batch.id} is ready for in-place re-analysis in the ETL Engine panel.`,
                                icon: 'info',
                                toast: true,
                                position: 'bottom-end',
                                timer: 3200,
                                showConfirmButton: false,
                            });
                        }
                    },

                    // child patches are now deleted via the parent batch lifecycle

                    isBatchProcessing(batch) {
                        const status = String(batch?.status || '').toLowerCase();
                        return status === 'pending' || status === 'processing';
                    },

                    clampPercent(value) {
                        const numeric = Number(value || 0);
                        if (!Number.isFinite(numeric)) return 0;
                        return Math.max(0, Math.min(100, numeric));
                    },

                    branchMatchPercent(batch, source) {
                        const raw = source === 'ims'
                            ? batch?.ims_pct
                            : batch?.hs_pct;

                        return this.clampPercent(raw);
                    },

                    branchProcessingPercent(batch, source) {
                        const progress = this.clampPercent(batch?.progress_pct || 0);
                        const base = source === 'ims' ? 18 : 14;
                        const span = source === 'ims' ? 72 : 68;
                        return this.clampPercent(base + ((progress / 100) * span));
                    },

                    branchBarWidth(batch, source) {
                        if (this.isBatchProcessing(batch)) {
                            return Math.max(10, this.branchProcessingPercent(batch, source));
                        }

                        const settledPercent = this.branchMatchPercent(batch, source);
                        return settledPercent > 0 ? settledPercent : 2;
                    },

                    branchBarStyle(batch, source) {
                        const width = this.branchBarWidth(batch, source);
                        const gradient = source === 'ims'
                            ? 'linear-gradient(90deg,#3b82f6,#60a5fa)'
                            : 'linear-gradient(90deg,#10b981,#34d399)';

                        return `width:${width}%; background:${gradient};`;
                    },

                    branchLabel(batch, source) {
                        const matches = source === 'ims'
                            ? Number(batch?.ims_matched_records || 0)
                            : Number(batch?.hs_matched_records || 0);

                        if (!this.isBatchProcessing(batch)) {
                            return `${matches} matched`;
                        }

                        const processed = Number(batch?.processed_records || 0);
                        const total = Number(batch?.total_records || 0);

                        if (processed > 0 && total > 0) {
                            return `${matches} matched · ${processed}/${total} scanned`;
                        }
                        if (processed > 0) {
                            return `${matches} matched · ${processed} scanned`;
                        }

                        return `${matches} matched · warming up`;
                    },

                    startPollingIfNecessary() {
                        if (this.hasActiveBatches() && !this.pollingToken) {
                            console.log('[Poller] Starting real-time sync...');
                            this.pollingToken = setInterval(() => this.fetchUpdates(), this.pollIntervalMs);
                        }
                    },

                    hasActiveBatches() {
                        return this.batches.some(b => {
                            if (!b.is_done && b.status !== 'failed') return true;
                            // A completed parent may still have an in-progress child patch
                            if (Array.isArray(b.child_patches)) {
                                return b.child_patches.some(cp => !cp.is_done && cp.status !== 'failed');
                            }
                            return false;
                        });
                    },

                    async fetchUpdates() {
                        const activeIds = [];
                        this.batches.forEach(b => {
                            if (!b.is_done && b.status !== 'failed') activeIds.push(b.id);
                            if (Array.isArray(b.child_patches)) {
                                b.child_patches.forEach(cp => {
                                    if (!cp.is_done && cp.status !== 'failed') activeIds.push(cp.id);
                                });
                            }
                        });

                        if (activeIds.length === 0) {
                            this.stopPolling();
                            return;
                        }

                        try {
                            const response = await fetch(`{{ route('reconciliation.batches.status') }}?ids[]=${activeIds.join('&ids[]=')}`);
                            if (!response.ok) throw new Error('Status check failed');

                            const updates = await response.json();
                            this.applyUpdates(updates);

                            if (!this.hasActiveBatches()) {
                                this.stopPolling();
                            }
                        } catch (e) {
                            console.error('[Poller] Error:', e);
                        }
                    },

                    applyUpdates(updates) {
                        updates.forEach(u => {
                            const idx = this.batches.findIndex(b => b.id == u.id);
                            if (idx !== -1) {
                                const existing = this.batches[idx];
                                const prevState = existing._show_error;
                                const existingChildrenState = {};

                                // Harvest existing UI states
                                if (Array.isArray(existing.child_patches)) {
                                    existing.child_patches.forEach(cp => {
                                        existingChildrenState[cp.id] = cp._show_error;
                                    });
                                }

                                Object.assign(existing, u);
                                existing._show_error = prevState ?? false;

                                // Restore and initialize children states
                                if (Array.isArray(existing.child_patches)) {
                                    existing.child_patches.forEach(cp => {
                                        cp._show_error = existingChildrenState[cp.id] ?? false;
                                    });
                                }

                                this.broadcastBatchUpdate(existing);
                            } else if (u.parent_batch_id) {
                                const parentIdx = this.batches.findIndex(b => b.id == u.parent_batch_id);
                                if (parentIdx !== -1) {
                                    const parent = this.batches[parentIdx];
                                    if (!Array.isArray(parent.child_patches)) parent.child_patches = [];
                                    const childIdx = parent.child_patches.findIndex(cp => cp.id == u.id);
                                    if (childIdx !== -1) {
                                        const prevState = parent.child_patches[childIdx]._show_error;
                                        Object.assign(parent.child_patches[childIdx], u);
                                        parent.child_patches[childIdx]._show_error = prevState ?? false;
                                        this.broadcastBatchUpdate(parent.child_patches[childIdx]);
                                    } else {
                                        u._show_error = false;
                                        parent.child_patches.push(u);
                                        this.broadcastBatchUpdate(u);
                                    }
                                }
                            }
                        });
                        window.dispatchEvent(new CustomEvent('batch-sync-state', { detail: { batches: this.batches } }));
                    },

                    broadcastBatchUpdate(batch) {
                        window.dispatchEvent(new CustomEvent('batch-updated', { detail: batch }));
                    },

                    stopPolling() {
                        if (this.pollingToken) {
                            console.log('[Poller] All runs finished. Stopping.');
                            clearInterval(this.pollingToken);
                            this.pollingToken = null;
                        }
                    }
                }));
            });
        </script>
    @endpush
    @push('head')
        <style>
            [x-cloak] {
                display: none !important;
            }

            @keyframes bob-processing-pulse {

                0%,
                100% {
                    filter: brightness(1);
                    opacity: 0.9;
                }

                50% {
                    filter: brightness(1.2);
                    opacity: 1;
                }
            }

            @keyframes bob-processing-scan {
                0% {
                    transform: translateX(-120%);
                    opacity: 0;
                }

                20% {
                    opacity: 1;
                }

                100% {
                    transform: translateX(240%);
                    opacity: 0;
                }
            }

            @keyframes bob-processing-dot {

                0%,
                100% {
                    transform: scale(0.9);
                    opacity: 0.8;
                }

                50% {
                    transform: scale(1.15);
                    opacity: 1;
                }
            }

            .bob-processing-fill {
                position: relative;
                overflow: hidden;
                animation: bob-processing-pulse 2.2s ease-in-out infinite;
            }

            .bob-processing-fill::after {
                content: '';
                position: absolute;
                inset: 0;
                width: 42%;
                background: linear-gradient(90deg,
                        rgba(255, 255, 255, 0),
                        rgba(255, 255, 255, 0.45),
                        rgba(255, 255, 255, 0));
                transform: translateX(-120%);
                animation: bob-processing-scan 1.9s cubic-bezier(0.4, 0, 0.2, 1) infinite;
                pointer-events: none;
            }

            .bob-processing-fill-ims {
                box-shadow: 0 0 12px rgba(59, 130, 246, 0.35);
            }

            .bob-processing-fill-hs {
                box-shadow: 0 0 12px rgba(16, 185, 129, 0.3);
            }

            .bob-processing-chip {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                font-size: 9px;
                font-weight: 700;
                letter-spacing: 0.05em;
                text-transform: uppercase;
                border-radius: 999px;
                padding: 2px 7px;
                border: 1px solid transparent;
                backdrop-filter: blur(3px);
            }

            .bob-processing-chip-ims {
                color: #93c5fd;
                background: rgba(37, 99, 235, 0.12);
                border-color: rgba(96, 165, 250, 0.22);
            }

            .bob-processing-chip-hs {
                color: #6ee7b7;
                background: rgba(5, 150, 105, 0.12);
                border-color: rgba(52, 211, 153, 0.22);
            }

            .bob-processing-chip-dot {
                width: 5px;
                height: 5px;
                border-radius: 999px;
                background: currentColor;
                animation: bob-processing-dot 1.1s ease-in-out infinite;
            }

            .bob-etl-live-card {
                background: linear-gradient(130deg, rgba(37, 99, 235, 0.11), rgba(79, 70, 229, 0.06) 40%, rgba(16, 185, 129, 0.05));
                border-color: rgba(99, 102, 241, 0.24);
                box-shadow: inset 0 1px 0 rgba(129, 140, 248, 0.12);
            }

            .bob-etl-stage {
                display: flex;
                align-items: center;
                gap: 10px;
                border-radius: 10px;
                border: 1px solid rgba(148, 163, 184, 0.16);
                background: rgba(15, 23, 42, 0.34);
                padding: 9px 10px;
                min-height: 56px;
                transition: border-color 160ms ease, background-color 160ms ease;
            }

            .bob-etl-stage.is-active {
                border-color: rgba(96, 165, 250, 0.34);
                background: rgba(30, 64, 175, 0.18);
            }

            .bob-etl-stage.is-done {
                border-color: rgba(16, 185, 129, 0.32);
                background: rgba(6, 95, 70, 0.18);
            }

            .bob-etl-stage.is-error {
                border-color: rgba(251, 113, 133, 0.36);
                background: rgba(136, 19, 55, 0.2);
            }

            .bob-etl-stage.is-skipped {
                border-style: dashed;
                border-color: rgba(100, 116, 139, 0.26);
                background: rgba(15, 23, 42, 0.2);
            }

            .bob-etl-stage-dot {
                width: 10px;
                height: 10px;
                border-radius: 999px;
                background: #334155;
                flex-shrink: 0;
            }

            .bob-etl-stage-dot.is-active {
                background: #60a5fa;
                box-shadow: 0 0 0 6px rgba(59, 130, 246, 0.15);
                animation: bob-processing-dot 1.1s ease-in-out infinite;
            }

            .bob-etl-stage-dot.is-done {
                background: #34d399;
                box-shadow: 0 0 0 6px rgba(16, 185, 129, 0.14);
            }

            .bob-etl-stage-dot.is-error {
                background: #fb7185;
                box-shadow: 0 0 0 6px rgba(244, 63, 94, 0.14);
            }

            .bob-etl-stage-dot.is-skipped {
                background: #64748b;
            }

            .bob-etl-stage-dot.is-idle {
                background: #475569;
            }

            .bob-etl-stage-title {
                font-size: 10px;
                font-weight: 800;
                letter-spacing: 0.05em;
                text-transform: uppercase;
                color: #cbd5e1;
            }

            .bob-etl-stage-state {
                margin-top: 2px;
                font-size: 10px;
                font-weight: 700;
                color: #94a3b8;
            }

            .bob-etl-metric-card {
                border: 1px solid rgba(148, 163, 184, 0.2);
                border-radius: 10px;
                background: rgba(15, 23, 42, 0.34);
                padding: 8px 10px;
                min-height: 58px;
            }

            .bob-etl-metric-label {
                font-size: 9px;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                color: #94a3b8;
            }

            .bob-etl-metric-value {
                margin-top: 4px;
                font-size: 13px;
                font-weight: 800;
                color: #e2e8f0;
                line-height: 1;
            }

            .bob-etl-event-box {
                border-color: rgba(148, 163, 184, 0.2);
                background: rgba(15, 23, 42, 0.28);
            }

            .bob-etl-event-head {
                border-color: rgba(148, 163, 184, 0.18);
                background: rgba(2, 6, 23, 0.36);
            }

            .bob-live-event-info {
                color: #cbd5e1;
            }

            .bob-live-event-active {
                color: #a5b4fc;
            }

            .bob-live-event-success {
                color: #6ee7b7;
            }

            .bob-live-event-warning {
                color: #fcd34d;
            }

            .bob-live-event-error {
                color: #fda4af;
            }

            @media (max-width: 640px) {
                .bob-etl-metric-value {
                    font-size: 12px;
                }
            }
        </style>
    @endpush
</x-reconciliation-layout>