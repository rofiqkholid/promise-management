@extends('layouts.app')

@section('title', 'Inquiry — ' . $inquiry->inquiry_no . ' · Promise Management')

@section('content')
@php
    $selectedOptionsByProduct = [];
    foreach($inquiry->products as $p) {
        if ($p->assessment && $p->assessment->details) {
            foreach($p->assessment->details as $d) {
                $selectedOptionsByProduct[$p->inquiry_product_id][$d->category_id] = $d->option_id;
            }
        }
    }
    $isLocked = in_array($inquiry->status, ['Closed', 'Cancelled']);

    $optionScores = [];
    foreach($scoreCategories as $cat) {
        foreach($cat->options as $opt) {
            $optionScores[$opt->option_id] = $opt->score_value;
        }
    }
    $rankings = \App\Models\AssessmentRanking::where('is_active', true)->orderBy('min_score', 'asc')->get();
@endphp

{{-- ───── Inline Styles ───────────────────────────────────────────────── --}}
<style>
    /* Toast */
    #erp-toast { pointer-events: none; transition: opacity .3s, transform .3s; }
    #erp-toast.show { opacity:1 !important; transform: translateY(0) !important; }

    /* Accordion slide */
    .accord-body { display: none; }
    .accord-body.open { display: table-row; }

    /* Scoring pill */
    .opt-pill {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 2px 8px; font-size: 10px; font-weight: 700;
        border: 1px solid; cursor: pointer; transition: background .15s;
        border-radius: 0; user-select: none;
    }
    .opt-pill.selected-pill {
        background: #2563eb !important;
        border-color: #2563eb !important;
        color: #fff !important;
    }
    .opt-pill:not(.selected-pill) {
        background: transparent;
        border-color: #cbd5e1;
        color: #64748b;
    }
    .dark .opt-pill:not(.selected-pill) { border-color:#475569; color:#94a3b8; }
    .opt-pill:hover:not(.selected-pill) { background:#f1f5f9; }
    .dark .opt-pill:hover:not(.selected-pill) { background:#1e293b; }

    /* Spinning loader inside button */
    .spin { animation: spin 1s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* Table row highlight on expand */
    tr.is-expanded > td { background: #eff6ff; }
    .dark tr.is-expanded > td { background: #1e3a5f20; }

    /* Score badge colors */
    .score-badge {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 36px; height: 24px; padding: 0 8px;
        font-size: 12px; font-weight: 800; border: 1px solid;
    }
    .score-high   { background:#dcfce7; border-color:#86efac; color:#15803d; }
    .score-mid    { background:#fef3c7; border-color:#fcd34d; color:#b45309; }
    .score-low    { background:#fee2e2; border-color:#fca5a5; color:#dc2626; }
    .score-none   { background:#f1f5f9; border-color:#cbd5e1; color:#94a3b8; }
    .dark .score-high  { background:#14532d40; border-color:#166534; color:#4ade80; }
    .dark .score-mid   { background:#78350f40; border-color:#92400e; color:#fcd34d; }
    .dark .score-low   { background:#7f1d1d40; border-color:#991b1b; color:#f87171; }
    .dark .score-none  { background:#1e293b; border-color:#334155; color:#64748b; }
</style>

{{-- ───── Toast Notification ─────────────────────────────────────────── --}}
<div id="erp-toast"
     class="fixed top-5 right-5 z-[9999] opacity-0 translate-y-[-8px] flex items-center gap-3 px-4 py-3 bg-white dark:bg-slate-800 border shadow-lg text-xs font-semibold text-slate-800 dark:text-slate-100 min-w-[240px]"
     style="opacity:0; transform:translateY(-8px);">
    <i id="erp-toast-icon" class="fa-solid fa-circle-check text-emerald-500 text-base"></i>
    <span id="erp-toast-msg">Saved.</span>
</div>

<div class="flex-1 overflow-y-auto p-4 pt-17.5 space-y-0 transition-colors duration-200"
     x-data="inquiryManagement">

    {{-- ── Loading Overlay ──────────────────────────────────────────────── --}}
    <div x-show="loading" class="fixed inset-0 z-[9000] bg-black/20 flex items-center justify-center" style="display:none;">
        <div class="bg-white dark:bg-slate-800 px-5 py-4 flex items-center gap-3 shadow-xl border border-slate-200 dark:border-slate-700">
            <i class="fa-solid fa-circle-notch fa-spin text-blue-600 text-lg"></i>
            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">Processing…</span>
        </div>
    </div>

    {{-- ── Unified Inquiry Header Card ────────────────────────────────────── --}}
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 mb-3 shadow-sm">
        
        {{-- Card Header: Title and Actions --}}
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 p-4 border-b border-slate-100 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-900/20">
            <div class="flex items-center gap-3">
                <a href="{{ route('management.inquiry.index') }}"
                   class="flex items-center justify-center w-7 h-7 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-500 hover:text-blue-600 hover:border-blue-500 transition-colors text-xs"
                   title="Back to list">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none mb-1">
                        Project Inquiry
                        <template x-if="selectedSpkProducts.length > 0">
                            <span class="text-blue-600 dark:text-blue-400 normal-case font-bold ml-1">
                                (<span x-text="selectedSpkProducts.length"></span> items selected for SPK)
                            </span>
                        </template>
                    </div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-base font-extrabold tracking-tight text-slate-800 dark:text-white leading-none">
                            {{ $inquiry->inquiry_no }}
                        </h1>
                        {{-- Status Badge --}}
                        @php
                            $statusMap = [
                                'Draft'     => ['dot' => 'bg-blue-500',    'cls' => 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-900/30'],
                                'Active'    => ['dot' => 'bg-sky-500',     'cls' => 'bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-400 border-sky-200 dark:border-sky-900/30'],
                                'Closed'    => ['dot' => 'bg-emerald-500', 'cls' => 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-900/30'],
                                'Cancelled' => ['dot' => 'bg-rose-500',    'cls' => 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-900/30'],
                            ];
                            $st = $statusMap[$inquiry->status] ?? ['dot' => 'bg-slate-400', 'cls' => 'bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700'];
                        @endphp
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 text-[10px] font-bold border {{ $st['cls'] }}">
                            <span class="w-1 h-1 rounded-full {{ $st['dot'] }}"></span>
                            {{ $inquiry->status }}
                        </span>
                        <span class="text-[10px] text-slate-400 dark:text-slate-500 font-medium ml-1">
                            {{ $inquiry->inquiry_date->format('d M Y') }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Actions Toolbar --}}
            <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto justify-start lg:justify-end">
                <template x-if="selectedSpkProducts.length > 0">
                    <a :href="`{{ Route::has('management.work-order.create') ? route('management.work-order.create') : '#' }}?inquiry_id={{ $inquiry->inquiry_id }}&products=${selectedSpkProducts.join(',')}`"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm transition-colors cursor-pointer">
                        <i class="fa-solid fa-file-signature text-[10px]"></i> Create SPK (<span x-text="selectedSpkProducts.length"></span>)
                    </a>
                </template>
                @if(!$isLocked)
                    <button @click="openAddProduct()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-sm transition-colors cursor-pointer">
                        <i class="fa-solid fa-plus text-[10px]"></i> Add Product
                    </button>
                    <button @click="showImportModal = true"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs font-semibold shadow-sm transition-colors cursor-pointer">
                        <i class="fa-solid fa-file-excel text-emerald-600 dark:text-emerald-500 text-[10px]"></i> Import Excel
                    </button>
                @endif

                @if($inquiry->status === 'Draft')
                    @if(!$isLocked)
                        <div class="h-5 w-[1px] bg-slate-200 dark:bg-slate-700 mx-1"></div>
                    @endif
                    <button @click="showEditModal = true"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs font-semibold shadow-sm transition-colors cursor-pointer">
                        <i class="fa-solid fa-pen-to-square text-[10px]"></i> Edit Info
                    </button>
                    <form method="POST" action="{{ route('management.inquiry.close', $inquiry->inquiry_id) }}"
                          onsubmit="return confirm('Close this inquiry? It will become read-only.')"
                          class="inline">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-sm transition-colors cursor-pointer">
                            <i class="fa-solid fa-lock text-[10px]"></i> Close Inquiry
                        </button>
                    </form>
                    <form method="POST" action="{{ route('management.inquiry.cancel', $inquiry->inquiry_id) }}"
                          onsubmit="return confirm('Cancel this inquiry?')"
                          class="inline">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-sm transition-colors cursor-pointer">
                            <i class="fa-solid fa-ban text-[10px]"></i> Cancel
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Card Body: Inquiry Details Grid --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-slate-100 dark:divide-slate-700/60">
            <div class="px-4 py-3">
                <span class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-0.5">Customer</span>
                <span class="text-xs font-bold text-slate-800 dark:text-slate-100">{{ $inquiry->customer_name }}</span>
            </div>
            <div class="px-4 py-3">
                <span class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-0.5">Project</span>
                <span class="text-xs font-bold text-slate-800 dark:text-slate-100">{{ $inquiry->project_name }}</span>
            </div>
            <div class="px-4 py-3">
                <span class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-0.5">Products</span>
                <span class="text-xs font-bold text-slate-800 dark:text-slate-100">{{ $inquiry->products->count() }} items</span>
            </div>
            <div class="px-4 py-3">
                <span class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-0.5">Remarks</span>
                <span class="text-xs font-medium text-slate-600 dark:text-slate-300 truncate block" title="{{ $inquiry->remarks }}">
                    {{ $inquiry->remarks ?: '—' }}
                </span>
            </div>
        </div>
    </div>

    {{-- ── Flash & Import Alerts ─────────────────────────────────────────── --}}
    @if(session('success'))
        <div class="mb-3 flex items-start gap-2 p-3 bg-emerald-50 dark:bg-emerald-950/30 border-l-4 border-emerald-500 text-emerald-800 dark:text-emerald-400 text-xs">
            <i class="fa-solid fa-circle-check mt-0.5"></i>{{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-3 flex items-start gap-2 p-3 bg-rose-50 dark:bg-rose-950/30 border-l-4 border-rose-500 text-rose-800 dark:text-rose-400 text-xs">
            <i class="fa-solid fa-circle-xmark mt-0.5"></i>{{ session('error') }}
        </div>
    @endif
    @if(session('import_errors') && is_array(session('import_errors')) && count(session('import_errors')) > 0)
        <div class="mb-3 space-y-2 p-3 bg-amber-50 dark:bg-amber-950/20 border-l-4 border-amber-500 text-amber-800 dark:text-amber-400 text-xs">
            <p class="font-bold"><i class="fa-solid fa-triangle-exclamation mr-1"></i> Excel Import Warnings/Errors:</p>
            <div class="max-h-[150px] overflow-y-auto space-y-1 divide-y divide-amber-100 dark:divide-amber-900/20">
                @foreach(session('import_errors') as $err)
                    <div class="pt-1 text-[11px]">
                        <strong>Row {{ $err['row'] ?? '?' }}:</strong>
                        <span>{{ is_array($err['errors']) ? implode(', ', $err['errors']) : $err['errors'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ── Products Table ───────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80">

        {{-- Table toolbar --}}
        <div class="flex items-center justify-between px-4 py-2.5 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40">
            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Products &amp; Feasibility Scoring</span>
        </div>

        <div class="overflow-x-auto w-full">
            <table class="w-full text-left border-collapse text-xs">
                {{-- ── Table Head ──────────────────────────────────────────── --}}
                <thead>
                    <tr class="border-b-2 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/30 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                        <th class="px-3 py-2.5 w-8 text-center">#</th>
                        <th class="px-3 py-2.5 w-10 text-center">
                            <input type="checkbox"
                                   :checked="selectedSpkProducts.length === products.length && products.length > 0"
                                   @change="toggleSelectAllSpk($event.target.checked)"
                                   class="w-3.5 h-3.5 text-blue-600 border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-blue-500 cursor-pointer rounded-xs"
                                   title="Select/Deselect All Products for SPK">
                        </th>
                        <th class="px-3 py-2.5">Model Name</th>
                        <th class="px-3 py-2.5">Part Number</th>
                        <th class="px-3 py-2.5">Part Name</th>
                        <th class="px-3 py-2.5">Category</th>
                        <th class="px-3 py-2.5 text-right">Ann. Vol.</th>
                        <th class="px-3 py-2.5 text-center w-20">Score</th>
                        <th class="px-3 py-2.5 text-center w-16">Rank</th>
                        <th class="px-3 py-2.5 text-center w-32">Priority</th>
                        <th class="px-3 py-2.5 text-right w-36">Actions</th>
                    </tr>
                </thead>
                <tbody id="products-tbody">
                    @forelse($inquiry->products as $index => $prod)
                        @php
                            $score   = $prod->assessment->total_score ?? 0;
                            $prevScore = isset($inquiry->products[$index - 1]) ? ($inquiry->products[$index - 1]->assessment->total_score ?? 0) : null;
                            $nextScore = isset($inquiry->products[$index + 1]) ? ($inquiry->products[$index + 1]->assessment->total_score ?? 0) : null;
                            $hasPrevSame = $prevScore !== null && $prevScore === $score;
                            $hasNextSame = $nextScore !== null && $nextScore === $score;

                            $rankCode  = $prod->assessment->ranking->rank_code ?? null;
                            $rankLabel = $prod->assessment->ranking->priority_label ?? null;

                            // Score badge color class
                            if ($score === 0) { $scoreClass = 'score-none'; }
                            elseif ($score >= 70) { $scoreClass = 'score-high'; }
                            elseif ($score >= 40) { $scoreClass = 'score-mid'; }
                            else { $scoreClass = 'score-low'; }

                            // Priority pill class
                            $priorityClass = match($rankLabel) {
                                'Review Now'  => 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border-rose-300 dark:border-rose-900/50',
                                'Review Next' => 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border-amber-300 dark:border-amber-900/50',
                                'Pending'     => 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400 border-blue-300 dark:border-blue-900/50',
                                default       => 'bg-slate-50 dark:bg-slate-900 text-slate-500 dark:text-slate-400 border-slate-200 dark:border-slate-700',
                            };
                        @endphp

                        {{-- ── Product Row ─────────────────────────────────── --}}
                        <tr id="row-{{ $prod->inquiry_product_id }}"
                            class="border-b border-slate-100 dark:border-slate-700/50 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors duration-100 cursor-pointer"
                            @click="openEditProduct({{ $prod->inquiry_product_id }})">

                            {{-- Row number --}}
                            <td class="px-3 py-2.5 text-center text-slate-400 font-mono text-[10px]">{{ $index + 1 }}</td>

                            {{-- Checkbox for SPK selection --}}
                            <td class="px-3 py-2.5 text-center" onclick="event.stopPropagation()">
                                <input type="checkbox"
                                       :checked="isProductSelectedForSpk({{ $prod->inquiry_product_id }})"
                                       @change="toggleSpkSelection({{ $prod->inquiry_product_id }}, $event.target.checked)"
                                       class="w-3.5 h-3.5 text-blue-600 border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-blue-500 cursor-pointer rounded-xs">
                            </td>

                            <td class="px-3 py-2.5 font-bold text-slate-800 dark:text-slate-100">
                                {{ $prod->model_name }}
                            </td>
                            <td class="px-3 py-2.5 font-mono text-slate-600 dark:text-slate-300">
                                {{ $prod->customer_part_no }}
                            </td>
                            <td class="px-3 py-2.5 text-slate-600 dark:text-slate-300 max-w-[180px] truncate" title="{{ $prod->customer_part_name }}">
                                {{ $prod->customer_part_name }}
                            </td>
                            <td class="px-3 py-2.5 text-slate-500 dark:text-slate-400">
                                {{ $prod->part_category ?: '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-right font-mono text-slate-700 dark:text-slate-300">
                                {{ $prod->annual_volume ? number_format($prod->annual_volume) : '—' }}
                            </td>

                            {{-- Total Score --}}
                            <td class="px-3 py-2.5 text-center">
                                <span id="score-badge-{{ $prod->inquiry_product_id }}"
                                      class="score-badge {{ $scoreClass }}">
                                    {{ $score }}
                                </span>
                            </td>

                            {{-- Rank Code --}}
                            <td class="px-3 py-2.5 text-center">
                                <span id="rank-code-{{ $prod->inquiry_product_id }}"
                                      class="inline-block px-1.5 py-0.5 text-[10px] font-bold bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                    {{ $rankCode ?? '—' }}
                                </span>
                            </td>

                            {{-- Priority Label --}}
                            <td class="px-3 py-2.5 text-center">
                                <span id="priority-{{ $prod->inquiry_product_id }}"
                                      class="inline-block px-2 py-0.5 text-[10px] font-bold border {{ $priorityClass }}">
                                    {{ $rankLabel ?? '—' }}
                                </span>
                            </td>

                            {{-- Action Buttons --}}
                            <td class="px-3 py-2.5 text-right" onclick="event.stopPropagation()">
                                <div class="inline-flex items-center gap-1 justify-end">
                                    @if($hasPrevSame)
                                        <button @click.stop="reorder({{ $prod->inquiry_product_id }}, 'up')"
                                                title="Move Up (same score)"
                                                class="w-6 h-6 flex items-center justify-center bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 border border-slate-200 dark:border-slate-600 text-slate-500 dark:text-slate-300 text-[10px] transition-colors">
                                            <i class="fa-solid fa-arrow-up"></i>
                                        </button>
                                    @endif
                                    @if($hasNextSame)
                                        <button @click.stop="reorder({{ $prod->inquiry_product_id }}, 'down')"
                                                title="Move Down (same score)"
                                                class="w-6 h-6 flex items-center justify-center bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 border border-slate-200 dark:border-slate-600 text-slate-500 dark:text-slate-300 text-[10px] transition-colors">
                                            <i class="fa-solid fa-arrow-down"></i>
                                        </button>
                                    @endif
                                    @if(!$isLocked)
                                        <button @click.stop="openEditProduct({{ $prod->inquiry_product_id }})"
                                                title="Edit product details"
                                                class="inline-flex items-center gap-1 px-2 py-1 bg-slate-100 dark:bg-slate-700 hover:bg-blue-50 dark:hover:bg-blue-950/30 border border-slate-200 dark:border-slate-600 hover:border-blue-300 dark:hover:border-blue-700 text-slate-600 dark:text-slate-300 hover:text-blue-700 dark:hover:text-blue-400 text-[10px] font-semibold transition-colors">
                                            <i class="fa-solid fa-pen text-[9px]"></i> Edit
                                        </button>
                                    @else
                                        <span class="text-[10px] text-slate-400 italic">Locked</span>
                                    @endif
                                </div>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="11" class="py-16 text-center">
                                <i class="fa-solid fa-folder-open text-4xl text-slate-300 dark:text-slate-600 block mb-3"></i>
                                <p class="text-sm font-semibold text-slate-400">No products have been added yet.</p>
                                <p class="text-xs text-slate-400 mt-1">Use the <strong>Add Product</strong> button or import via Excel.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Table footer --}}
        <div class="px-4 py-2.5 border-t border-slate-100 dark:border-slate-700/60 bg-slate-50 dark:bg-slate-900/30 flex items-center justify-between">
            <span class="text-[10px] text-slate-400">
                {{ $inquiry->products->count() }} product(s) listed &nbsp;·&nbsp;
                Click any row to edit specifications &amp; scoring
            </span>
            @if($isLocked)
                <span class="inline-flex items-center gap-1.5 text-[10px] font-semibold text-amber-600 dark:text-amber-400">
                    <i class="fa-solid fa-lock text-[9px]"></i> This inquiry is {{ strtolower($inquiry->status) }} — read-only
                </span>
            @endif
        </div>
    </div>


    {{-- ═══════════════════ MODALS ══════════════════════════════════════════ --}}

    {{-- ── Edit Inquiry Metadata Modal ──────────────────────────────────── --}}
    <div x-show="showEditModal"
         class="fixed inset-0 z-[8000] flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4"
         style="display:none;">
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 w-full max-w-lg shadow-2xl">
            {{-- Modal Header --}}
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40">
                <h3 class="text-sm font-bold text-slate-800 dark:text-white">Edit Inquiry Metadata</h3>
                <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg leading-none">&times;</button>
            </div>
            {{-- Modal Body --}}
            <form @submit.prevent="submitEdit" class="p-5 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Customer Name <span class="text-rose-500">*</span></label>
                        <input type="text" x-model="editForm.customer_name" required
                               class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Project Name <span class="text-rose-500">*</span></label>
                        <input type="text" x-model="editForm.project_name" required
                               class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500">
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Inquiry Date <span class="text-rose-500">*</span></label>
                    <input type="date" x-model="editForm.inquiry_date" required
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Remarks</label>
                    <textarea x-model="editForm.remarks" rows="3"
                              class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 resize-none"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-700">
                    <button type="button" @click="showEditModal = false"
                            class="px-4 py-2 text-xs font-semibold border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white transition-colors">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Add / Edit Product Modal ──────────────────────────────────────── --}}
    <div x-show="showProductModal"
         class="fixed inset-0 z-[8000] flex items-center justify-center bg-black/40 p-4"
         style="display:none;" x-cloak>
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 w-full max-w-5xl shadow-2xl flex flex-col max-h-[92vh]">
            {{-- Modal Header --}}
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40 flex-shrink-0">
                <h3 class="text-sm font-bold text-slate-800 dark:text-white"
                    x-text="productModalMode === 'add' ? 'Add New Product' : 'Edit Product & Assessment'"></h3>
                <button @click="showProductModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg leading-none">&times;</button>
            </div>

            <form @submit.prevent="submitProductForm" class="flex flex-col flex-1 overflow-hidden">
                <div class="overflow-y-auto flex-1 p-5 grid grid-cols-1 lg:grid-cols-2 gap-6 divide-y lg:divide-y-0 lg:divide-x lg:divide-slate-200 dark:lg:divide-slate-700">
                    
                    {{-- Left side: Product Specifications --}}
                    <div class="space-y-4">
                        <h4 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest pb-1 border-b border-slate-200/60 dark:border-slate-700/50">Product Specifications</h4>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Model Name <span class="text-rose-500">*</span></label>
                                <input type="text" x-model="productForm.model_name" required
                                       class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs focus:outline-none focus:border-blue-500 text-slate-800 dark:text-slate-100">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Part Number <span class="text-rose-500">*</span></label>
                                <input type="text" x-model="productForm.customer_part_no" required
                                       class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs focus:outline-none focus:border-blue-500 text-slate-800 dark:text-slate-100">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Part Name <span class="text-rose-500">*</span></label>
                                <input type="text" x-model="productForm.customer_part_name" required
                                       class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs focus:outline-none focus:border-blue-500 text-slate-800 dark:text-slate-100">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Part Category</label>
                                <input type="text" x-model="productForm.part_category"
                                       class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs focus:outline-none focus:border-blue-500 text-slate-800 dark:text-slate-100">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Destination</label>
                                <input type="text" x-model="productForm.destination"
                                       class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs focus:outline-none focus:border-blue-500 text-slate-800 dark:text-slate-100">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Annual Volume</label>
                                <input type="number" x-model="productForm.annual_volume" min="0"
                                       class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs focus:outline-none focus:border-blue-500 text-slate-800 dark:text-slate-100">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">SOP Date</label>
                                <input type="date" x-model="productForm.sop_date"
                                       class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs focus:outline-none focus:border-blue-500 text-slate-800 dark:text-slate-100">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">EOL Date</label>
                                <input type="date" x-model="productForm.eol_date"
                                       class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs focus:outline-none focus:border-blue-500 text-slate-800 dark:text-slate-100">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Model Life (months)</label>
                                <input type="number" x-model="productForm.model_life" min="0"
                                       class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs focus:outline-none focus:border-blue-500 text-slate-800 dark:text-slate-100">
                            </div>
                        </div>

                        {{-- Available Data check --}}
                        <div class="space-y-1.5 pt-1">
                            <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Available Data</span>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-200 cursor-pointer">
                                    <input type="checkbox" x-model="productForm.has_2d_data" class="border-slate-300 text-blue-600 focus:ring-blue-500">
                                    2D Drawing
                                </label>
                                <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-200 cursor-pointer">
                                    <input type="checkbox" x-model="productForm.has_3d_data" class="border-slate-300 text-blue-600 focus:ring-blue-500">
                                    3D Model
                                </label>
                                <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-200 cursor-pointer">
                                    <input type="checkbox" x-model="productForm.has_tech_doc" class="border-slate-300 text-blue-600 focus:ring-blue-500">
                                    Technical Document
                                </label>
                            </div>
                        </div>

                        {{-- Specs Remarks --}}
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Product Remarks</label>
                            <textarea x-model="productForm.remarks" rows="2"
                                      placeholder="Notes regarding product properties..."
                                      class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs focus:outline-none focus:border-blue-500 text-slate-800 dark:text-slate-100 resize-none"></textarea>
                        </div>
                    </div>
                    
                    {{-- Right side: Feasibility Scoring --}}
                    <div class="lg:pl-6 space-y-4 pt-4 lg:pt-0">
                        <h4 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest pb-1 border-b border-slate-200/60 dark:border-slate-700/50">Feasibility Scoring</h4>
                        
                        <div class="space-y-3.5">
                            @foreach($scoreCategories as $cat)
                                <div class="space-y-1.5">
                                    <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ $cat->category_name }}</label>
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($cat->options as $opt)
                                            <button type="button"
                                                    @click="assessmentForm.selections[{{ $cat->category_id }}] = {{ $opt->option_id }}; calculateActiveScore()"
                                                    :class="assessmentForm.selections[{{ $cat->category_id }}] == {{ $opt->option_id }} 
                                                        ? 'border-blue-600 dark:border-blue-500 bg-blue-50/80 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400 font-bold ring-2 ring-blue-500/20' 
                                                        : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400'"
                                                    class="flex-1 px-2.5 py-1.5 border text-[11px] text-center transition-all hover:bg-slate-50 dark:hover:bg-slate-700 focus:outline-none flex justify-between sm:justify-center items-center gap-1 shadow-sm"
                                                    {{ $isLocked ? 'disabled' : '' }}>
                                                <span>{{ $opt->option_name }}</span>
                                                <span class="text-[9px] font-mono opacity-80">(+{{ $opt->score_value }})</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Scoring Remarks --}}
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Scoring Remarks &amp; Recommendations</label>
                            <textarea
                                x-model="assessmentForm.remarks"
                                rows="2"
                                placeholder="Notes regarding feasibility scoring decisions…"
                                class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-blue-500 transition-colors resize-none shadow-sm"
                                {{ $isLocked ? 'disabled' : '' }}></textarea>
                        </div>

                        {{-- Score Summary Card --}}
                        <div class="bg-blue-600 dark:bg-blue-700 text-white p-3 shadow-md flex flex-col justify-between space-y-2">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-[9px] font-bold uppercase tracking-widest opacity-80">Current Score</div>
                                    <div class="text-2xl font-extrabold mt-0.5">
                                        <span x-text="activeScore">0</span><span class="text-xs font-medium opacity-60">/100</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-[9px] font-bold uppercase tracking-widest opacity-80">Rank</div>
                                    <div class="text-xl font-extrabold mt-0.5 bg-white/10 px-2 py-0.5 flex items-center justify-center min-w-[36px]" x-text="activeRankCode">
                                        —
                                    </div>
                                </div>
                            </div>
                            <div class="border-t border-white/20 pt-1.5 flex items-center gap-1.5">
                                <i class="fa-solid fa-triangle-exclamation text-[10px] text-blue-200"></i>
                                <div class="text-[9px] font-semibold text-blue-100 uppercase tracking-wide truncate">
                                    Recommendation: <span class="text-white font-bold" x-text="activeRankLabel">—</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                
                {{-- Footer --}}
                <div class="flex justify-end gap-2 px-5 py-3.5 border-t border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/30 flex-shrink-0">
                    <button type="button" @click="showProductModal = false"
                            class="px-4 py-2 text-xs font-semibold border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white transition-colors">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Import Excel Modal ──────────────────────────────────────────── --}}
    <div x-show="showImportModal"
         class="fixed inset-0 z-[8000] flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4"
         style="display:none;" x-cloak>
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 w-full max-w-md shadow-2xl">
            {{-- Modal Header --}}
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40">
                <h3 class="text-sm font-bold text-slate-800 dark:text-white">Import Products from Excel</h3>
                <button @click="showImportModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg leading-none">&times;</button>
            </div>
            {{-- Modal Body --}}
            <form action="{{ route('management.inquiry.import', $inquiry->inquiry_id) }}" method="POST" enctype="multipart/form-data" class="p-5 space-y-4">
                @csrf
                <div class="space-y-3">
                    <p class="text-xs text-slate-600 dark:text-slate-400">
                        Upload your spreadsheet (.xlsx, .xls) containing product information to import them directly into this inquiry.
                    </p>
                    <div class="p-4 bg-blue-50 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/30 text-xs">
                        <a href="{{ route('management.inquiry.download-template') }}" 
                           class="text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1.5 font-semibold">
                            <i class="fa-solid fa-download"></i> Download Excel Import Template
                        </a>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Choose File <span class="text-rose-500">*</span></label>
                        <input type="file" name="excel_file" required accept=".xlsx,.xls"
                               class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-xs focus:outline-none focus:border-blue-500 text-slate-700 dark:text-slate-300">
                    </div>
                    {{-- Append mode toggle --}}
                    <div class="flex items-start gap-3 p-3 bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/30 rounded-xs">
                        <input type="checkbox" name="append" value="true" id="import_append_mode"
                               class="mt-0.5 w-3.5 h-3.5 rounded-xs border-slate-300 accent-blue-600 cursor-pointer flex-shrink-0">
                        <div>
                            <label for="import_append_mode" class="text-xs font-bold text-amber-800 dark:text-amber-300 cursor-pointer">Append mode (add to existing products)</label>
                            <p class="text-[10px] text-amber-700 dark:text-amber-400 mt-0.5">By default, importing will <strong>replace all existing products</strong>. Check this to <strong>add</strong> the imported products to the existing list instead.</p>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-700">
                    <button type="button" @click="showImportModal = false"
                            class="px-4 py-2 text-xs font-semibold border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white transition-colors">
                        Import Data
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>{{-- end x-data --}}

{{-- ═══════════════════ SCRIPTS ════════════════════════════════════════════ --}}
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('inquiryManagement', () => ({
        showEditModal: false,
        showImportModal: false,
        editForm: {
            id: '{{ $inquiry->inquiry_id }}',
            customer_name: @json($inquiry->customer_name),
            project_name:  @json($inquiry->project_name),
            inquiry_date:  '{{ $inquiry->inquiry_date->format('Y-m-d') }}',
            remarks:       @json($inquiry->remarks)
        },
        showProductModal: false,
        productModalMode: 'add',
        productId: null,
        productForm: {
            model_name: '', customer_part_no: '', customer_part_name: '',
            part_category: '', destination: '', sop_date: '', eol_date: '',
            model_life: '', annual_volume: '',
            has_2d_data: false, has_3d_data: false, has_tech_doc: false, remarks: ''
        },
        loading: false,

        products: @json($inquiry->products),
        selectedSpkProducts: [],
        showAssessmentDrawer: false,
        activeProduct: null,
        assessmentForm: {
            selections: {},
            remarks: ''
        },
        activeScore: 0,
        activeRankCode: '—',
        activeRankLabel: '—',
        optionScores: @json($optionScores),
        rankings: @json($rankings),
        selectedOptions: @json($selectedOptionsByProduct),

        openAssessmentDrawer(prodId) {
            let prod = this.products.find(p => p.inquiry_product_id == prodId);
            this.activeProduct = prod;
            this.assessmentForm.remarks = (prod.assessment && prod.assessment.remarks) ? prod.assessment.remarks : '';
            this.assessmentForm.selections = {};
            if (this.selectedOptions[prodId]) {
                this.assessmentForm.selections = { ...this.selectedOptions[prodId] };
            }
            this.calculateActiveScore();
            this.showAssessmentDrawer = true;
        },

        calculateActiveScore() {
            let score = 0;
            Object.values(this.assessmentForm.selections).forEach(id => {
                if (id && this.optionScores[id] !== undefined) {
                    score += parseInt(this.optionScores[id]);
                }
            });
            this.activeScore = score;
            this.activeRankCode = '—';
            this.activeRankLabel = '—';
            for (let rank of this.rankings) {
                if (score >= rank.min_score && score <= rank.max_score) {
                    this.activeRankCode = rank.rank_code;
                    this.activeRankLabel = rank.priority_label;
                    break;
                }
            }
        },

        openAddProduct() {
            this.productModalMode = 'add';
            this.productId = null;
            this.productForm = {
                model_name: '', customer_part_no: '', customer_part_name: '',
                part_category: '', destination: '', sop_date: '', eol_date: '',
                model_life: '', annual_volume: '',
                has_2d_data: false, has_3d_data: false, has_tech_doc: false, remarks: ''
            };
            this.assessmentForm.selections = {};
            this.assessmentForm.remarks = '';
            this.calculateActiveScore();
            this.showProductModal = true;
        },

        openEditProduct(prodId) {
            let prod = this.products.find(p => p.inquiry_product_id == prodId);
            this.productModalMode = 'edit';
            this.productId = prod.inquiry_product_id;
            this.productForm = {
                model_name:          prod.model_name || '',
                customer_part_no:    prod.customer_part_no || '',
                customer_part_name:  prod.customer_part_name || '',
                part_category:       prod.part_category || '',
                destination:         prod.destination || '',
                sop_date:            prod.sop_date ? prod.sop_date.substring(0,10) : '',
                eol_date:            prod.eol_date ? prod.eol_date.substring(0,10) : '',
                model_life:          prod.model_life || '',
                annual_volume:       prod.annual_volume || '',
                has_2d_data:         !!prod.has_2d_data,
                has_3d_data:         !!prod.has_3d_data,
                has_tech_doc:        !!prod.has_tech_doc,
                remarks:             prod.remarks || ''
            };
            this.assessmentForm.remarks = (prod.assessment && prod.assessment.remarks) ? prod.assessment.remarks : '';
            this.assessmentForm.selections = {};
            if (this.selectedOptions[prodId]) {
                this.assessmentForm.selections = { ...this.selectedOptions[prodId] };
            }
            this.calculateActiveScore();
            this.showProductModal = true;
        },

        submitProductForm() {
            this.loading = true;
            let url    = this.productModalMode === 'add'
                ? `/management/inquiry/{{ $inquiry->inquiry_id }}/product`
                : `/management/inquiry-product/${this.productId}`;
            let method = this.productModalMode === 'add' ? 'POST' : 'PATCH';

            fetch(url, {
                method,
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}', 'Accept':'application/json' },
                body: JSON.stringify(this.productForm)
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    this.loading = false;
                    showToast(data.message || 'Save failed.', 'error');
                    return;
                }
                
                // Get the product ID for scoring save
                let prodId = this.productId || (data.product ? data.product.inquiry_product_id : null);
                
                if (prodId) {
                    let selections = [];
                    Object.values(this.assessmentForm.selections).forEach(id => {
                        if (id) selections.push(parseInt(id));
                    });
                    
                    fetch(`/management/inquiry-product/${prodId}/assess`, {
                        method: 'POST',
                        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}', 'Accept':'application/json' },
                        body: JSON.stringify({
                            selections: selections,
                            remarks: this.assessmentForm.remarks
                        })
                    })
                    .then(r2 => r2.json())
                    .then(data2 => {
                        this.loading = false;
                        if (data2.success) {
                            showToast('Product and scoring saved successfully!');
                            this.showProductModal = false;
                            setTimeout(() => window.location.reload(), 600);
                        } else {
                            showToast(data2.message || 'Product updated, but scoring failed to save.', 'warning');
                        }
                    })
                    .catch(() => {
                        this.loading = false;
                        showToast('Network error while saving scoring.', 'error');
                    });
                } else {
                    this.loading = false;
                    window.location.reload();
                }
            })
            .catch(() => { this.loading = false; showToast('Network error.', 'error'); });
        },

        reorder(prodId, direction) {
            this.loading = true;
            fetch(`/management/inquiry-product/${prodId}/reorder`, {
                method: 'POST',
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}', 'Accept':'application/json' },
                body: JSON.stringify({ direction })
            })
            .then(r => r.json())
            .then(data => {
                this.loading = false;
                if (data.success) { window.location.reload(); }
                else { showToast(data.message || 'Reorder failed.', 'error'); }
            })
            .catch(() => { this.loading = false; showToast('Network error.', 'error'); });
        },

        submitEdit() {
            this.loading = true;
            fetch(`/management/inquiry/${this.editForm.id}`, {
                method: 'PATCH',
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}', 'Accept':'application/json' },
                body: JSON.stringify({
                    customer_name: this.editForm.customer_name,
                    project_name:  this.editForm.project_name,
                    inquiry_date: this.editForm.inquiry_date,
                    remarks:       this.editForm.remarks
                })
            })
            .then(r => r.json())
            .then(data => {
                this.loading = false;
                if (data.success) { window.location.reload(); }
                else { showToast(data.message || 'Update failed.', 'error'); }
            })
            .catch(() => { this.loading = false; showToast('Network error.', 'error'); });
        },

        isProductSelectedForSpk(prodId) {
            return this.selectedSpkProducts.includes(prodId);
        },

        toggleSpkSelection(prodId, isChecked) {
            if (isChecked) {
                if (!this.selectedSpkProducts.includes(prodId)) {
                    this.selectedSpkProducts.push(prodId);
                }
            } else {
                this.selectedSpkProducts = this.selectedSpkProducts.filter(id => id !== prodId);
            }
        },

        toggleSelectAllSpk(isChecked) {
            if (isChecked) {
                this.selectedSpkProducts = this.products.map(p => p.inquiry_product_id);
            } else {
                this.selectedSpkProducts = [];
            }
        }
    }));
});

// ── Toast Notification ──────────────────────────────────────────────────────
let toastTimer;
function showToast(message, type = 'success') {
    const toast = document.getElementById('erp-toast');
    const icon  = document.getElementById('erp-toast-icon');
    const msg   = document.getElementById('erp-toast-msg');

    icon.className = type === 'success'
        ? 'fa-solid fa-circle-check text-emerald-500 text-base'
        : 'fa-solid fa-circle-xmark text-rose-500 text-base';
    msg.textContent = message;

    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('show'), 3000);
}
</script>

@endsection
