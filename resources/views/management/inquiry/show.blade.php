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
    <div x-show="loading" class="fixed inset-0 z-[9000] bg-black/35 dark:bg-black/55 backdrop-blur-xs flex items-center justify-center" style="display:none;">
        <div class="bg-white dark:bg-slate-900 px-6 py-5 rounded-md flex flex-col items-center justify-center gap-3 shadow-2xl border border-slate-100 dark:border-slate-800 text-center min-w-[130px]">
            <div class="relative w-9 h-9">
                <!-- Spinner background ring -->
                <div class="w-9 h-9 rounded-full border-[3.5px] border-slate-100 dark:border-slate-800"></div>
                <!-- Spinning active arc -->
                <div class="absolute top-0 left-0 w-9 h-9 rounded-full border-[3.5px] border-transparent border-t-blue-600 dark:border-t-blue-500 animate-spin"></div>
            </div>
            <span class="text-[11px] font-bold text-slate-650 dark:text-slate-200 tracking-wider uppercase animate-pulse">Processing...</span>
        </div>
    </div>


    {{-- ── Page Header (Title & Actions Toolbar) ─────────────────────────── --}}
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 border-b border-slate-200 dark:border-slate-800 pb-4 mb-4 select-none">
        
        {{-- Left Side: Back Arrow, Title, Status & Date --}}
        <div class="flex items-center gap-3.5">
            <a href="{{ route('management.inquiry.index') }}"
               @click.prevent="
                   if (orderDirty || Object.keys(unsavedDecisions).length > 0) {
                       window.confirmDialog({
                           title: 'Unsaved Changes',
                           text: 'You have unsaved changes (Order or Decisions). Are you sure you want to go back? Unsaved changes will be lost.',
                           icon: 'warning',
                           confirmButtonColor: '#dc2626',
                           confirmButtonText: 'Yes, leave',
                           cancelButtonText: 'Cancel',
                           onConfirm: () => {
                               allowLeaving = true;
                               window.location.href = '{{ route('management.inquiry.index') }}';
                           }
                       });
                   } else {
                       allowLeaving = true;
                       window.location.href = '{{ route('management.inquiry.index') }}';
                   }
               "
               class="flex items-center justify-center w-9 h-9 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-500 hover:text-blue-600 hover:border-blue-500 transition-colors text-sm rounded-xs"
               title="Back to list">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none mb-1.5 flex items-center gap-1.5">
                    <span>Project Inquiry</span>
                </div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-xl font-extrabold tracking-tight text-slate-800 dark:text-white leading-none">
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
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-[10px] font-bold border rounded-xs {{ $st['cls'] }}">
                        <span class="w-1 h-1 rounded-full {{ $st['dot'] }}"></span>
                        {{ $inquiry->status }}
                    </span>
                    <span class="text-xs text-slate-400 dark:text-slate-500 font-medium ml-1">
                        {{ $inquiry->inquiry_date->format('d M Y') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Right Side: Actions Toolbar --}}
        <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto justify-start lg:justify-end">
            @if(!$isLocked)
                <button @click="openAddProduct()"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition-colors cursor-pointer rounded-xs">
                    <i class="fa-solid fa-plus text-xs"></i> Add Product
                </button>
                <button @click="showImportModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs font-bold transition-colors cursor-pointer rounded-xs">
                    <i class="fa-solid fa-file-excel text-emerald-600 dark:text-emerald-500 text-xs"></i> Import Excel
                </button>
                <button @click="showAssessmentConfigModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs font-bold transition-colors cursor-pointer rounded-xs">
                    <i class="fa-solid fa-gears text-xs"></i> Scoring Config
                </button>
            @endif

            @if($inquiry->status === 'Draft')
                @if(!$isLocked)
                    <div class="h-6 w-[1px] bg-slate-300 dark:bg-slate-700 mx-1"></div>
                @endif
                <button @click="showEditModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs font-bold transition-colors cursor-pointer rounded-xs">
                    <i class="fa-solid fa-pen-to-square text-xs"></i> Edit Info
                </button>
                <form id="close-inquiry-form" method="POST" action="{{ route('management.inquiry.close', $inquiry->hashed_id) }}"
                      class="inline">
                    @csrf
                    <button type="button" onclick="confirmCloseInquiry()"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-colors cursor-pointer rounded-xs">
                        <i class="fa-solid fa-lock text-xs"></i> Close Inquiry
                    </button>
                </form>
                <form id="cancel-inquiry-form" method="POST" action="{{ route('management.inquiry.cancel', $inquiry->hashed_id) }}"
                      class="inline">
                    @csrf
                    <button type="button" onclick="confirmDeleteInquiry()"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition-colors cursor-pointer rounded-xs">
                        <i class="fa-solid fa-trash-can text-xs"></i> Delete
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- ── Inquiry Metadata Card ────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 mb-4 rounded-xs">
        <div class="grid grid-cols-2 lg:grid-cols-5 divide-x divide-y lg:divide-y-0 divide-slate-200 dark:divide-slate-700">
            <div class="px-4 py-3">
                <span class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-0.5">Customer</span>
                <span class="text-xs font-bold text-slate-800 dark:text-slate-100">
                    {{ $inquiry->customer->name ?? '—' }}
                    @if($inquiry->customer && $inquiry->customer->code)
                        <span class="text-slate-800 dark:text-slate-500 font-medium">({{ $inquiry->customer->code }})</span>
                    @endif
                </span>
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

    {{-- ── Flash & Page Alerts ─────────────────────────────────────────── --}}
    @if(session('success'))
        <div class="mb-3 flex items-start gap-2 p-3 bg-emerald-50 dark:bg-emerald-950/30 border-l-4 border-emerald-500 text-emerald-850 dark:text-emerald-400 text-xs">
            <i class="fa-solid fa-circle-check mt-0.5"></i>{{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-3 flex items-start gap-2 p-3 bg-rose-50 dark:bg-rose-950/30 border-l-4 border-rose-500 text-rose-850 dark:text-rose-400 text-xs">
            <i class="fa-solid fa-circle-xmark mt-0.5"></i>{{ session('error') }}
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
                'forex'              => $p->forex,
                'material_condition' => $p->material_condition,
                'decision'           => $p->decision,
                'reviewed_product_id'=> $p->reviewed_product_id,
            ];
        });
    @endphp
    <script>
        window.__inquiryProducts = @json($productsForJs);
        window.__reviewedProductsList = @json($reviewedProductsList);
    </script>

    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 shadow-sm mt-4">

        {{-- Table toolbar --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 px-4 py-3 border-b border-slate-200 dark:border-slate-700 bg-gradient-to-r from-slate-50 to-white dark:from-slate-900/60 dark:to-slate-800">
            <div class="flex items-center gap-3 flex-wrap">
                <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Products &amp; Feasibility Scoring</span>
                <span class="inline-flex items-center px-2 py-0.5 bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 text-[9px] font-bold border border-blue-100 dark:border-blue-900/40"
                      x-text="`${products.length} items`">{{ $inquiry->products->count() }} items</span>
                {{-- Unsaved indicator --}}
                <span x-show="orderDirty" x-cloak
                      class="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-50 dark:bg-amber-955/30 text-amber-600 dark:text-amber-400 text-[9px] font-bold border border-amber-200 dark:border-amber-900/40 animate-pulse">
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
                                <th class="p-3 w-20 text-left bg-slate-50/50 dark:bg-slate-900/50">
                                    <input type="checkbox" id="select-all-spk"
                                           onchange="window._alpine_toggleSelectAllSpk(this.checked)"
                                           class="w-3.5 h-3.5 text-blue-600 border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-blue-500 cursor-pointer rounded-xs"
                                           title="Select/Deselect All Products for SPK">
                                </th>
                                <th class="p-3 bg-slate-50/50 dark:bg-slate-900/50">Part Number</th>
                                <th class="p-3 bg-slate-50/50 dark:bg-slate-900/50">Part Name</th>
                                <th class="p-3 text-center bg-slate-50/50 dark:bg-slate-900/50">Variant</th>
                                <th class="p-3 bg-slate-50/50 dark:bg-slate-900/50">Category</th>
                                <th class="p-3 text-right bg-slate-50/50 dark:bg-slate-900/50">Ann. Vol.</th>
                                <th class="p-3 text-center bg-slate-50/50 dark:bg-slate-900/50 w-28">Decision</th>
                                <th class="p-3 text-center bg-slate-50/50 dark:bg-slate-900/50 w-36">Reviewed</th>
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
                        <select id="edit_customer_select" required
                                class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500">
                            <option value="">Select Customer</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" {{ $c->id == $inquiry->customer_id ? 'selected' : '' }}>{{ $c->code ? '[' . $c->code . '] ' : '' }}{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Project Name (Model) <span class="text-rose-500">*</span></label>
                        <select id="edit_model_select" required
                                class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 disabled:opacity-50">
                            <option value="">Select Model</option>
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
                <div class="overflow-y-auto flex-1 p-5 grid grid-cols-1 lg:grid-cols-2 gap-y-6 lg:gap-y-0">
                    
                    {{-- Left side: Product Specifications --}}
                    <div class="space-y-4 lg:pr-6 lg:border-r border-slate-200 dark:border-slate-700">
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
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Forex</label>
                                <input type="text" x-model="productForm.forex"
                                       class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs focus:outline-none focus:border-blue-500 text-slate-800 dark:text-slate-100">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Mat. Condition</label>
                                <input type="text" x-model="productForm.material_condition"
                                       class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs focus:outline-none focus:border-blue-500 text-slate-800 dark:text-slate-100">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Decision</label>
                                <select x-model="productForm.decision"
                                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs focus:outline-none focus:border-blue-500 text-slate-800 dark:text-slate-100">
                                    <option value="">Select Decision</option>
                                    <option value="go">Go</option>
                                    <option value="not go">Not Go</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Reviewed By</label>
                                <select x-model="productForm.reviewed_product_id"
                                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs focus:outline-none focus:border-blue-500 text-slate-800 dark:text-slate-100">
                                    <option value="">Select Reviewer</option>
                                    <template x-for="r in (window.__reviewedProductsList || [])" :key="r.id">
                                        <option :value="r.id" x-text="r.reviewer" :selected="productForm.reviewed_product_id == r.id"></option>
                                    </template>
                                </select>
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
                                                        : 'border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400'"
                                                    class="flex-1 px-2.5 py-1.5 border text-[11px] text-center transition-all hover:bg-slate-50 dark:hover:bg-slate-700 focus:outline-none flex justify-between sm:justify-center items-center gap-1"
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
                                class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 px-3 py-1.5 text-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-blue-500 transition-colors resize-none"
                                {{ $isLocked ? 'disabled' : '' }}></textarea>
                        </div>

                        {{-- Score Summary Card --}}
                        <div class="bg-blue-600 dark:bg-blue-700 text-white p-3 flex flex-col justify-between space-y-2">
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
                               class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs focus:outline-none focus:border-blue-500 text-slate-750 dark:text-slate-300 file:mr-3 file:py-2 file:px-3 file:border-0 file:bg-slate-200 dark:file:bg-slate-700 file:text-slate-700 dark:file:text-slate-200 file:text-xs file:font-bold hover:file:bg-slate-300 dark:hover:file:bg-slate-650 cursor-pointer">
                    </div>
                    {{-- Overwrite mode toggle --}}
                    <div class="flex items-start gap-3 p-3 bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/30 rounded-xs">
                        <input type="checkbox" name="append" value="true" id="import_append_mode"
                               class="mt-0.5 w-3.5 h-3.5 rounded-xs border-slate-300 accent-blue-600 cursor-pointer flex-shrink-0">
                        <div>
                            <label for="import_append_mode" class="text-xs font-bold text-amber-800 dark:text-amber-300 cursor-pointer">Overwrite mode (Check to clear existing data)</label>
                            <p class="text-[10px] text-amber-700 dark:text-amber-400 mt-0.5">If checked, the import process will <strong class="font-bold text-amber-900 dark:text-amber-200">delete all existing products</strong> in this inquiry before inserting the new ones. If unchecked, new products will be <strong class="font-bold text-amber-900 dark:text-amber-200">appended</strong> to the current list.</p>
                        </div>
                    </div>

                    {{-- Import Specific Flash Messages --}}
                    @if(session('import_success'))
                        <div class="flex items-start gap-2 p-3 bg-emerald-50 dark:bg-emerald-950/30 border-l-4 border-emerald-500 text-emerald-800 dark:text-emerald-400 text-xs">
                            <i class="fa-solid fa-circle-check mt-0.5"></i>{{ session('import_success') }}
                        </div>
                    @endif
                    @if(session('import_error'))
                        <div class="flex items-start gap-2 p-3 bg-rose-50 dark:bg-rose-950/30 border-l-4 border-rose-500 text-rose-800 dark:text-rose-400 text-xs">
                            <i class="fa-solid fa-circle-xmark mt-0.5"></i>{{ session('import_error') }}
                        </div>
                    @endif
                    @if(session('import_errors') && is_array(session('import_errors')) && count(session('import_errors')) > 0)
                        <div class="space-y-2 p-3 bg-amber-50 dark:bg-amber-955/20 border-l-4 border-amber-500 text-amber-800 dark:text-amber-400 text-xs">
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

    {{-- ── Floating Actions & Save Order Bar ──────────────────────────────── --}}
    <div x-show="selectedSpkProducts.length > 0 || orderDirty || Object.keys(unsavedDecisions).length > 0 || Object.keys(unsavedReviewed).length > 0" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-y-20 opacity-0"
         x-transition:enter-end="translate-y-0 opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-y-0 opacity-100"
         x-transition:leave-end="translate-y-20 opacity-0"
         class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[5000] bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-100 px-5 py-2.5 rounded-md shadow-2xl flex items-center gap-5 w-max max-w-[95vw] select-none">
         
         {{-- PART 1: Selected Items Left Controls (Select Info & Reorder) --}}
         <template x-if="selectedSpkProducts.length > 0">
             <div class="flex items-center gap-4">
                 <div class="flex items-center gap-2 text-xs font-bold whitespace-nowrap text-slate-500 dark:text-slate-400">
                     <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                     <span x-text="selectedSpkProducts.length"></span> selected
                 </div>
                 
                 <div class="h-4 w-[1px] bg-slate-200 dark:bg-slate-700"></div>
                 
                 {{-- Action: Move to Target Position --}}
                 <div class="flex items-center gap-1 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 px-2 py-0.5 rounded-xs">
                     <span class="text-[10px] text-slate-500 dark:text-slate-400 font-bold whitespace-nowrap">Move to Row:</span>
                     <input type="number" x-model="targetOrderInput" min="1" :max="products.length"
                            class="w-12 h-5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs font-bold text-center text-slate-800 dark:text-slate-100 focus:outline-none focus:border-indigo-500"
                            placeholder="Pos">
                     <button type="button" @click="moveSelectedToTarget()"
                             class="px-2 py-0.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-extrabold text-[9px] uppercase tracking-wider rounded-xs flex items-center justify-center transition-colors cursor-pointer border border-slate-300 dark:border-slate-600">
                         Go
                     </button>
                 </div>
                 
                 {{-- Deselect Button --}}
                 <button type="button" @click="selectedSpkProducts = []; renderRows()"
                         class="text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors font-bold cursor-pointer bg-transparent border-0">
                     Clear
                 </button>
             </div>
         </template>

          {{-- Divider if items are selected AND there are unsaved changes (order or decisions) --}}
          <template x-if="selectedSpkProducts.length > 0 && (orderDirty || Object.keys(unsavedDecisions).length > 0 || Object.keys(unsavedReviewed).length > 0)">
              <div class="h-5 w-[1px] bg-slate-200 dark:bg-slate-700"></div>
          </template>

          {{-- PART 2: Combined Unsaved Changes Actions --}}
          <template x-if="orderDirty || Object.keys(unsavedDecisions).length > 0 || Object.keys(unsavedReviewed).length > 0">
              <div class="flex items-center gap-3">
                  <div class="flex items-center gap-1.5 text-xs font-bold text-amber-600 dark:text-amber-400 whitespace-nowrap">
                      <span class="relative flex h-2 w-2">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                      </span>
                      <span x-text="orderDirty && (Object.keys(unsavedDecisions).length > 0 || Object.keys(unsavedReviewed).length > 0) ? 'Unsaved Order & Product Updates' : (orderDirty ? 'Unsaved Order' : 'Unsaved Updates')"></span>
                  </div>
                  <button @click="saveAllChanges()"
                          :disabled="savingOrder"
                          class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white text-xs font-bold transition-colors cursor-pointer rounded-xs border-0 whitespace-nowrap">
                      <template x-if="!savingOrder">
                          <span class="flex items-center gap-1.5">
                              <i class="fa-solid fa-floppy-disk"></i> Save Changes
                          </span>
                      </template>
                      <template x-if="savingOrder">
                          <span class="flex items-center gap-1.5">
                              <i class="fa-solid fa-circle-notch fa-spin"></i> Saving…
                          </span>
                      </template>
                  </button>
                  <button @click="discardAllChanges()"
                          class="px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs font-semibold transition-colors cursor-pointer rounded-xs whitespace-nowrap">
                      <i class="fa-solid fa-rotate-left"></i> Reset
                  </button>
              </div>
          </template>

         {{-- Divider if items are selected --}}
         <template x-if="selectedSpkProducts.length > 0">
             <div class="h-5 w-[1px] bg-slate-200 dark:bg-slate-700"></div>
         </template>

         {{-- PART 3: Create WO Action (Paling Kanan) --}}
         <template x-if="selectedSpkProducts.length > 0">
             <form id="create-spk-form" action="{{ route('management.work-order.create') }}" method="POST" class="inline flex-shrink-0"
                   @submit="allowLeaving = true; creatingWo = true">
                 @csrf
                 <input type="hidden" name="inquiry_id" value="{{ $inquiry->hashed_id }}">
                 <template x-for="id in selectedSpkProducts" :key="id">
                     <input type="hidden" name="products[]" :value="products.find(p => p.id === id)?.hashed_id || id">
                 </template>
                 <button type="submit"
                          :disabled="orderDirty || Object.keys(unsavedDecisions).length > 0 || Object.keys(unsavedReviewed).length > 0 || creatingWo"
                          :title="orderDirty || Object.keys(unsavedDecisions).length > 0 || Object.keys(unsavedReviewed).length > 0 ? 'Please save your changes before creating a Work Order.' : ''"
                          class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 disabled:bg-slate-300 dark:disabled:bg-slate-700 disabled:text-slate-450 dark:disabled:text-slate-500 disabled:cursor-not-allowed text-white text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5 rounded-xs border-0 whitespace-nowrap">
                     <template x-if="!creatingWo">
                          <span class="flex items-center gap-1.5">
                              <i class="fa-solid fa-file-signature"></i> Create WO
                          </span>
                      </template>
                      <template x-if="creatingWo">
                          <span class="flex items-center gap-1.5">
                              <i class="fa-solid fa-circle-notch fa-spin"></i> Creating WO…
                          </span>
                      </template>
                 </button>
             </form>
         </template>
    </div>

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
        showImportModal: {{ (session('import_errors') || session('import_success') || session('import_error')) ? 'true' : 'false' }},
        editForm: {
            id: '{{ $inquiry->id }}',
            customer_id: '{{ $inquiry->customer_id }}',
            project_id:  '{{ $inquiry->model_id }}',
            inquiry_date:  '{{ $inquiry->inquiry_date->format('Y-m-d') }}',
            remarks:       @json($inquiry->remarks)
        },
        getFilteredModels(customerId) {
            const allModels = @json($models);
            const filtered = allModels.filter(m => m.customer_id == customerId);
            const unique = [];
            const names = new Set();
            filtered.forEach(m => {
                if (m.id == this.editForm.project_id || !names.has(m.name)) {
                    names.add(m.name);
                    unique.push(m);
                }
            });
            return unique;
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
        allowLeaving: false,
        orderDirty: false,
        savingOrder: false,
        creatingWo: false,
        selectedSpkProducts: [],
        unsavedDecisions: {},
        unsavedReviewed: {},
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

        targetOrderInput: '',
        
        moveSelectedToTarget() {
            const targetPos = parseInt(this.targetOrderInput);
            if (isNaN(targetPos) || targetPos < 1 || targetPos > this.products.length) {
                showToast('Please enter a valid target position (1 to ' + this.products.length + ').', 'warning');
                return;
            }

            const targetIndex = targetPos - 1;
            const selectedIds = this.selectedSpkProducts.map(id => Number(id));
            const selectedItems = [];
            const remainingItems = [];

            // Separate selected products and remaining products preserving order
            this.products.forEach(p => {
                if (selectedIds.includes(Number(p.id))) {
                    selectedItems.push(p);
                } else {
                    remainingItems.push(p);
                }
            });

            if (selectedItems.length === 0) return;

            // Insert selected items at targetIndex of remaining items
            remainingItems.splice(targetIndex, 0, ...selectedItems);

            this.products = remainingItems;
            this.orderDirty = true;
            this.targetOrderInput = '';
            this.renderRows();
            showToast('Moved selected items. Click "Save Order" in the footer to apply changes.', 'success');
        },

        init() {
            // Bootstrap products from server-injected global variable
            if (window.__inquiryProducts) {
                this.products = window.__inquiryProducts;
                this._initialProducts = JSON.parse(JSON.stringify(this.products));
            }
            this.$nextTick(() => this.renderRows());

            // Handle page reload/browser navigation warning when changes are unsaved
            window.addEventListener('beforeunload', (e) => {
                if (!this.allowLeaving && (this.orderDirty || Object.keys(this.unsavedDecisions).length > 0)) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });

            // Watch showEditModal to initialize Select2
            this.$watch('showEditModal', (value) => {
                if (value) {
                    setTimeout(() => {
                        const self = this;
                        const $customerSelect = $('#edit_customer_select');
                        
                        if (typeof $customerSelect.select2 === 'function') {
                            $customerSelect.select2({
                                dropdownParent: $customerSelect.parent(),
                                width: '100%'
                            }).off('change').on('change', function() {
                                const newCustId = $(this).val();
                                if (newCustId !== self.editForm.customer_id) {
                                    self.editForm.customer_id = newCustId;
                                    self.editForm.project_id = '';
                                }
                                self.updateModelSelect2();
                            }).val(self.editForm.customer_id).trigger('change');

                            // Initialize Model Select2
                            self.updateModelSelect2();
                        }
                    }, 100);
                }
            });

            @if(session('import_success'))
                showToast("{{ session('import_success') }}", 'success');
            @endif
            @if(session('import_error'))
                showToast("{{ session('import_error') }}", 'error');
            @endif
        },

        updateModelSelect2() {
            const self = this;
            const models = this.getFilteredModels(this.editForm.customer_id);
            const $modelSelect = $('#edit_model_select');
            
            // Clear existing options
            $modelSelect.empty().append('<option value="">Select Model</option>');
            
            // Add filtered options
            models.forEach(m => {
                const isSelected = m.id == self.editForm.project_id;
                const option = new Option(m.name, m.id, isSelected, isSelected);
                $modelSelect.append(option);
            });
            
            // Initialize/Re-initialize select2 and set value
            if (typeof $modelSelect.select2 === 'function') {
                $modelSelect.select2({
                    dropdownParent: $modelSelect.parent(),
                    width: '100%'
                }).off('change').on('change', function() {
                    self.editForm.project_id = $(this).val();
                }).val(this.editForm.project_id || '').trigger('change');
            }
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

            // Sort products so that original 'not go' is always at the bottom (keeps order stable during edit)
            filteredProducts.sort((a, b) => {
                const aNotGo = a.decision === 'not go' ? 1 : 0;
                const bNotGo = b.decision === 'not go' ? 1 : 0;
                return aNotGo - bNotGo;
            });

            const total = filteredProducts.length;
            if (total === 0) {
                const emptyMsg = this.searchQuery 
                    ? 'No records found matching your search.'
                    : 'No products have been added yet.';
                tbody.innerHTML = `<tr><td colspan="14" class="py-16 text-center">
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
                const vol = prod.annual_volume ? prod.annual_volume.toLocaleString() : '—';
                
                const hasSpk = prod.spkList && prod.spkList.length > 0;
                const spkTooltip = hasSpk ? 'Associated WOs:\n' + prod.spkList.map(s => `• ${s.number} (${s.status})`).join('\n') : '';
                const spkBadge = hasSpk 
                    ? `<span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-xs bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-450 border border-emerald-200 dark:border-emerald-800 text-[9px] font-bold cursor-pointer whitespace-nowrap ml-1.5" title="${spkTooltip}">
                         <i class="fa-solid fa-file-signature text-[8px]"></i> ${prod.spkList.length} WO
                       </span>`
                    : '';

                const chatBtn = `<button onclick="event.stopPropagation();window.dispatchEvent(new CustomEvent('open-product-chat', { detail: { id: ${prod.id} } }))" title="Chat & Details" class="w-6 h-6 flex items-center justify-center bg-indigo-600 hover:bg-indigo-700 text-white border border-indigo-600 hover:border-indigo-700 transition-colors"><i class="fa-solid fa-comments text-[9px]"></i></button>`;
                const editBtn = !isLocked
                    ? `<button onclick="event.stopPropagation();window._alpine_editProduct(${prod.id})" title="Edit" class="w-6 h-6 flex items-center justify-center bg-slate-100 hover:bg-blue-50 dark:bg-slate-800 dark:hover:bg-blue-950/30 border border-slate-300 dark:border-slate-700 hover:border-blue-300 dark:hover:border-blue-800 text-slate-600 dark:text-slate-350 hover:text-blue-700 dark:hover:text-blue-400 transition-colors"><i class="fa-solid fa-pen text-[9px]"></i></button>`
                    : `<span class="text-[10px] text-slate-400 italic">Locked</span>`;
                const deleteBtn = !isLocked
                    ? `<button onclick="event.stopPropagation();window._alpine_deleteProduct(${prod.id})" title="Delete" class="w-6 h-6 flex items-center justify-center bg-slate-100 hover:bg-rose-50 dark:bg-slate-800 dark:hover:bg-rose-950/30 border border-slate-300 dark:border-slate-700 hover:border-rose-300 dark:hover:border-rose-800 text-slate-600 dark:text-slate-350 hover:text-rose-600 dark:hover:text-rose-400 transition-colors"><i class="fa-solid fa-trash text-[9px]"></i></button>`
                    : '';

                // Background calculation: blue highlight when selected, otherwise alternate zebra striping
                const currentDecision = this.unsavedDecisions[prod.id] !== undefined ? this.unsavedDecisions[prod.id] : prod.decision;
                const currentReviewed = this.unsavedReviewed[prod.id] !== undefined ? this.unsavedReviewed[prod.id] : prod.reviewed_product_id;
                const isNotGo = currentDecision === 'not go';
                const isGo = currentDecision === 'go';
                const disabledAttr = !isGo ? 'disabled' : '';
                const cursorClass = !isGo ? 'cursor-not-allowed opacity-30' : 'cursor-pointer';
                const draggable = !isLocked && !isNotGo ? 'true' : 'false';
                
                let rowBg = '';
                let hoverClass = 'hover:bg-slate-100 dark:hover:bg-slate-700/60';
                if (isNotGo) {
                    rowBg = 'bg-slate-200/50 dark:bg-slate-900/60';
                    hoverClass = 'hover:bg-slate-200/80 dark:hover:bg-slate-850';
                } else if (currentDecision === 'go') {
                    rowBg = 'bg-emerald-100/50 dark:bg-emerald-950/25';
                    hoverClass = 'hover:bg-emerald-100/80 dark:hover:bg-emerald-900/40';
                } else if (isSelected) {
                    rowBg = 'bg-blue-50/70 dark:bg-blue-950/20 text-blue-900 dark:text-blue-100';
                    hoverClass = 'hover:bg-blue-100/70 dark:hover:bg-blue-900/40';
                } else {
                    rowBg = index % 2 === 0 ? 'bg-white dark:bg-slate-800' : 'bg-slate-100/40 dark:bg-slate-900/30';
                }

                const trClass = `border-b border-slate-200 dark:border-slate-700 ${rowBg} ${hoverClass} transition-colors duration-150 ${!isLocked && !isNotGo ? 'cursor-move' : ''}`;

                const textMuteClass = isNotGo ? 'text-slate-400 dark:text-slate-500 italic' : 'text-slate-650 dark:text-slate-300';
                const textMuteModel = isNotGo ? 'text-slate-400 dark:text-slate-500 italic' : 'text-slate-650 dark:text-slate-100';
                const textMuteCat   = isNotGo ? 'text-slate-400 dark:text-slate-500/80 italic' : 'text-slate-500 dark:text-slate-400';

                return `<tr id="row-${prod.id}" class="${trClass}" draggable="${draggable}"
                    data-id="${prod.id}"
                    onclick="if(!event.defaultPrevented && !${isLocked} && !${isNotGo})window._alpine_editProduct(${prod.id})">
                  <td class="p-3 text-center text-slate-500 dark:text-slate-450 font-mono text-xs font-semibold" onclick="event.stopPropagation()">
                    <span>${index + 1}</span>
                  </td>
                  <td class="p-3 text-left" onclick="event.stopPropagation()">
                    <div class="flex items-center justify-start gap-2">
                      <i class="fa-solid fa-grip-vertical text-slate-400 dark:text-slate-500 cursor-grab active:cursor-grabbing hover:text-slate-600 dark:hover:text-slate-300 text-xs ${!isLocked && !isNotGo ?'':'opacity-25'}" title="${!isLocked && !isNotGo ?'Drag to reorder':''}"></i>
                      <input type="checkbox" ${checkedAttr} ${disabledAttr}
                        onchange="event.stopPropagation();window._alpine_toggleSpk(${prod.id}, this.checked)"
                        class="w-3.5 h-3.5 text-blue-600 border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 focus:ring-blue-500 rounded-xs ${cursorClass}"
                        title="${!isGo ? 'Only products with decision Go can be selected for SPK' : ''}">
                      ${spkBadge}
                    </div>
                  </td>
                  <td class="p-3 font-semibold ${textMuteClass}">${prod.customer_part_no || '—'}</td>
                  <td class="p-3 max-w-[180px] ${textMuteClass}" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="${prod.customer_part_name || ''}">${prod.customer_part_name || '—'}</td>
                  <td class="p-3 text-center font-medium ${textMuteClass}">${prod.variant || '—'}</td>
                  <td class="p-3 ${textMuteCat}">${prod.part_category || '—'}</td>
                  <td class="p-3 text-right font-mono ${isNotGo ? 'text-slate-400 dark:text-slate-500' : 'text-slate-700 dark:text-slate-300'}">${vol}</td>
                  <td class="p-3 text-center" onclick="event.stopPropagation()">
                    <select onchange="window._alpine_changeDecision(${prod.id}, this.value)"
                            class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-750 px-1.5 py-1.5 text-[10px] font-semibold uppercase rounded-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                      <option value="" ${!currentDecision ? 'selected' : ''}>Select</option>
                      <option value="go" ${currentDecision === 'go' ? 'selected' : ''}>Go</option>
                      <option value="not go" ${currentDecision === 'not go' ? 'selected' : ''}>Not Go</option>
                    </select>
                  </td>
                  <td class="p-3 text-center" onclick="event.stopPropagation()">
                    <select onchange="window._alpine_changeReviewed(${prod.id}, this.value)"
                            class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-750 px-1.5 py-1.5 text-[10px] font-semibold uppercase rounded-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer w-full max-w-[120px]">
                      <option value="" ${!currentReviewed ? 'selected' : ''}>Select</option>
                      ${(window.__reviewedProductsList || []).map(r => {
                          const isSel = currentReviewed == r.id ? 'selected' : '';
                          return `<option value="${r.id}" ${isSel}>${r.reviewer}</option>`;
                      }).join('')}
                    </select>
                  </td>
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
                    <div class="inline-flex items-center gap-1.5 justify-end">${chatBtn}${editBtn}${deleteBtn}</div>
                  </td>
                </tr>`;
            });

            tbody.innerHTML = rows.join('');

            // Sync master checkbox check state based on selectable (non-not go) products
            const selectAll = document.getElementById('select-all-spk');
            if (selectAll) {
                const selectable = this.products.filter(p => {
                    const decision = this.unsavedDecisions[p.id] !== undefined ? this.unsavedDecisions[p.id] : p.decision;
                    return decision === 'go';
                });
                selectAll.checked = selectable.length > 0 && this.selectedSpkProducts.length === selectable.length;
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
                 has_2d_data: false, has_3d_data: false, has_tech_doc: false, variant: '', remarks: '',
                 forex: '', material_condition: '', decision: '', reviewed_product_id: ''
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
                     remarks:             prod.remarks || '',
                     forex:               prod.forex || '',
                     material_condition:  prod.material_condition || '',
                     decision:            prod.decision || '',
                      reviewed_product_id: prod.reviewed_product_id || ''
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
                const prod = this.products.find(p => p.id == prodId);
                const decision = prod ? (this.unsavedDecisions[prod.id] !== undefined ? this.unsavedDecisions[prod.id] : prod.decision) : '';
                if (decision !== 'go') return;
                if (!this.selectedSpkProducts.includes(prodId)) {
                    this.selectedSpkProducts.push(prodId);
                }
            } else {
                this.selectedSpkProducts = this.selectedSpkProducts.filter(id => id !== prodId);
            }
            // Sync master checkbox state
            const selectAll = document.getElementById('select-all-spk');
            if (selectAll) {
                const selectable = this.products.filter(p => {
                    const decision = this.unsavedDecisions[p.id] !== undefined ? this.unsavedDecisions[p.id] : p.decision;
                    return decision === 'go';
                });
                selectAll.checked = selectable.length > 0 && this.selectedSpkProducts.length === selectable.length;
            }
            this.renderRows();
        },

        toggleSelectAllSpk(isChecked) {
            if (isChecked) {
                this.selectedSpkProducts = this.products
                    .filter(p => {
                        const decision = this.unsavedDecisions[p.id] !== undefined ? this.unsavedDecisions[p.id] : p.decision;
                        return decision === 'go';
                    })
                    .map(p => p.id);
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
        },

        changeDecisionLocal(prodId, decisionValue) {
            const originalProduct = this.products.find(p => p.id === prodId);
            const originalDecision = originalProduct ? originalProduct.decision : '';
            
            if (decisionValue === (originalDecision || '')) {
                delete this.unsavedDecisions[prodId];
            } else {
                this.unsavedDecisions[prodId] = decisionValue;
            }

            if (decisionValue !== 'go') {
                this.selectedSpkProducts = this.selectedSpkProducts.filter(id => id !== prodId);
            }

            this.renderRows();
        },

        changeReviewedLocal(prodId, reviewedValue) {
            const originalProduct = this.products.find(p => p.id === prodId);
            const originalReviewed = originalProduct ? originalProduct.reviewed_product_id : '';
            
            if (reviewedValue === (originalReviewed || '')) {
                delete this.unsavedReviewed[prodId];
            } else {
                this.unsavedReviewed[prodId] = reviewedValue ? parseInt(reviewedValue) : null;
            }

            this.renderRows();
        },

        discardAllChanges() {
            this.unsavedDecisions = {};
            this.unsavedReviewed = {};
            if (this.orderDirty) {
                this.products = JSON.parse(JSON.stringify(this._initialProducts));
                this.orderDirty = false;
            }
            this.renderRows();
            showToast('All changes discarded.', 'info');
        },

        saveAllChanges() {
            const decisionEntries = Object.entries(this.unsavedDecisions);
            const reviewedEntries = Object.entries(this.unsavedReviewed);
            const hasDecisions = decisionEntries.length > 0;
            const hasReviewed = reviewedEntries.length > 0;
            const hasOrder = this.orderDirty;

            if (!hasDecisions && !hasReviewed && !hasOrder) return;

            this.loading = true;
            this.savingOrder = true;

            const promises = [];

            // 1. Decisions & Reviewed Promise (Batch request for all decisions and reviewed products)
            if (hasDecisions || hasReviewed) {
                const decisionsPayload = {};
                decisionEntries.forEach(([prodId, decisionValue]) => {
                    decisionsPayload[prodId] = decisionValue;
                });
                const reviewedPayload = {};
                reviewedEntries.forEach(([prodId, reviewedValue]) => {
                    reviewedPayload[prodId] = reviewedValue;
                });
                promises.push(
                    fetch('{{ route('management.inquiry-product.update-decisions-batch') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ 
                            decisions: decisionsPayload,
                            reviewed: reviewedPayload
                        })
                    })
                    .then(res => res.json())
                    .catch(() => ({ success: false }))
                );
            }

            // 2. Order Promise
            if (hasOrder) {
                const orderedIds = this.products.map(p => p.id);
                promises.push(
                    fetch('{{ route('management.inquiry-product.reorder-all') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ ids: orderedIds })
                    })
                    .then(res => res.json())
                    .catch(() => ({ success: false }))
                );
            }

            Promise.all(promises)
            .then(results => {
                this.loading = false;
                this.savingOrder = false;

                const failed = results.filter(r => !r.success);
                if (failed.length === 0) {
                    showToast('All changes saved successfully!', 'success');
                    
                    // Apply decisions locally
                    if (hasDecisions) {
                        decisionEntries.forEach(([prodId, decisionValue]) => {
                            const product = this.products.find(p => p.id == prodId);
                            if (product) {
                                product.decision = decisionValue;
                                if (decisionValue !== 'go') {
                                    this.selectedSpkProducts = this.selectedSpkProducts.filter(sid => sid != prodId);
                                }
                            }
                        });
                    }

                    // Apply reviewed status locally
                    if (hasReviewed) {
                        reviewedEntries.forEach(([prodId, reviewedValue]) => {
                            const product = this.products.find(p => p.id == prodId);
                            if (product) {
                                product.reviewed_product_id = reviewedValue;
                            }
                        });
                    }

                    // Reset tracking flags
                    this.unsavedDecisions = {};
                    this.unsavedReviewed = {};
                    this.orderDirty = false;
                    this._initialProducts = JSON.parse(JSON.stringify(this.products));
                    this.renderRows();
                } else {
                    showToast('Some changes failed to save. Reloading to sync...', 'error');
                    setTimeout(() => window.location.reload(), 1500);
                }
            })
            .catch(() => {
                this.loading = false;
                this.savingOrder = false;
                showToast('Network error while saving changes.', 'error');
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
window._alpine_changeDecision = (id, val) => { const d = _getInquiryData(); if (d) d.changeDecisionLocal(id, val); };
window._alpine_changeReviewed = (id, val) => { const d = _getInquiryData(); if (d) d.changeReviewedLocal(id, val); };

// ── SweetAlert2 Confirmation Dialogs (delegates to x-sweetalert component) ──
function confirmCloseInquiry() {
    window.confirmDialog({
        title: 'Close Inquiry?',
        text: 'This inquiry will become read-only and no further changes can be made.',
        icon: 'warning',
        confirmButtonColor: '#059669', // Emerald 600
        confirmButtonText: 'Yes, close it!',
        cancelButtonText: 'No',
        onConfirm: () => {
            const d = _getInquiryData();
            if (d) d.allowLeaving = true;
            document.getElementById('close-inquiry-form').submit();
        }
    });
}

function confirmDeleteInquiry() {
    window.confirmDialog({
        title: 'Delete Inquiry?',
        text: 'Are you sure you want to delete this inquiry?',
        icon: 'warning',
        confirmButtonColor: '#dc2626', // Rose 600
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'No',
        onConfirm: () => {
            const d = _getInquiryData();
            if (d) d.allowLeaving = true;
            document.getElementById('cancel-inquiry-form').submit();
        }
    });
}
</script>

@include('management.inquiry.chat_drawer')

@endsection
