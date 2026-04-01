<x-reconciliation-layout>
    <x-slot name="pageTitle">Import Feeds</x-slot>
    <x-slot name="pageSubtitle">Upload carrier & IMS data files for reconciliation processing</x-slot>

    <x-slot name="headerActions">
        <a href="{{ route('reconciliation.dashboard') }}" class="bob-btn-ghost">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
            Back to Grid
        </a>
    </x-slot>

    <div class="space-y-6">

        {{-- ── Upload Card ── --}}
        <div class="bob-glass-panel p-6">
            <form action="{{ route('reconciliation.upload.store') }}" method="POST" enctype="multipart/form-data" x-data="uploadForm()">
                @csrf
                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="type" class="bob-form-label">Source Type</label>
                            <select id="type" name="type" class="bob-select w-full">
                                <option value="carrier">Carrier Feed (BOB)</option>
                                <option value="ims">IMS Data Export</option>
                            </select>
                        </div>
                        <div>
                            <label for="duplicate_strategy" class="bob-form-label">Duplicate Strategy</label>
                            <select id="duplicate_strategy" name="duplicate_strategy" class="bob-select w-full">
                                <option value="skip">Skip existing records</option>
                                <option value="update">Update existing records</option>
                            </select>
                        </div>
                    </div>

                    {{-- Dropzone --}}
                    <label for="dropzone-file" class="bob-dropzone" :class="{ '!border-[#6366f1] !bg-[#6366f1]/10': fileName }">
                        <template x-if="!fileName">
                            <div class="flex flex-col items-center justify-center py-4 text-slate-400">
                                <svg class="w-10 h-10 mb-4 text-[#6366f1]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                </svg>
                                <p class="text-sm mb-1"><span class="font-bold text-[#818cf8]">Click to upload</span> or drag and drop</p>
                                <p class="text-[11px] font-medium tracking-wide">CSV, XLS, XLSX (MAX. 50MB)</p>
                            </div>
                        </template>
                        <template x-if="fileName">
                            <div class="flex items-center gap-4 py-4">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background: rgba(16,185,129,0.15);">
                                    <svg class="w-6 h-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-white tracking-wide" x-text="fileName"></div>
                                    <div class="text-[11px] font-semibold text-slate-400" x-text="fileSize"></div>
                                </div>
                            </div>
                        </template>
                        <input id="dropzone-file" type="file" name="file" class="hidden" accept=".csv,.xls,.xlsx" required @change="handleFile($event)" />
                    </label>

                    @if($errors->any())
                        <div class="rounded-xl p-4 text-sm border font-medium" style="background: rgba(225,29,72,0.1); border-color: rgba(225,29,72,0.2); color: #fb7185;">
                            @foreach($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="bob-btn-primary">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg>
                            Upload & Process
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- ── Batches Table ── --}}
        <div class="bob-glass-panel pb-2">
            <div class="px-6 py-5 flex items-center justify-between border-b border-white/5">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z" /></svg>
                    <h3 class="text-sm font-bold text-white tracking-wide">Recent Imports</h3>
                </div>
                <span class="text-[11px] font-bold px-2.5 py-1 rounded-md text-indigo-400" style="background: rgba(99,102,241,0.1);">{{ $batches->total() }} total</span>
            </div>

            <div class="overflow-x-auto">
                <table class="bob-table">
                    <thead>
                        <tr>
                            <th>File</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Progress</th>
                            <th>Uploaded By</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($batches as $batch)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                    <span class="truncate max-w-[200px] text-white">{{ $batch->original_name }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="bob-badge" style="background: rgba(56,189,248,0.1); color: #38bdf8; border: 1px solid rgba(56,189,248,0.2);">{{ strtoupper($batch->type) }}</span>
                            </td>
                            <td>
                                @php
                                    $statusMap = [
                                        'completed' => 'bob-badge-matched',
                                        'processing' => 'bob-badge-pending',
                                        'failed' => 'bob-badge-flagged',
                                        'completed_with_errors' => 'bob-badge-flagged',
                                        'pending' => 'bob-badge-pending',
                                    ];
                                @endphp
                                <span class="bob-badge {{ $statusMap[$batch->status] ?? 'bob-badge-pending' }}">
                                    {{ ucfirst(str_replace('_', ' ', $batch->status)) }}
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center gap-3">
                                    @php $pct = $batch->total_records ? round(($batch->processed_records / $batch->total_records) * 100) : 0; @endphp
                                    <div class="w-16 h-1.5 rounded-full overflow-hidden" style="background: rgba(255,255,255,0.1);">
                                        <div class="h-full rounded-full" style="width: {{ $pct }}%; background: linear-gradient(90deg, #10b981, #0ea5e9);"></div>
                                    </div>
                                    <span class="text-[11px] font-mono font-bold text-slate-400">{{ $batch->processed_records }}/{{ $batch->total_records ?? '?' }}</span>
                                    @if($batch->failed_records > 0)
                                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded" style="background: rgba(225,29,72,0.1); color: #fb7185;">{{ $batch->failed_records }} err</span>
                                    @endif
                                </div>
                            </td>
                            <td>{{ $batch->uploadedBy?->name ?? 'System' }}</td>
                            <td>{{ $batch->created_at->diffForHumans() }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-10 text-slate-500 font-medium">No imports yet. Upload your first file.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
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
                fileName: '',
                fileSize: '',
                handleFile(e) {
                    const file = e.target.files[0];
                    if (file) {
                        this.fileName = file.name;
                        this.fileSize = (file.size / 1024 / 1024).toFixed(2) + ' MB';
                    }
                }
            }));
        });
    </script>
    @endpush
</x-reconciliation-layout>
