@extends('layouts.app')

@section('title', 'EBD Detail · Promise Management')
@section('page_title', 'EBD Detail')
@section('header-title', 'Engineering Breakdown (EBD)')

@section('content')
<div class="flex h-[calc(100vh-64px)] mt-16 overflow-hidden bg-white flex-col border-t border-slate-200">

    {{-- ===== METADATA BAR (MICROSOFT FLUENT STYLE WITH BACK ACTION) ===== --}}
    <div class="flex items-center gap-6 px-6 py-3 bg-slate-100 border-b border-slate-200 flex-shrink-0 text-xs">
        {{-- Back Action & Revision info --}}
        <div class="flex flex-col gap-1 pr-6 border-r border-slate-200 dark:border-slate-700 flex-shrink-0">
            <a href="{{ route('management.ebd.index') }}"
               class="inline-flex items-center justify-center gap-2 px-3 h-8 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-750 border border-slate-300 dark:border-slate-600 rounded-sm text-xs font-semibold text-slate-750 dark:text-slate-250 transition-all active:scale-98">
                <i class="fa-solid fa-arrow-left text-[10px]"></i> Back
            </a>
            <span class="text-[9px] font-mono font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none">
                Revision: {{ $ebdHeader->revision }}
            </span>
        </div>

        {{-- Metadata Grid --}}
        <div class="flex-1 grid grid-cols-2 md:grid-cols-4 gap-6">
            <div>
                <span class="block text-[10px] font-bold text-slate-500 dark:text-slate-405 uppercase tracking-wider mb-0.5">WO Number</span>
                <span class="font-medium text-slate-850 dark:text-slate-100 text-xs">{{ $ebdHeader->workOrder->wo_number ?? '—' }}</span>
            </div>
            <div>
                <span class="block text-[10px] font-bold text-slate-500 dark:text-slate-405 uppercase tracking-wider mb-0.5">Customer</span>
                <span class="font-medium text-slate-850 dark:text-slate-100 text-xs">{{ $ebdHeader->customer->name ?? '—' }}</span>
            </div>
            <div>
                <span class="block text-[10px] font-bold text-slate-500 dark:text-slate-405 uppercase tracking-wider mb-0.5">Project Model</span>
                <span class="font-medium text-slate-850 dark:text-slate-100 text-xs">{{ $ebdHeader->projectModel->name ?? '—' }}</span>
            </div>
            <div>
                <span class="block text-[10px] font-bold text-slate-500 dark:text-slate-405 uppercase tracking-wider mb-0.5">EBD Date</span>
                <span class="font-mono text-slate-900 dark:text-slate-100 font-medium text-xs">{{ $ebdHeader->date ? $ebdHeader->date->format('d M Y') : '—' }}</span>
            </div>
        </div>

        {{-- Global Status --}}
        <div class="pl-6 border-l border-slate-200 dark:border-slate-700 flex-shrink-0 flex items-center gap-2">
            @php
                $statusCls = match($ebdHeader->status) {
                    'Released' => 'bg-emerald-50 text-emerald-700 border-emerald-250 dark:bg-emerald-950/30 dark:text-emerald-450 dark:border-emerald-900/30',
                    default    => 'bg-blue-55 text-blue-700 border-blue-200 dark:bg-blue-950/30 dark:text-blue-450 dark:border-blue-900/30',
                };
            @endphp
            <span class="px-2.5 py-1 text-xs font-semibold border rounded-xs tracking-wide {{ $statusCls }}">
                {{ $ebdHeader->status ?? 'Draft' }}
            </span>
            <button type="button" id="btn-open-edit-header-modal"
                    class="px-2.5 py-1.5 text-xs font-semibold border border-slate-300 dark:border-slate-650 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 rounded-sm cursor-pointer transition-all flex items-center gap-1.5 active:scale-[0.98]">
                <i class="fa-solid fa-pen text-[10px]"></i> Edit Info
            </button>
            <button type="button" id="btn-open-import-items-modal"
                    class="px-2.5 py-1.5 text-xs font-semibold border border-indigo-200 bg-indigo-50 dark:bg-indigo-950/20 hover:bg-indigo-100/50 text-indigo-700 dark:text-indigo-400 rounded-sm cursor-pointer transition-all flex items-center gap-1.5 active:scale-[0.98]">
                <i class="fa-solid fa-file-import text-[10px]"></i> Import BOM
            </button>
            <button type="button" id="btn-open-add-item-modal"
                    class="px-2.5 py-1.5 text-xs font-semibold border border-emerald-250 bg-emerald-50 dark:bg-emerald-950/20 hover:bg-emerald-100/50 text-emerald-750 dark:text-emerald-400 rounded-sm cursor-pointer transition-all flex items-center gap-1.5 active:scale-[0.98]">
                <i class="fa-solid fa-plus text-[10px]"></i> Add Part
            </button>
        </div>
    </div>

    {{-- ===== TWO-COLUMN SPLIT PANEL CONTAINER (FULL PANEL) ===== --}}
    <div class="flex-1 flex min-h-0 overflow-hidden">
        
        {{-- LEFT PANEL: INTERACTIVE BOM TREE --}}
        <div class="w-[30%] max-w-[360px] min-w-[280px] flex flex-col bg-slate-50/50 dark:bg-slate-900/30 border-r border-slate-200 overflow-hidden h-full">
            <div class="px-4 py-3 bg-slate-100/50 dark:bg-slate-900/80 border-b border-slate-200 flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-200 flex items-center gap-2">
                    <i class="fa-solid fa-sitemap text-indigo-550 text-[10px]"></i> Component Tree
                </span>
                <span class="text-[10px] bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-100 px-2 py-0.5 rounded-xs font-mono font-semibold">
                    {{ $ebdHeader->items->count() }} items
                </span>
            </div>
            
            <div class="flex-1 overflow-y-auto p-2 space-y-0.5">
                @forelse ($ebdHeader->items as $item)
                    @php
                        $depth = ($item->bom_level ?? $item->level_aktif ?? 1) - 1;
                        $paddingLeft = $depth * 16;
                    @endphp
                    <div data-item-id="{{ $item->id }}"
                         class="bom-item-row group flex items-center gap-2.5 p-2 rounded-xs cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700/50 border-l-2 border-transparent transition-all"
                         style="padding-left: {{ max($paddingLeft + 8, 8) }}px">
                        
                        <span class="flex-shrink-0 w-5 h-5 flex items-center justify-center rounded text-[9px] font-bold bg-slate-200 dark:bg-slate-700 text-slate-750 dark:text-slate-300 group-hover:bg-slate-300 dark:group-hover:bg-slate-600 transition-colors">
                            {{ $item->bom_level ?? $item->level_aktif }}
                        </span>
                        
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-mono font-semibold text-slate-800 dark:text-slate-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 truncate leading-none mb-1 transition-colors">
                                {{ $item->part_no }}
                            </p>
                            <p class="text-[10px] text-slate-600 dark:text-slate-400 truncate">
                                {{ $item->part_name }}
                            </p>
                        </div>
                        
                        <i class="fa-solid fa-chevron-right text-[9px] text-slate-450 group-hover:text-slate-500 transition-colors"></i>
                    </div>
                @empty
                    <div class="p-6 text-center text-slate-400">
                        <i class="fa-regular fa-folder-open text-xl mb-2 block"></i>
                        No parts found.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- RIGHT PANEL: COMPACT DETAIL CARD --}}
        <div class="flex-1 flex flex-col bg-white dark:bg-slate-800 overflow-hidden h-full">
            
            {{-- Card Title Bar (Active Part Identifiers) --}}
            <div class="px-5 py-4 bg-slate-50 dark:bg-slate-900/50 border-b border-slate-350 dark:border-slate-650 flex items-center justify-between flex-shrink-0">
                <div>
                    <h3 id="active-part-no" class="text-sm font-mono font-semibold text-slate-800 dark:text-slate-100 leading-none">Select a part...</h3>
                    <p id="active-part-name" class="text-[11px] text-slate-600 dark:text-slate-400 mt-1.5 font-medium">Click any part on the left BOM list to view details.</p>
                </div>
                <div id="active-part-actions" class="hidden flex gap-2">
                    <button type="button" id="btn-edit-item"
                            class="inline-flex items-center justify-center gap-1.5 px-3 h-8 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-750 border border-slate-300 dark:border-slate-600 rounded-sm text-xs font-semibold text-slate-700 dark:text-slate-200 transition-all cursor-pointer">
                        <i class="fa-solid fa-pencil text-[10px]"></i> Edit Part
                    </button>
                    <button type="button" id="btn-delete-item"
                            class="inline-flex items-center justify-center gap-1.5 px-3 h-8 bg-rose-50 dark:bg-rose-950/20 hover:bg-rose-100/55 dark:hover:bg-rose-900/30 border border-rose-200 dark:border-rose-900 rounded-sm text-xs font-semibold text-rose-700 dark:text-rose-400 transition-all cursor-pointer">
                        <i class="fa-solid fa-trash text-[10px]"></i> Delete Part
                    </button>
                </div>
            </div>

            {{-- Inside Detail Tabs Navigation (Microsoft Pivot Style) --}}
            <div class="border-b border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-5 flex-shrink-0">
                <nav class="flex gap-4" id="detail-card-tabs">
                    <button type="button" data-tab="specs"
                            class="card-tab-btn active-tab py-2.5 text-xs font-semibold border-b-2 border-indigo-600 text-indigo-600 dark:text-indigo-400 -mb-px transition-all">
                        Part Specs & Material
                    </button>
                    <button type="button" data-tab="tooling"
                            class="card-tab-btn py-2.5 text-xs font-semibold border-b-2 border-transparent text-slate-650 hover:text-slate-800 dark:hover:text-slate-300 -mb-px transition-all">
                        Tooling Process (<span id="tooling-badge-count">0</span>)
                    </button>
                    <button type="button" data-tab="addprocess"
                            class="card-tab-btn py-2.5 text-xs font-semibold border-b-2 border-transparent text-slate-650 hover:text-slate-800 dark:hover:text-slate-300 -mb-px transition-all">
                        Add Process (<span id="addprocess-badge-count">0</span>)
                    </button>
                </nav>
            </div>

            {{-- Tab Contents --}}
            <div class="flex-1 overflow-y-auto p-5 min-h-0 bg-white dark:bg-slate-800">
                
                {{-- TAB: SPECS & MATERIAL --}}
                <div id="card-tab-specs" class="card-tab-panel space-y-6">
                    
                    {{-- Part Specs Grid --}}
                    <div>
                        <h4 class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3 border-b border-slate-300 dark:border-slate-600 pb-1.5">Part Dimensions & Quantities</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs">
                                <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">PART RANK</span>
                                <span id="info-part-rank" class="font-medium text-slate-900 dark:text-slate-100 text-sm">—</span>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs">
                                <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">PART STATUS</span>
                                <span id="info-part-status" class="font-medium text-slate-900 dark:text-slate-100 text-sm">—</span>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs">
                                <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">QTY/UNIT</span>
                                <span id="spec-qty-unit" class="font-medium text-slate-900 dark:text-slate-100 text-sm">—</span>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs">
                                <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">PCS/MONTH</span>
                                <span id="spec-pcs-month" class="font-medium text-slate-900 dark:text-slate-100 text-sm">—</span>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs">
                                <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">DIMENSIONS (W×L×H mm)</span>
                                <span id="spec-dimensions" class="font-medium text-slate-900 dark:text-slate-100 text-sm">—</span>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs">
                                <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">WEIGHT (kg)</span>
                                <span id="spec-weight" class="font-medium text-slate-900 dark:text-slate-100 text-sm">—</span>
                            </div>
                        </div>
                    </div>

                    {{-- Material Specifications --}}
                    <div>
                        <h4 class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3 border-b border-slate-300 dark:border-slate-600 pb-1.5">Material Details</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs">
                                <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">MATERIAL SPEC</span>
                                <span id="mat-spec" class="font-semibold text-emerald-700 dark:text-emerald-300 text-sm">—</span>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs">
                                <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">THICKNESS (mm)</span>
                                <span id="mat-thick" class="font-medium text-slate-900 dark:text-slate-100 text-sm font-mono">—</span>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs">
                                <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">SHEET WIDTH × LENGTH</span>
                                <span id="mat-size" class="font-medium text-slate-900 dark:text-slate-100 text-sm font-mono">—</span>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs">
                                <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">PCS/SHEET</span>
                                <span id="mat-pcs-sheet" class="font-medium text-slate-900 dark:text-slate-100 text-sm font-mono">—</span>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs">
                                <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">WEIGHT/PCS</span>
                                <span id="mat-weight-pcs" class="font-medium text-slate-900 dark:text-slate-100 text-sm font-mono">—</span>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs">
                                <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">YIELD RATIO</span>
                                <span id="mat-yield" class="font-semibold text-indigo-650 dark:text-indigo-400 text-sm">—</span>
                            </div>
                        </div>
                    </div>

                    {{-- Packing & Standard Parts --}}
                    <div>
                        <h4 class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3 border-b border-slate-300 dark:border-slate-600 pb-1.5">Packing & Standard Parts</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs">
                                <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">PACKING TYPE</span>
                                <span id="pack-type" class="font-medium text-slate-900 dark:text-slate-100 text-sm">—</span>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs">
                                <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">PCS/PACK</span>
                                <span id="pack-pcs" class="font-medium text-slate-900 dark:text-slate-100 text-sm">—</span>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs">
                                <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">STD PART NO</span>
                                <span id="std-part-no" class="font-medium text-slate-900 dark:text-slate-100 text-sm font-mono">—</span>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs">
                                <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">STD PART QTY</span>
                                <span id="std-qty" class="font-medium text-slate-900 dark:text-slate-100 text-sm">—</span>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- TAB: TOOLING PROCESSES --}}
                <div id="card-tab-tooling" class="card-tab-panel hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700 font-bold text-slate-500 uppercase tracking-wider text-[10px]">
                                    <th class="p-2 w-8">#</th>
                                    <th class="p-2">Rank</th>
                                    <th class="p-2">Cat</th>
                                    <th class="p-2">OP</th>
                                    <th class="p-2">Process Name</th>
                                    <th class="p-2">Homeline</th>
                                    <th class="p-2 text-right">Tonnage</th>
                                    <th class="p-2 text-right">Die Ht</th>
                                    <th class="p-2 text-center">Cav</th>
                                    <th class="p-2 text-center">Qty</th>
                                    <th class="p-2 text-right">Price</th>
                                    <th class="p-2">Status</th>
                                </tr>
                            </thead>
                            <tbody id="tooling-tbody" class="divide-y divide-slate-100 dark:divide-slate-700/50">
                                {{-- Filled via JS --}}
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- TAB: ADD PROCESSES --}}
                <div id="card-tab-addprocess" class="card-tab-panel hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700 font-bold text-slate-500 uppercase tracking-wider text-[10px]">
                                    <th class="p-2 w-8">#</th>
                                    <th class="p-2">Process Name</th>
                                    <th class="p-2 text-center">Qty</th>
                                    <th class="p-2 text-center">Unit</th>
                                    <th class="p-2 text-right">Cost (IDR)</th>
                                </tr>
                            </thead>
                            <tbody id="addprocess-tbody" class="divide-y divide-slate-100 dark:divide-slate-700/50">
                                {{-- Filled via JS --}}
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

    </div>

</div>

<x-sweetalert />

@push('scripts')
<script>
$(function () {
    // 1. Raw database elements rendered inside a local JS lookup object to eliminate latency
    const rawItemsData = @json($ebdHeader->items->load(['toolingProcesses', 'addProcesses']));
    const itemsLookup = {};
    rawItemsData.forEach(item => {
        itemsLookup[item.id] = item;
    });

    // 2. Tab switching logic inside the right details card
    $('.card-tab-btn').on('click', function () {
        const target = $(this).data('tab');
        
        $('.card-tab-btn').removeClass('active-tab border-indigo-600 text-indigo-600 dark:text-indigo-400')
                          .addClass('border-transparent text-slate-500');
        $(this).addClass('active-tab border-indigo-600 text-indigo-600 dark:text-indigo-400')
               .removeClass('border-transparent text-slate-500');

        $('.card-tab-panel').addClass('hidden');
        $('#card-tab-' + target).removeClass('hidden');
    });

    // 3. Selection handler for BOM Tree items
    $('.bom-item-row').on('click', function () {
        const itemId = $(this).data('item-id');
        selectBomItem(itemId);
    });

    function selectBomItem(itemId) {
        const item = itemsLookup[itemId];
        if (!item) return;

        // Highlight selected left panel item
        $('.bom-item-row').removeClass('bg-indigo-50/60 dark:bg-indigo-950/20 border-indigo-200 dark:border-indigo-800 text-indigo-900 dark:text-indigo-200');
        $(`.bom-item-row[data-item-id="${itemId}"]`).addClass('bg-indigo-50/60 dark:bg-indigo-950/20 border-indigo-200 dark:border-indigo-800 text-indigo-900 dark:text-indigo-200');

        // Populate Identifiers
        $('#active-part-no').text(item.part_no || '—');
        $('#active-part-name').text(item.part_name || '—');
        
        // Populate Rank and Status in information panel grid
        $('#info-part-rank').text(item.part_rank || '—');
        $('#info-part-status').text(item.status || '—');

        // TAB 1: Specs & Material Spec
        $('#spec-qty-unit').text(item.qty_unit ?? 1);
        $('#spec-pcs-month').text(item.pcs_month ? Number(item.pcs_month).toLocaleString('id-ID') : '0');
        
        const w = item.width ? Number(item.width).toFixed(1) : '0';
        const l = item.length ? Number(item.length).toFixed(1) : '0';
        const h = item.height ? Number(item.height).toFixed(1) : '0';
        $('#spec-dimensions').text(`${w} × ${l} × ${h}`);
        $('#spec-weight').text(item.weight ? Number(item.weight).toFixed(3) : '0.000');

        $('#mat-spec').text(item.mat_spec || '—');
        $('#mat-thick').text(item.mat_thick ? Number(item.mat_thick).toFixed(2) + ' mm' : '—');
        
        const mw = item.mat_width ? Number(item.mat_width).toFixed(0) : '0';
        const ml = item.mat_length ? Number(item.mat_length).toFixed(0) : '0';
        $('#mat-size').text(`${mw} × ${ml} mm`);
        
        $('#mat-pcs-sheet').text(item.mat_pcs_sheet ?? '—');
        $('#mat-weight-pcs').text(item.mat_weight_pcs ? Number(item.mat_weight_pcs).toFixed(3) + ' kg' : '—');
        $('#mat-yield').text(item.mat_yield_ratio ? Number(item.mat_yield_ratio).toFixed(2) + '%' : '—');

        $('#pack-type').text(item.packing_type || '—');
        $('#pack-pcs').text(item.pcs_packing ? Number(item.pcs_packing).toLocaleString('id-ID') : '—');
        $('#std-part-no').text(item.std_part_no || '—');
        $('#std-qty').text(item.std_qty || '—');

        // Tooling list count badge
        const toolings = item.tooling_processes || [];
        $('#tooling-badge-count').text(toolings.length);

        // Tooling process table render
        let toolingHtml = '';
        if (toolings.length > 0) {
            toolings.forEach((tp, idx) => {
                const tonnageVal = tp.tonnage !== null ? tp.tonnage + ' T' : '—';
                const heightVal = tp.die_height !== null ? Number(tp.die_height).toFixed(1) + ' mm' : '—';
                const priceVal = tp.price_idr ? 'Rp ' + Number(tp.price_idr).toLocaleString('id-ID') : '—';
                toolingHtml += `
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30">
                        <td class="p-2 text-slate-400 font-mono text-[10px]">${idx + 1}</td>
                        <td class="p-2 font-semibold text-slate-600 dark:text-slate-400">${tp.tool_rank || '—'}</td>
                        <td class="p-2 text-slate-600 dark:text-slate-400">${tp.category || '—'}</td>
                        <td class="p-2 font-bold text-rose-600 dark:text-rose-400">${tp.op || '—'}</td>
                        <td class="p-2 font-semibold text-slate-800 dark:text-slate-200">${tp.process_name || '—'}</td>
                        <td class="p-2 text-slate-600 dark:text-slate-400">${tp.prod_homeline || '—'}</td>
                        <td class="p-2 text-right font-mono">${tonnageVal}</td>
                        <td class="p-2 text-right font-mono">${heightVal}</td>
                        <td class="p-2 text-center">${tp.cavity ?? 1}</td>
                        <td class="p-2 text-center">${tp.qty ?? 1}</td>
                        <td class="p-2 text-right font-bold font-mono text-slate-800 dark:text-slate-300">${priceVal}</td>
                        <td class="p-2">
                            ${tp.tooling_status ? `<span class="px-1.5 py-0.5 text-[9px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 rounded">${tp.tooling_status}</span>` : '—'}
                        </td>
                    </tr>
                `;
            });
        } else {
            toolingHtml = '<tr><td colspan="12" class="p-4 text-center text-slate-400">No tooling process data.</td></tr>';
        }
        $('#tooling-tbody').html(toolingHtml);

        // Add process list count badge
        const addProcesses = item.add_processes || [];
        $('#addprocess-badge-count').text(addProcesses.length);

        // Add process table render
        let addProcessHtml = '';
        if (addProcesses.length > 0) {
            addProcesses.forEach((ap, idx) => {
                const costVal = ap.cost_idr ? 'Rp ' + Number(ap.cost_idr).toLocaleString('id-ID') : '—';
                addProcessHtml += `
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30">
                        <td class="p-2 text-slate-400 font-mono text-[10px]">${idx + 1}</td>
                        <td class="p-2 font-bold text-slate-800 dark:text-slate-200">${ap.process_name || '—'}</td>
                        <td class="p-2 text-center">${ap.qty ?? 0}</td>
                        <td class="p-2 text-center font-mono text-slate-400">${ap.unit ?? 'pcs'}</td>
                        <td class="p-2 text-right font-bold text-teal-600 dark:text-teal-400 font-mono">${costVal}</td>
                    </tr>
                `;
            });
        } else {
            addProcessHtml = '<tr><td colspan="5" class="p-4 text-center text-slate-400">No additional process data.</td></tr>';
        }
        $('#addprocess-tbody').html(addProcessHtml);
    }

        }
        $('#addprocess-tbody').html(addProcessHtml);
    }

    // 4. Automatically select the first BOM item on load
    const firstRow = $('.bom-item-row').first();
    if (firstRow.length > 0) {
        selectBomItem(firstRow.data('item-id'));
    }
});
</script>

{{-- ===== EDIT HEADER METADATA MODAL ===== --}}
<div id="edit-header-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-2xl w-full max-w-lg mx-4 animate-fade-in text-xs text-slate-800 dark:text-slate-100">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-700">
            <div>
                <h2 class="text-sm font-bold text-slate-850 dark:text-white">Edit EBD Info</h2>
                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Update Engineering Breakdown header information</p>
            </div>
            <button type="button" id="btn-close-edit-header-modal"
                    class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-450 hover:text-slate-600 hover:bg-slate-105 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <form id="form-edit-header" action="{{ route('management.ebd.update', $ebdHeader->id) }}" method="POST">
            @csrf
            <div class="px-5 py-4 space-y-4">
                {{-- WO Selection --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                        Work Order (SPK)
                    </label>
                    <select name="wo_id" class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        <option value="">— No Work Order —</option>
                        @foreach($workOrders as $wo)
                            <option value="{{ $wo->id }}" {{ $ebdHeader->wo_id == $wo->id ? 'selected' : '' }}>{{ $wo->wo_number }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Customer --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                        Customer
                    </label>
                    <select name="customer_id" class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        <option value="">— Select Customer —</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ $ebdHeader->customer_id == $customer->id ? 'selected' : '' }}>{{ $customer->code }} — {{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Model --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                        Model
                    </label>
                    <select name="model_id" class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        <option value="">— Select Model —</option>
                        @foreach($models as $model)
                            <option value="{{ $model->id }}" {{ $ebdHeader->model_id == $model->id ? 'selected' : '' }}>{{ $model->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Date, Revision & Status --}}
                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-1">
                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                            EBD Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="date" required value="{{ $ebdHeader->date ? $ebdHeader->date->format('Y-m-d') : date('Y-m-d') }}"
                               class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                            Revision
                        </label>
                        <input type="text" name="revision" value="{{ $ebdHeader->revision }}" placeholder="e.g. 0"
                               class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                            Status <span class="text-rose-500">*</span>
                        </label>
                        <select name="status" required class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                            <option value="Draft" {{ $ebdHeader->status === 'Draft' ? 'selected' : '' }}>Draft</option>
                            <option value="Released" {{ $ebdHeader->status === 'Released' ? 'selected' : '' }}>Released</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="px-5 py-3.5 bg-slate-50 dark:bg-slate-900/30 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-2 rounded-b-xl">
                <button type="button" id="btn-cancel-edit-header"
                        class="px-4 py-1.5 text-xs font-semibold text-slate-650 dark:text-slate-300 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-1.5 text-xs font-bold text-white bg-indigo-650 hover:bg-indigo-700 rounded-lg shadow-sm hover:shadow transition-all flex items-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-floppy-disk text-[10px]"></i>
                    <span>Save Changes</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ===== IMPORT ITEMS MODAL (OVERWRITE) ===== --}}
<div id="import-items-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-2xl w-full max-w-lg mx-4 animate-fade-in text-xs">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-700">
            <div>
                <h2 class="text-sm font-bold text-slate-850 dark:text-white">Import EBD BOM Items</h2>
                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Upload XLSX file to overwrite current EBD BOM items</p>
            </div>
            <button type="button" id="btn-close-import-items-modal"
                    class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-450 hover:text-slate-600 hover:bg-slate-105 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <form id="form-import-items" enctype="multipart/form-data">
            @csrf
            <div class="px-5 py-4 space-y-4">
                <div class="p-3 bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/30 text-amber-800 dark:text-amber-300 rounded text-[11px] leading-relaxed">
                    <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                    <strong>Warning:</strong> Importing items will completely <strong>overwrite</strong> and delete all current BOM items, tooling processes, and add-processes in this EBD.
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                        EBD File (XLSX) <span class="text-rose-500">*</span>
                    </label>
                    <div id="drop-zone" class="relative border-2 border-dashed border-slate-350 dark:border-slate-600 rounded-lg p-5 text-center cursor-pointer hover:border-indigo-400 hover:bg-indigo-50/30 dark:hover:bg-indigo-950/20 transition-all group">
                        <input type="file" name="file_ebd" id="input-file-ebd" required accept=".xlsx"
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                        <div id="drop-zone-content">
                            <i class="fa-solid fa-cloud-arrow-up text-2xl text-slate-300 dark:text-slate-600 group-hover:text-indigo-400 transition-colors mb-2 block"></i>
                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                                Drop your XLSX file here, or <span class="text-indigo-500">browse</span>
                            </p>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">Accepts .xlsx (max 20 MB)</p>
                        </div>
                        <div id="file-selected-info" class="hidden">
                            <i class="fa-solid fa-file-excel text-2xl text-emerald-500 mb-1 block"></i>
                            <p class="text-xs font-bold text-slate-700 dark:text-slate-200" id="file-selected-name"></p>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500" id="file-selected-size"></p>
                        </div>
                    </div>
                </div>

                <div id="importResult" class="hidden text-[10px] rounded border overflow-y-auto max-h-40 p-3 bg-rose-50 text-rose-900 border-rose-100"></div>
            </div>

            <div class="px-5 py-3.5 bg-slate-50 dark:bg-slate-900/30 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-2 rounded-b-xl">
                <button type="button" id="btn-cancel-import-items"
                        class="px-4 py-1.5 text-xs font-semibold text-slate-655 dark:text-slate-300 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-105 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                    Cancel
                </button>
                <button type="submit" id="btn-submit-import"
                        class="px-4 py-1.5 text-xs font-bold text-white bg-indigo-650 hover:bg-indigo-700 rounded-lg shadow-sm hover:shadow transition-all flex items-center gap-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                    <i class="fa-solid fa-file-import text-[10px]"></i>
                    <span id="btn-submit-text">Start Overwrite</span>
                    <span id="btn-submit-spinner" class="hidden">
                        <i class="fa-solid fa-spinner animate-spin text-[10px]"></i>
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ===== ADD/EDIT EBD ITEM (PART) MODAL ===== --}}
<div id="item-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-2xl w-full max-w-2xl mx-4 animate-fade-in text-xs text-slate-800 dark:text-slate-100 overflow-hidden flex flex-col max-h-[90vh]">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-700 flex-shrink-0">
            <div>
                <h2 id="item-modal-title" class="text-sm font-bold text-slate-850 dark:text-white">Add EBD Item (Part)</h2>
                <p id="item-modal-subtitle" class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Add a new part to this Engineering Breakdown document</p>
            </div>
            <button type="button" id="btn-close-item-modal"
                    class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-455 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <form id="form-item-ebd" class="flex flex-col min-h-0">
            @csrf
            <div class="px-5 py-4 space-y-4 overflow-y-auto flex-1">
                {{-- Part Identification --}}
                <div>
                    <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2 border-b pb-1">Part Identification</h4>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 uppercase mb-1">Part Number <span class="text-rose-500">*</span></label>
                            <input type="text" name="part_no" required placeholder="e.g. 555-ABC-001"
                                   class="w-full px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 uppercase mb-1">Part Name <span class="text-rose-500">*</span></label>
                            <input type="text" name="part_name" required placeholder="e.g. PANEL ASSY"
                                   class="w-full px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all">
                        </div>
                    </div>
                </div>

                {{-- Hierarchy & Levels --}}
                <div>
                    <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2 border-b pb-1">Hierarchy & Structure</h4>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 uppercase mb-1">BOM Level <span class="text-rose-500">*</span></label>
                            <input type="number" name="level_aktif" required min="1" max="10" value="1"
                                   class="w-full px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all font-mono">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-[10px] font-semibold text-slate-500 uppercase mb-1">Parent Part</label>
                            <select name="parent_id" id="input-parent-id"
                                    class="w-full px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all">
                                <option value="">— None (Root Part) —</option>
                                @foreach($allItems as $itm)
                                    <option value="{{ $itm->id }}">Level {{ $itm->level_aktif }} — {{ $itm->part_no }} ({{ $itm->part_name }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Quantities, Ranks, Sizes --}}
                <div>
                    <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2 border-b pb-1">Part Dimensions & Quantities</h4>
                    <div class="grid grid-cols-4 gap-3">
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 uppercase mb-1">Qty/Unit</label>
                            <input type="number" name="qty_unit" min="1" value="1"
                                   class="w-full px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 uppercase mb-1">Pcs/Month</label>
                            <input type="number" name="pcs_month"
                                   class="w-full px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 uppercase mb-1">Part Rank</label>
                            <input type="text" name="part_rank" placeholder="e.g. A"
                                   class="w-full px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 uppercase mb-1">Part Status</label>
                            <input type="text" name="status" placeholder="e.g. Active"
                                   class="w-full px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all">
                        </div>
                    </div>
                    <div class="grid grid-cols-4 gap-3 mt-3">
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 uppercase mb-1">Width (mm)</label>
                            <input type="number" step="0.01" name="width"
                                   class="w-full px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all font-mono">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 uppercase mb-1">Length (mm)</label>
                            <input type="number" step="0.01" name="length"
                                   class="w-full px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all font-mono">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 uppercase mb-1">Height (mm)</label>
                            <input type="number" step="0.01" name="height"
                                   class="w-full px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all font-mono">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 uppercase mb-1">Weight (kg)</label>
                            <input type="number" step="0.001" name="weight"
                                   class="w-full px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all font-mono">
                        </div>
                    </div>
                </div>

                {{-- Material Specs --}}
                <div>
                    <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2 border-b pb-1">Material Details</h4>
                    <div class="grid grid-cols-4 gap-3">
                        <div class="col-span-2">
                            <label class="block text-[10px] font-semibold text-slate-500 uppercase mb-1">Material Spec</label>
                            <input type="text" name="mat_spec" placeholder="e.g. SPHC-P"
                                   class="w-full px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all font-semibold text-indigo-700">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 uppercase mb-1">Thickness (mm)</label>
                            <input type="number" step="0.01" name="mat_thick"
                                   class="w-full px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all font-mono">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 uppercase mb-1">Yield Ratio (%)</label>
                            <input type="number" step="0.01" name="mat_yield_ratio"
                                   class="w-full px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all font-mono">
                        </div>
                    </div>
                    <div class="grid grid-cols-4 gap-3 mt-3">
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 uppercase mb-1">Sheet Width</label>
                            <input type="number" step="0.01" name="mat_width"
                                   class="w-full px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded font-mono">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 uppercase mb-1">Sheet Length</label>
                            <input type="number" step="0.01" name="mat_length"
                                   class="w-full px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded font-mono">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 uppercase mb-1">Pcs/Sheet</label>
                            <input type="number" name="mat_pcs_sheet"
                                   class="w-full px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded font-mono">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 uppercase mb-1">Weight/Pcs</label>
                            <input type="number" step="0.001" name="mat_weight_pcs"
                                   class="w-full px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded font-mono">
                        </div>
                    </div>
                </div>

                {{-- Pack, Std Parts, Transport --}}
                <div>
                    <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2 border-b pb-1">Packing & Transport</h4>
                    <div class="grid grid-cols-4 gap-3">
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 uppercase mb-1">Packing Type</label>
                            <input type="text" name="packing_type" placeholder="e.g. Box"
                                   class="w-full px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 uppercase mb-1">Pcs/Packing</label>
                            <input type="number" name="pcs_packing"
                                   class="w-full px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded font-mono">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 uppercase mb-1">Part Vol (m2)</label>
                            <input type="number" step="0.0001" name="part_vol_m2"
                                   class="w-full px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded font-mono">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 uppercase mb-1">Truck Vol (m2)</label>
                            <input type="number" step="0.0001" name="truck_vol_m2"
                                   class="w-full px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded font-mono">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 mt-3">
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 uppercase mb-1">Std Part No</label>
                            <input type="text" name="std_part_no"
                                   class="w-full px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded font-mono">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 uppercase mb-1">Std Qty</label>
                            <input type="number" name="std_qty"
                                   class="w-full px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded font-mono">
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-5 py-3.5 bg-slate-50 dark:bg-slate-900/30 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-2 flex-shrink-0">
                <button type="button" id="btn-cancel-item"
                        class="px-4 py-1.5 text-xs font-semibold text-slate-655 dark:text-slate-300 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-105 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-1.5 text-xs font-bold text-white bg-emerald-650 hover:bg-emerald-700 rounded-lg shadow-sm hover:shadow transition-all flex items-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-floppy-disk text-[10px]"></i>
                    <span id="btn-submit-item-text">Save Part</span>
                </button>
            </div>
        </form>
    </div>
</div>

<x-sweetalert />

@push('scripts')
<script>
$(function () {
    const WO_BASE_URL = '{{ url('management') }}';
    const EBD_HEADER_ID = '{{ $ebdHeader->id }}';
    window.selectedItemId = null;
    window.selectedItemData = null;

    // Trigger select item and toggle edit/delete action visibility
    const originalSelectBomItem = window.selectBomItem;
    window.selectBomItem = function(id) {
        if (typeof originalSelectBomItem === 'function') {
            originalSelectBomItem(id);
        }
        window.selectedItemId = id;
        window.selectedItemData = itemsLookup[id] || null;
        if (window.selectedItemId) {
            $('#active-part-actions').removeClass('hidden');
        } else {
            $('#active-part-actions').addClass('hidden');
        }
    };

    // Edit Header Metadata Modal triggers
    $('#btn-open-edit-header-modal').on('click', function() {
        $('#edit-header-modal').removeClass('hidden').addClass('flex');
    });
    $('#btn-close-edit-header-modal, #btn-cancel-edit-header').on('click', function() {
        $('#edit-header-modal').addClass('hidden').removeClass('flex');
    });

    // Import Items Modal triggers
    $('#btn-open-import-items-modal').on('click', function() {
        $('#import-items-modal').removeClass('hidden').addClass('flex');
    });
    $('#btn-close-import-items-modal, #btn-cancel-import-items').on('click', function() {
        $('#import-items-modal').addClass('hidden').removeClass('flex');
        $('#form-import-items')[0].reset();
        $('#drop-zone-content').removeClass('hidden');
        $('#file-selected-info').addClass('hidden');
        $('#importResult').addClass('hidden').html('');
    });

    // File Selection in Dropzone
    $('#input-file-ebd').on('change', function () {
        const file = this.files[0];
        if (!file) {
            $('#drop-zone-content').removeClass('hidden');
            $('#file-selected-info').addClass('hidden');
            return;
        }
        const sizeMB = (file.size / 1024 / 1024).toFixed(2);
        $('#drop-zone-content').addClass('hidden');
        $('#file-selected-info').removeClass('hidden');
        $('#file-selected-name').text(file.name);
        $('#file-selected-size').text(sizeMB + ' MB');
    });

    // AJAX — Overwrite BOM items
    $('#form-import-items').on('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        $('#btn-submit-text').text('Overwriting BOM...');
        $('#btn-submit-spinner').removeClass('hidden');
        $('#btn-submit-import').prop('disabled', true);

        $.ajax({
            url: `${WO_BASE_URL}/ebd/${EBD_HEADER_ID}/import-items`,
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(res) {
                showToast(res.message || 'BOM items overwrited successfully.', 'success');
                setTimeout(() => window.location.reload(), 1200);
            },
            error: function(xhr) {
                $('#btn-submit-text').text('Start Overwrite');
                $('#btn-submit-spinner').addClass('hidden');
                $('#btn-submit-import').prop('disabled', false);

                const err = xhr.responseJSON;
                let errorHtml = `<p class="font-bold mb-1">${err?.message || 'Error occurred during overwrite'}</p>`;
                if (err?.errors && err.errors.length > 0) {
                    errorHtml += '<ul class="list-disc pl-4 space-y-1">';
                    err.errors.forEach(e => {
                        errorHtml += `<li>Row ${e.row || '?'}: ${e.message || e}</li>`;
                    });
                    errorHtml += '</ul>';
                }
                $('#importResult').removeClass('hidden').html(errorHtml);
                showToast('Overwrite failed.', 'error');
            }
        });
    });

    // Add EBD Item Modal triggers
    $('#btn-open-add-item-modal').on('click', function() {
        $('#form-item-ebd')[0].reset();
        $('#input-parent-id').val('');
        $('#item-modal-title').text('Add EBD Item (Part)');
        $('#item-modal-subtitle').text('Add a new part to this Engineering Breakdown document');
        $('#btn-submit-item-text').text('Save Part');
        window.tempEditItemId = null;
        $('#item-modal').removeClass('hidden').addClass('flex');
    });

    // Edit Part trigger
    $('#btn-edit-item').on('click', function() {
        if (!window.selectedItemData) return;
        const d = window.selectedItemData;
        window.tempEditItemId = d.id;

        // Populate fields
        const f = $('#form-item-ebd');
        f.find('input[name="part_no"]').val(d.part_no);
        f.find('input[name="part_name"]').val(d.part_name);
        f.find('input[name="level_aktif"]').val(d.level_aktif);
        f.find('select[name="parent_id"]').val(d.parent_id || '');
        f.find('input[name="qty_unit"]').val(d.qty_unit);
        f.find('input[name="pcs_month"]').val(d.pcs_month);
        f.find('input[name="part_rank"]').val(d.part_rank);
        f.find('input[name="status"]').val(d.status);
        f.find('input[name="width"]').val(d.width);
        f.find('input[name="length"]').val(d.length);
        f.find('input[name="height"]').val(d.height);
        f.find('input[name="weight"]').val(d.weight);

        f.find('input[name="mat_spec"]').val(d.mat_spec);
        f.find('input[name="mat_thick"]').val(d.mat_thick);
        f.find('input[name="mat_width"]').val(d.mat_width);
        f.find('input[name="mat_length"]').val(d.mat_length);
        f.find('input[name="mat_pcs_sheet"]').val(d.mat_pcs_sheet);
        f.find('input[name="mat_weight_pcs"]').val(d.mat_weight_pcs);
        f.find('input[name="mat_yield_ratio"]').val(d.mat_yield_ratio);

        f.find('input[name="packing_type"]').val(d.packing_type);
        f.find('input[name="pcs_packing"]').val(d.pcs_packing);
        f.find('input[name="part_vol_m2"]').val(d.part_vol_m2);
        f.find('input[name="truck_vol_m2"]').val(d.truck_vol_m2);
        f.find('input[name="std_part_no"]').val(d.std_part_no);
        f.find('input[name="std_qty"]').val(d.std_qty);

        $('#item-modal-title').text('Edit EBD Item (Part)');
        $('#item-modal-subtitle').text(`Update details for Part: ${d.part_no}`);
        $('#btn-submit-item-text').text('Update Part');
        $('#item-modal').removeClass('hidden').addClass('flex');
    });

    $('#btn-close-item-modal, #btn-cancel-item').on('click', function() {
        $('#item-modal').addClass('hidden').removeClass('flex');
    });

    // Save/Update EBD Item form submit
    $('#form-item-ebd').on('submit', function(e) {
        e.preventDefault();
        const isEdit = !!window.tempEditItemId;
        const targetUrl = isEdit 
            ? `${WO_BASE_URL}/ebd-item/${window.tempEditItemId}/update`
            : `${WO_BASE_URL}/ebd/${EBD_HEADER_ID}/item`;

        $.ajax({
            url: targetUrl,
            type: 'POST',
            data: $(this).serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(res) {
                showToast(res.message || 'Part saved successfully.', 'success');
                setTimeout(() => window.location.reload(), 1200);
            },
            error: function(xhr) {
                showToast(xhr.responseJSON?.message || 'Failed to save part data.', 'error');
            }
        });
    });

    // Delete Part action
    $('#btn-delete-item').on('click', function() {
        if (!window.selectedItemId) return;
        confirmDialog({
            title: 'Delete Part?',
            text: 'Are you sure you want to delete this part from the BOM? This action is permanent.',
            icon: 'warning',
            confirmButtonText: 'Yes, Delete',
            confirmButtonColor: '#e11d48',
            onConfirm: function () {
                $.ajax({
                    url: `${WO_BASE_URL}/ebd-item/${window.selectedItemId}/delete`,
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (res) {
                        showToast(res.message || 'Part deleted successfully.', 'success');
                        setTimeout(() => window.location.reload(), 1200);
                    },
                    error: function (xhr) {
                        showToast(xhr.responseJSON?.message || 'Failed to delete part.', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
@endsection