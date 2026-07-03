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
        <div class="pl-6 border-l border-slate-200 dark:border-slate-700 flex-shrink-0">
            @php
                $statusCls = match($ebdHeader->status) {
                    'Released' => 'bg-emerald-50 text-emerald-700 border-emerald-250 dark:bg-emerald-950/30 dark:text-emerald-450 dark:border-emerald-900/30',
                    default    => 'bg-blue-55 text-blue-700 border-blue-200 dark:bg-blue-950/30 dark:text-blue-450 dark:border-blue-900/30',
                };
            @endphp
            <span class="px-2.5 py-1 text-xs font-semibold border rounded-xs tracking-wide {{ $statusCls }}">
                {{ $ebdHeader->status ?? 'Draft' }}
            </span>
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

    // 4. Automatically select the first BOM item on load
    const firstRow = $('.bom-item-row').first();
    if (firstRow.length > 0) {
        selectBomItem(firstRow.data('item-id'));
    }
});
</script>
@endpush
@endsection