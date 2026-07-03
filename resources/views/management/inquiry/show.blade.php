@extends('layouts.app')

@section('title', 'Inquiry — ' . $inquiry->inquiry_no . ' · Promise Management')

@section('content')
<x-sweetalert />
@php
    $selectedOptionsByProduct = [];
    foreach($inquiry->products as $p) {
        if ($p->assessment && $p->assessment->details) {
            foreach($p->assessment->details as $d) {
                $selectedOptionsByProduct[$p->id][$d->category_id] = $d->option_id;
            }
        }
    }
    $isLocked = in_array($inquiry->status, ['Closed', 'Cancelled']);

    $optionScores = [];
    foreach($scoreCategories as $cat) {
        foreach($cat->options as $opt) {
            $optionScores[$opt->id] = $opt->score_value;
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
                    <a :href="`{{ Route::has('management.work-order.create') ? route('management.work-order.create') : '#' }}?inquiry_id={{ $inquiry->hashed_id }}&` + selectedSpkProducts.map(id => `products[]=${products.find(p => p.id === id)?.hashed_id || id}`).join('&')"
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
                    <button @click="showAssessmentConfigModal = true"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs font-semibold shadow-sm transition-colors cursor-pointer">
                        <i class="fa-solid fa-gears text-[10px]"></i> Scoring Config
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
                    <form id="close-inquiry-form" method="POST" action="{{ route('management.inquiry.close', $inquiry->hashed_id) }}"
                          class="inline">
                        @csrf
                        <button type="button" onclick="confirmCloseInquiry()"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-sm transition-colors cursor-pointer">
                            <i class="fa-solid fa-lock text-[10px]"></i> Close Inquiry
                        </button>
                    </form>
                    <form id="cancel-inquiry-form" method="POST" action="{{ route('management.inquiry.cancel', $inquiry->hashed_id) }}"
                          class="inline">
                        @csrf
                        <button type="button" onclick="confirmCancelInquiry()"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-sm transition-colors cursor-pointer">
                            <i class="fa-solid fa-ban text-[10px]"></i> Cancel
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Card Body: Inquiry Details Grid --}}
        <div class="grid grid-cols-2 lg:grid-cols-5 divide-x divide-y lg:divide-y-0 divide-slate-100 dark:divide-slate-700/60">
            <div class="px-4 py-3">
                <span class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-0.5">Customer</span>
                <span class="text-xs font-bold text-slate-800 dark:text-slate-100">{{ $inquiry->customer->name ?? '—' }}</span>
            </div>
            <div class="px-4 py-3">
                <span class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-0.5">Model</span>
                <span class="text-xs font-bold text-slate-800 dark:text-slate-100">{{ $inquiry->model_name ?? '—' }}</span>
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

    {{-- Pass initial data for JS rendering (hybrid approach) --}}
    @php
        $productsForJs = $inquiry->products->map(function($p) {
            $score = $p->assessment->total_score ?? 0;
            $scoreClass = $score === 0 ? 'none' : ($score >= 70 ? 'high' : ($score >= 40 ? 'mid' : 'low'));
            $wos = $p->workOrderProducts()
                    ->whereHas('workOrder', fn($q) => $q->whereNull('deleted_at')->where('status', '!=', 'Rejected'))
                    ->with('workOrder')->get();
            $spkList = $wos->map(function($wo) {
                return [
                    'number' => $wo->workOrder->wo_number,
                    'status' => $wo->workOrder->status
                ];
            })->toArray();
            return [
                'id'                 => $p->id,
                'hashed_id'          => $p->hashed_id,
                'model_name'         => $p->model_name,
                'customer_part_no'   => $p->customer_part_no,
                'customer_part_name' => $p->customer_part_name,
                'variant'            => $p->variant,
                'part_category'      => $p->part_category,
                'annual_volume'      => $p->annual_volume,
                'score'              => $score,
                'scoreClass'         => $scoreClass,
                'rankCode'           => $p->assessment->ranking->rank_code ?? null,
                'rankLabel'          => $p->assessment->ranking->priority_label ?? null,
                'spkList'            => $spkList,
            ];
        });
    @endphp
    <script>window.__inquiryProducts = @json($productsForJs);</script>

    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 shadow-sm mt-4">

        {{-- Table toolbar --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 px-4 py-3 border-b border-slate-200 dark:border-slate-700 bg-gradient-to-r from-slate-50 to-white dark:from-slate-900/60 dark:to-slate-800">
            <div class="flex items-center gap-3 flex-wrap">
                <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Products &amp; Feasibility Scoring</span>
                <span class="inline-flex items-center px-2 py-0.5 bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 text-[9px] font-bold border border-blue-100 dark:border-blue-900/40"
                      x-text="`${products.length} items`">{{ $inquiry->products->count() }} items</span>
                {{-- Unsaved indicator --}}
                <span x-show="orderDirty" x-cloak
                      class="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-50 dark:bg-amber-950/30 text-amber-600 dark:text-amber-400 text-[9px] font-bold border border-amber-200 dark:border-amber-900/40 animate-pulse">
                    <i class="fa-solid fa-circle-exclamation"></i> Unsaved order changes
                </span>
            </div>
            {{-- Search Input --}}
            <div class="w-full sm:w-64">
                <input type="text" x-model="searchQuery" @input="renderRows()" placeholder="Search records..."
                       class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 transition-colors">
            </div>
        </div>

        {{-- Scrollable table container with padding from card --}}
        <div class="p-4">
            <div class="overflow-x-auto w-full border border-slate-200 dark:border-slate-700/80">
                <div id="products-scroll-container" class="max-h-[480px] overflow-y-auto" style="position:relative;">
                    <table class="w-full text-left border-collapse text-xs" style="min-width:900px">
                        {{-- Sticky Table Head --}}
                        <thead class="sticky top-0 z-10">
                            <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400 text-xs font-semibold uppercase tracking-wider">
                                <th class="p-3 w-12 text-center bg-slate-50/50 dark:bg-slate-900/50">#</th>
                                <th class="p-3 w-20 text-center bg-slate-50/50 dark:bg-slate-900/50">
                                    <input type="checkbox" id="select-all-spk"
                                           onchange="window._alpine_toggleSelectAllSpk(this.checked)"
                                           class="w-3.5 h-3.5 text-blue-600 border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-blue-500 cursor-pointer rounded-xs"
                                           title="Select/Deselect All Products for SPK">
                                </th>
                                <th class="p-3 bg-slate-50/50 dark:bg-slate-900/50">Model</th>
                                <th class="p-3 bg-slate-50/50 dark:bg-slate-900/50">Part Number</th>
                                <th class="p-3 bg-slate-50/50 dark:bg-slate-900/50">Part Name</th>
                                <th class="p-3 text-center bg-slate-50/50 dark:bg-slate-900/50">Variant</th>
                                <th class="p-3 bg-slate-50/50 dark:bg-slate-900/50">Category</th>
                                <th class="p-3 text-right bg-slate-50/50 dark:bg-slate-900/50">Ann. Vol.</th>
                                <th class="p-3 text-center w-20 bg-slate-50/50 dark:bg-slate-900/50">Score</th>
                                <th class="p-3 text-center w-16 bg-slate-50/50 dark:bg-slate-900/50">Rank</th>
                                <th class="p-3 text-center w-32 bg-slate-50/50 dark:bg-slate-900/50">Priority</th>
                                <th class="p-3 text-right w-24 bg-slate-50/50 dark:bg-slate-900/50">Actions</th>
                            </tr>
                        </thead>
                        {{-- tbody is rendered & managed by JS renderRows() --}}
                        <tbody id="products-tbody">
                            {{-- JS-rendered rows go here --}}
                        </tbody>
                    </table>
                    {{-- Drop indicator line --}}
                    <div id="drop-indicator" style="display:none;position:absolute;left:0;right:0;height:2px;background:#2563eb;pointer-events:none;z-index:20;"></div>
                </div>
            </div>
        </div>

        {{-- Table Footer --}}
        <div class="px-4 py-2.5 border-t border-slate-100 dark:border-slate-700/60 bg-gradient-to-r from-slate-50 to-white dark:from-slate-900/60 dark:to-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="text-[10px] text-slate-400">
                    <span x-text="products.length">{{ $inquiry->products->count() }}</span> products listed
                    &nbsp;·&nbsp; Click row to edit &nbsp;·&nbsp; Drag grip to reorder
                </span>
            </div>
            <div class="flex items-center gap-2">
                @if($isLocked)
                    <span class="inline-flex items-center gap-1.5 text-[10px] font-semibold text-amber-600 dark:text-amber-400">
                        <i class="fa-solid fa-lock text-[9px]"></i> Inquiry {{ strtolower($inquiry->status) }} — read-only
                    </span>
                @else
                    <button x-show="orderDirty" x-cloak
                            @click="saveOrder()"
                            :disabled="savingOrder"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 disabled:bg-emerald-400 text-white text-[10px] font-bold transition-colors shadow-sm cursor-pointer">
                        <template x-if="!savingOrder">
                            <span class="flex items-center gap-1.5">
                                <i class="fa-solid fa-floppy-disk"></i> Save Order
                            </span>
                        </template>
                        <template x-if="savingOrder">
                            <span class="flex items-center gap-1.5">
                                <i class="fa-solid fa-circle-notch fa-spin"></i> Saving…
                            </span>
                        </template>
                    </button>
                    <button x-show="orderDirty" x-cloak
                            @click="discardOrder()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 text-[10px] font-semibold transition-colors cursor-pointer">
                        <i class="fa-solid fa-rotate-left text-[9px]"></i> Reset
                    </button>
                @endif
            </div>
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
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Customer <span class="text-rose-500">*</span></label>
                        <select x-model="editForm.customer_id" required @change="editForm.project_id = ''"
                                class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500">
                            <option value="">Select Customer</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Project Name (Model) <span class="text-rose-500">*</span></label>
                        <select x-model="editForm.project_id" required :disabled="!editForm.customer_id"
                                class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 disabled:opacity-50">
                            <option value="">Select Model</option>
                            <template x-for="m in getFilteredModels(editForm.customer_id)" :key="m.id">
                                <option :value="m.id" x-text="m.name" :selected="m.id == editForm.project_id"></option>
                            </template>
                        </select>
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
                                <input type="text" x-model="productForm.model_name" required readonly
                                       class="w-full bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs text-slate-500 dark:text-slate-400 cursor-not-allowed focus:outline-none">
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
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Variant (Steering Position)</label>
                                <select x-model="productForm.variant"
                                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs focus:outline-none focus:border-blue-500 text-slate-800 dark:text-slate-100">
                                    <option value="">Select Variant</option>
                                    <option value="RHD">RHD</option>
                                    <option value="LHD">LHD</option>
                                    <option value="RHD & LHD">RHD & LHD</option>
                                </select>
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
                                                    @click="assessmentForm.selections[{{ $cat->id }}] = {{ $opt->id }}; calculateActiveScore()"
                                                    :class="assessmentForm.selections[{{ $cat->id }}] == {{ $opt->id }} 
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
            <form action="{{ route('management.inquiry.import', $inquiry->hashed_id) }}" method="POST" enctype="multipart/form-data" class="p-5 space-y-4">
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
                    {{-- Overwrite mode toggle --}}
                    <div class="flex items-start gap-3 p-3 bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/30 rounded-xs">
                        <input type="checkbox" name="append" value="true" id="import_append_mode"
                               class="mt-0.5 w-3.5 h-3.5 rounded-xs border-slate-300 accent-blue-600 cursor-pointer flex-shrink-0">
                        <div>
                            <label for="import_append_mode" class="text-xs font-bold text-amber-800 dark:text-amber-300 cursor-pointer">Overwrite mode (Centang untuk menghapus data lama)</label>
                            <p class="text-[10px] text-amber-700 dark:text-amber-400 mt-0.5">Jika dicentang, proses import akan **menghapus semua produk lama** pada inquiry ini sebelum mengisi dengan yang baru. Jika tidak dicentang, data baru akan **ditambahkan** ke daftar produk yang ada.</p>
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

    <!-- Assessment Configuration Modal -->
    @include('management.inquiry.assessment-config-modal')

</div>{{-- end x-data --}}

{{-- ═══════════════════ SCRIPTS ════════════════════════════════════════════ --}}
<style>
/* ── Table row drag & drop styles ── */
#products-tbody tr.drag-over-top { box-shadow: 0 -2px 0 0 #2563eb inset; }
#products-tbody tr.drag-over-bottom { box-shadow: 0 2px 0 0 #2563eb inset; }
#products-tbody tr.dragging { opacity: 0.35; background: #eff6ff; }
.dark #products-tbody tr.dragging { background: #1e3a5f30; }
#products-tbody tr.drag-placeholder { background: #dbeafe !important; }
.dark #products-tbody tr.drag-placeholder { background: #1e3a5f40 !important; }

/* Score badge micro-animation */
@keyframes score-pop { 0%,100%{transform:scale(1)} 50%{transform:scale(1.12)} }
.score-pop { animation: score-pop .2s ease; }

/* Unsaved indicator pulse */
@keyframes dirty-pulse { 0%,100%{opacity:1} 50%{opacity:0.6} }
.dirty-pulse { animation: dirty-pulse 1.6s ease infinite; }
</style>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('inquiryManagement', () => ({
        showEditModal: false,
        showImportModal: false,
        editForm: {
            id: '{{ $inquiry->id }}',
            customer_id: '{{ $inquiry->customer_id }}',
            project_id:  '{{ $inquiry->project_id }}',
            inquiry_date:  '{{ $inquiry->inquiry_date->format('Y-m-d') }}',
            remarks:       @json($inquiry->remarks)
        },
        getFilteredModels(customerId) {
            const allModels = @json($models);
            return allModels.filter(m => m.customer_id == customerId);
        },
        showAssessmentConfigModal: false,
        showProductModal: false,
        productModalMode: 'add',
        productId: null,
        productForm: {
            model_name: '', customer_part_no: '', customer_part_name: '',
            part_category: '', destination: '', sop_date: '', eol_date: '',
            model_life: '', annual_volume: '',
            has_2d_data: false, has_3d_data: false, has_tech_doc: false, variant: '', remarks: ''
        },
        loading: false,

        searchQuery: '',
        products: [],
        _initialProducts: [],
        orderDirty: false,
        savingOrder: false,
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

        init() {
            // Bootstrap products from server-injected global variable
            if (window.__inquiryProducts) {
                this.products = window.__inquiryProducts;
                this._initialProducts = JSON.parse(JSON.stringify(this.products));
            }
            this.$nextTick(() => this.renderRows());
        },

        // ── Render table rows from this.products array (client-side) ── //
        renderRows() {
            const tbody = document.getElementById('products-tbody');
            if (!tbody) return;
            const isLocked = {{ $isLocked ? 'true' : 'false' }};
            
            // Filter products based on search query
            let filteredProducts = this.products;
            if (this.searchQuery) {
                const q = this.searchQuery.toLowerCase().trim();
                filteredProducts = this.products.filter(p => 
                    (p.model_name && p.model_name.toLowerCase().includes(q)) ||
                    (p.customer_part_no && p.customer_part_no.toLowerCase().includes(q)) ||
                    (p.customer_part_name && p.customer_part_name.toLowerCase().includes(q)) ||
                    (p.variant && p.variant.toLowerCase().includes(q)) ||
                    (p.part_category && p.part_category.toLowerCase().includes(q))
                );
            }

            const total = filteredProducts.length;
            if (total === 0) {
                const emptyMsg = this.searchQuery 
                    ? 'No records found matching your search.'
                    : 'No products have been added yet.';
                tbody.innerHTML = `<tr><td colspan="12" class="py-16 text-center">
                    <i class="fa-solid fa-folder-open text-4xl text-slate-300 block mb-3"></i>
                    <p class="text-sm font-semibold text-slate-400">${emptyMsg}</p>
                </td></tr>`;
                return;
            }

            const scoreColors = {
                high: 'score-high', mid: 'score-mid', low: 'score-low', none: 'score-none'
            };
            const priorityColors = {
                'Review Now':  'bg-rose-50 text-rose-700 border-rose-300',
                'Review Next': 'bg-amber-50 text-amber-700 border-amber-300',
                'Pending':     'bg-blue-50 text-blue-700 border-blue-300',
            };

            const rows = filteredProducts.map((prod, index) => {
                const sc = scoreColors[prod.scoreClass] || 'score-none';
                const pc = priorityColors[prod.rankLabel] || 'bg-slate-50 text-slate-500 border-slate-200';
                const isSelected = this.selectedSpkProducts.includes(prod.id);
                const checkedAttr = isSelected ? 'checked' : '';
                const draggable = !isLocked ? 'true' : 'false';
                const vol = prod.annual_volume ? prod.annual_volume.toLocaleString() : '—';
                
                const hasSpk = prod.spkList && prod.spkList.length > 0;
                const spkTooltip = hasSpk ? 'Associated SPKs:\n' + prod.spkList.map(s => `• ${s.number} (${s.status})`).join('\n') : '';
                const spkIcon = hasSpk 
                    ? `<i class="fa-solid fa-file-signature text-emerald-650 dark:text-emerald-450 text-xs ml-1 cursor-pointer" title="${spkTooltip}"></i>`
                    : '';

                const upBtn = (index > 0 && !isLocked)
                    ? `<button onclick="event.stopPropagation();window._alpine_reorder(${prod.id},'up')" title="Move Up" class="w-6 h-6 flex items-center justify-center bg-slate-100 hover:bg-slate-250 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-350 transition-colors"><i class="fa-solid fa-arrow-up text-[9px]"></i></button>` : '';
                const downBtn = (index < total - 1 && !isLocked)
                    ? `<button onclick="event.stopPropagation();window._alpine_reorder(${prod.id},'down')" title="Move Down" class="w-6 h-6 flex items-center justify-center bg-slate-100 hover:bg-slate-250 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-350 transition-colors"><i class="fa-solid fa-arrow-down text-[9px]"></i></button>` : '';
                const editBtn = !isLocked
                    ? `<button onclick="event.stopPropagation();window._alpine_editProduct(${prod.id})" title="Edit" class="w-6 h-6 flex items-center justify-center bg-slate-100 hover:bg-blue-50 dark:bg-slate-800 dark:hover:bg-blue-950/30 border border-slate-300 dark:border-slate-700 hover:border-blue-300 dark:hover:border-blue-800 text-slate-600 dark:text-slate-350 hover:text-blue-700 dark:hover:text-blue-400 transition-colors"><i class="fa-solid fa-pen text-[9px]"></i></button>`
                    : `<span class="text-[10px] text-slate-400 italic">Locked</span>`;
                const deleteBtn = !isLocked
                    ? `<button onclick="event.stopPropagation();window._alpine_deleteProduct(${prod.id})" title="Delete" class="w-6 h-6 flex items-center justify-center bg-slate-100 hover:bg-rose-50 dark:bg-slate-800 dark:hover:bg-rose-950/30 border border-slate-300 dark:border-slate-700 hover:border-rose-300 dark:hover:border-rose-800 text-slate-600 dark:text-slate-350 hover:text-rose-600 dark:hover:text-rose-400 transition-colors"><i class="fa-solid fa-trash text-[9px]"></i></button>`
                    : '';

                // Background calculation: blue highlight when selected, otherwise alternate zebra striping
                const rowBg = isSelected 
                    ? 'bg-blue-50/70 dark:bg-blue-950/20 text-blue-900 dark:text-blue-100'
                    : (index % 2 === 0 ? 'bg-white dark:bg-slate-800' : 'bg-slate-100/40 dark:bg-slate-900/30');

                const trClass = `border-b border-slate-200 dark:border-slate-700/80 ${rowBg} hover:bg-blue-50/30 dark:hover:bg-slate-750 transition-colors duration-150 ${!isLocked ? 'cursor-move' : ''}`;

                return `<tr id="row-${prod.id}" class="${trClass}" draggable="${draggable}"
                    data-id="${prod.id}"
                    onclick="if(!event.defaultPrevented && !${isLocked})window._alpine_editProduct(${prod.id})">
                  <td class="p-3 text-center text-slate-450 dark:text-slate-500 font-mono text-[10px]" onclick="event.stopPropagation()">
                    <span>${index + 1}</span>
                  </td>
                  <td class="p-3 text-center" onclick="event.stopPropagation()">
                    <div class="flex items-center justify-center gap-2">
                      <i class="fa-solid fa-grip-vertical text-slate-400 dark:text-slate-550 cursor-grab active:cursor-grabbing hover:text-slate-650 dark:hover:text-slate-350 text-xs ${!isLocked?'':'opacity-25'}" title="${!isLocked?'Drag to reorder':''}"></i>
                      <input type="checkbox" ${checkedAttr}
                        onchange="event.stopPropagation();window._alpine_toggleSpk(${prod.id}, this.checked)"
                        class="w-3.5 h-3.5 text-blue-600 border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 focus:ring-blue-500 cursor-pointer rounded-xs">
                      ${spkIcon}
                    </div>
                  </td>
                  <td class="p-3 font-medium text-slate-600 dark:text-slate-100">
                    <span>${prod.model_name || '—'}</span>
                  </td>
                  <td class="p-3 text-slate-600 dark:text-slate-300 font-medium">${prod.customer_part_no || '—'}</td>
                  <td class="p-3 text-slate-600 dark:text-slate-300 max-w-[180px]" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="${prod.customer_part_name || ''}">${prod.customer_part_name || '—'}</td>
                  <td class="p-3 text-center font-medium text-slate-600 dark:text-slate-300">${prod.variant || '—'}</td>
                  <td class="p-3 text-slate-500 dark:text-slate-400">${prod.part_category || '—'}</td>
                  <td class="p-3 text-right font-mono text-slate-700 dark:text-slate-300">${vol}</td>
                  <td class="p-3 text-center">
                    <span id="score-badge-${prod.id}" class="score-badge ${sc}">${prod.score}</span>
                  </td>
                  <td class="p-3 text-center">
                    <span class="inline-block px-1.5 py-0.5 text-[10px] font-bold bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-455 border border-slate-200 dark:border-slate-700">${prod.rankCode || '—'}</span>
                  </td>
                  <td class="p-3 text-center">
                    <span class="inline-block px-2 py-0.5 text-[10px] font-bold border ${pc}">${prod.rankLabel || '—'}</span>
                  </td>
                  <td class="p-3 text-right" onclick="event.stopPropagation()">
                    <div class="inline-flex items-center gap-1.5 justify-end">${upBtn}${downBtn}${editBtn}${deleteBtn}</div>
                  </td>
                </tr>`;
            });

            tbody.innerHTML = rows.join('');

            // Sync master checkbox check state
            const selectAll = document.getElementById('select-all-spk');
            if (selectAll) {
                selectAll.checked = this.selectedSpkProducts.length === this.products.length && this.products.length > 0;
            }

            // Re-attach native drag events to newly rendered rows
            if (!isLocked) this._attachDragEvents();
        },

        _attachDragEvents() {
            const tbody = document.getElementById('products-tbody');
            if (!tbody) return;
            tbody.querySelectorAll('tr[draggable="true"]').forEach(tr => {
                tr.addEventListener('dragstart', (e) => this.dragStart(e, parseInt(tr.dataset.id)));
                tr.addEventListener('dragover',  (e) => { e.preventDefault(); this.dragOver(e, tr); });
                tr.addEventListener('dragleave', (e) => { tr.classList.remove('drag-over-top','drag-over-bottom'); });
                tr.addEventListener('drop',      (e) => { e.preventDefault(); this.dragDrop(e, parseInt(tr.dataset.id)); });
                tr.addEventListener('dragend',   (e) => this.dragEnd(e));
            });
        },

        openAssessmentDrawer(prodId) {
            let prod = this.products.find(p => p.id == prodId);
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
                model_name: '{{ $inquiry->projectModel->name ?? "" }}',
                customer_part_no: '', customer_part_name: '',
                part_category: '', destination: '', sop_date: '', eol_date: '',
                model_life: '', annual_volume: '',
                has_2d_data: false, has_3d_data: false, has_tech_doc: false, variant: '', remarks: ''
            };
            this.assessmentForm.selections = {};
            this.assessmentForm.remarks = '';
            this.calculateActiveScore();
            this.showProductModal = true;
        },

        openEditProduct(prodId) {
            this.loading = true;
            fetch('{{ url('management/inquiry-product') }}/' + prodId, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(r => r.json())
            .then(data => {
                this.loading = false;
                const prod = data.product || data;
                this.productModalMode = 'edit';
                this.productId = prod.id;
                this.productForm = {
                    model_name:          prod.model_name || '{{ $inquiry->projectModel->name ?? "" }}',
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
                    variant:             prod.variant || '',
                    remarks:             prod.remarks || ''
                };
                this.assessmentForm.remarks = (prod.assessment && prod.assessment.remarks) ? prod.assessment.remarks : '';
                this.assessmentForm.selections = {};
                if (this.selectedOptions[prodId]) {
                    this.assessmentForm.selections = { ...this.selectedOptions[prodId] };
                }
                this.calculateActiveScore();
                this.showProductModal = true;
            })
            .catch(() => { this.loading = false; showToast('Failed to load product data.', 'error'); });
        },

        submitProductForm() {
            this.loading = true;
            let url    = this.productModalMode === 'add'
                ? '{{ url('management/inquiry') }}/{{ $inquiry->hashed_id }}/product'
                : '{{ url('management/inquiry-product') }}/' + this.productId;
            
            let bodyData = { ...this.productForm };
            if (this.productModalMode !== 'add') {
                bodyData._method = 'PATCH';
            }

            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}', 'Accept':'application/json' },
                body: JSON.stringify(bodyData)
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    this.loading = false;
                    showToast(data.message || 'Save failed.', 'error');
                    return;
                }
                
                // Get the product ID for scoring save
                let prodId = this.productId || (data.product ? data.product.id : null);
                
                if (prodId) {
                    let selections = [];
                    Object.values(this.assessmentForm.selections).forEach(id => {
                        if (id) selections.push(parseInt(id));
                    });
                    
                    fetch('{{ url('management/inquiry-product') }}/' + prodId + '/assess', {
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

        // ── Drag & Drop ─────────────────────────────────────────────────── //
        draggedIds: null,
        _dragTargetId: null,
        _dragPos: null, // 'top' | 'bottom'

        dragStart(e, id) {
            if ({{ $isLocked ? 'true' : 'false' }}) return;
            this.draggedIds = this.selectedSpkProducts.includes(id)
                ? [...this.selectedSpkProducts]
                : [id];
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', JSON.stringify(this.draggedIds));
            this.$nextTick(() => {
                this.draggedIds.forEach(dragId => {
                    let el = document.getElementById('row-' + dragId);
                    if (el) el.classList.add('dragging');
                });
            });
        },

        dragOver(e, targetTr) {
            if (!this.draggedIds) return;
            e.dataTransfer.dropEffect = 'move';
            // Calculate drop position (top half = before, bottom half = after)
            const rect = targetTr.getBoundingClientRect();
            const mid  = rect.top + rect.height / 2;
            const pos  = e.clientY < mid ? 'top' : 'bottom';
            const id   = parseInt(targetTr.dataset.id);

            if (this._dragTargetId !== id || this._dragPos !== pos) {
                this._dragTargetId = id;
                this._dragPos      = pos;
                // Update drop indicator
                const indicator = document.getElementById('drop-indicator');
                const scroll    = document.getElementById('products-scroll-container');
                if (indicator && scroll) {
                    const scrollRect = scroll.getBoundingClientRect();
                    const top = (pos === 'top' ? rect.top : rect.bottom) - scrollRect.top + scroll.scrollTop;
                    indicator.style.display = 'block';
                    indicator.style.top     = top + 'px';
                }
                // Visual indicator on row
                document.querySelectorAll('#products-tbody tr').forEach(tr => {
                    tr.classList.remove('drag-over-top','drag-over-bottom');
                });
                targetTr.classList.add(pos === 'top' ? 'drag-over-top' : 'drag-over-bottom');
            }
        },

        dragDrop(e, targetId) {
            if ({{ $isLocked ? 'true' : 'false' }} || !this.draggedIds || this.draggedIds.includes(targetId)) {
                this._clearDragState();
                return;
            }

            const pos = this._dragPos || 'bottom';
            // Remove dragged items
            let draggedItems = [];
            this.draggedIds.forEach(dragId => {
                let idx = this.products.findIndex(p => p.id == dragId);
                if (idx > -1) draggedItems.push(this.products.splice(idx, 1)[0]);
            });

            // Find new insertion index
            let targetIndex = this.products.findIndex(p => p.id == targetId);
            if (targetIndex === -1) targetIndex = this.products.length;
            const insertAt = pos === 'bottom' ? targetIndex + 1 : targetIndex;
            this.products.splice(insertAt, 0, ...draggedItems);

            this.orderDirty = true;
            this._clearDragState();
            this.renderRows();
            showToast('Order updated. Click "Save Order" in the footer to apply changes.', 'info');
        },

        dragEnd(e) {
            this._clearDragState();
        },

        _clearDragState() {
            document.querySelectorAll('#products-tbody tr').forEach(tr => {
                tr.classList.remove('dragging','drag-over-top','drag-over-bottom');
            });
            const indicator = document.getElementById('drop-indicator');
            if (indicator) indicator.style.display = 'none';
            this.draggedIds      = null;
            this._dragTargetId  = null;
            this._dragPos       = null;
        },

        // ── Reorder buttons (up/down) — only updates in-memory ── //
        reorder(prodId, direction) {
            const idx = this.products.findIndex(p => p.id == prodId);
            if (idx === -1) return;
            const newIdx = direction === 'up' ? idx - 1 : idx + 1;
            if (newIdx < 0 || newIdx >= this.products.length) return;
            // Swap
            [this.products[idx], this.products[newIdx]] = [this.products[newIdx], this.products[idx]];
            this.orderDirty = true;
            this.renderRows();
        },

        // ── Save Order — sends reorder to server ── //
        saveOrder() {
            this.savingOrder = true;
            const orderedIds = this.products.map(p => p.id);
            fetch('{{ url('management/inquiry-product/reorder-all') }}', {
                method: 'POST',
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}', 'Accept':'application/json' },
                body: JSON.stringify({ ids: orderedIds })
            })
            .then(r => r.json())
            .then(data => {
                this.savingOrder = false;
                if (data.success) {
                    this.orderDirty = false;
                    this._initialProducts = JSON.parse(JSON.stringify(this.products));
                    showToast('Order saved successfully!');
                } else {
                    showToast(data.message || 'Failed to save order.', 'error');
                }
            })
            .catch(() => { this.savingOrder = false; showToast('Network error.', 'error'); });
        },

        discardOrder() {
            this.products = JSON.parse(JSON.stringify(this._initialProducts));
            this.orderDirty = false;
            this.renderRows();
            showToast('Order changes discarded.');
        },

        submitEdit() {
            this.loading = true;
            fetch('{{ url('management/inquiry') }}/' + this.editForm.id, {
                method: 'POST',
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}', 'Accept':'application/json' },
                body: JSON.stringify({
                    _method: 'PATCH',
                    customer_id: this.editForm.customer_id,
                    project_id:  this.editForm.project_id,
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
            // Sync master checkbox state
            const selectAll = document.getElementById('select-all-spk');
            if (selectAll) {
                selectAll.checked = this.selectedSpkProducts.length === this.products.length && this.products.length > 0;
            }
            this.renderRows();
        },

        toggleSelectAllSpk(isChecked) {
            if (isChecked) {
                this.selectedSpkProducts = this.products.map(p => p.id);
            } else {
                this.selectedSpkProducts = [];
            }
            this.renderRows();
        },

        deleteProduct(id) {
            window.confirmDialog({
                title: 'Delete Product?',
                text: 'Are you sure you want to remove this product from the inquiry?',
                icon: 'warning',
                confirmButtonColor: '#dc2626', // Rose 600
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'No',
                onConfirm: () => {
                    this.loading = true;
                    fetch('{{ url('management/inquiry-product') }}/' + id, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ _method: 'DELETE' })
                    })
                    .then(res => res.json())
                    .then(res => {
                        this.loading = false;
                        if (res.success) {
                            this.products = this.products.filter(p => p.id !== id);
                            this.selectedSpkProducts = this.selectedSpkProducts.filter(sid => sid !== id);
                            showToast(res.message || 'Product deleted.', 'success');
                            this.renderRows();
                        } else {
                            showToast(res.message || 'Failed to delete product.', 'error');
                        }
                    })
                    .catch(() => {
                        this.loading = false;
                        showToast('Network error while deleting product.', 'error');
                    });
                }
            });
        }
    }));
});

// ── Wire up global callbacks for inline-HTML onclick handlers ──
// Alpine.js v3: access component data via Alpine.$data on the root element
function _getInquiryData() {
    const el = document.querySelector('[x-data="inquiryManagement"]');
    return el ? Alpine.$data(el) : null;
}
window._alpine_editProduct = (id)         => { const d = _getInquiryData(); if (d) d.openEditProduct(id); };
window._alpine_reorder     = (id, dir)    => { const d = _getInquiryData(); if (d) d.reorder(id, dir); };
window._alpine_toggleSpk   = (id, checked)=> { const d = _getInquiryData(); if (d) d.toggleSpkSelection(id, checked); };
window._alpine_toggleSelectAllSpk = (checked)=> { const d = _getInquiryData(); if (d) d.toggleSelectAllSpk(checked); };
window._alpine_deleteProduct = (id)       => { const d = _getInquiryData(); if (d) d.deleteProduct(id); };

// ── SweetAlert2 Confirmation Dialogs (delegates to x-sweetalert component) ──
function confirmCloseInquiry() {
    window.confirmDialog({
        title: 'Close Inquiry?',
        text: 'This inquiry will become read-only and no further changes can be made.',
        icon: 'warning',
        confirmButtonColor: '#059669', // Emerald 600
        confirmButtonText: 'Yes, close it!',
        cancelButtonText: 'No',
        onConfirm: () => document.getElementById('close-inquiry-form').submit()
    });
}

function confirmCancelInquiry() {
    window.confirmDialog({
        title: 'Cancel Inquiry?',
        text: 'Are you sure you want to cancel this inquiry?',
        icon: 'warning',
        confirmButtonColor: '#dc2626', // Rose 600
        confirmButtonText: 'Yes, cancel it!',
        cancelButtonText: 'No',
        onConfirm: () => document.getElementById('cancel-inquiry-form').submit()
    });
}
</script>

@endsection
