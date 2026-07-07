@extends('layouts.app')

@section('title', 'EBD Detail · Promise Management')
@section('page_title', 'EBD Detail')
@section('header-title', 'Engineering Breakdown (EBD)')

@section('content')
<div class="flex h-[calc(100vh-64px)] mt-16 overflow-hidden bg-white dark:bg-slate-900 flex-col border-t border-slate-300 dark:border-slate-800">

    {{-- ===== METADATA BAR ===== --}}
    <div class="flex items-center gap-6 px-6 py-3 bg-slate-100 dark:bg-slate-900 border-b border-slate-300 dark:border-slate-800 flex-shrink-0 text-xs text-slate-800 dark:text-slate-100">
        {{-- Back Action & Revision info --}}
        <div class="flex flex-col gap-1.5 pr-6 border-r border-slate-300 dark:border-slate-700 flex-shrink-0">
            <div class="flex gap-1.5">
                <a href="{{ route('management.ebd.index') }}"
                   class="inline-flex items-center justify-center gap-2 px-3 h-8 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-750 border border-slate-300 dark:border-slate-600 rounded-xs text-xs font-semibold text-slate-750 dark:text-slate-250 transition-all active:scale-98">
                    <i class="fa-solid fa-arrow-left text-[10px]"></i> Back
                </a>
                <button type="button" id="btn-open-import-modal"
                        class="inline-flex items-center justify-center gap-2 px-3 h-8 bg-indigo-600 hover:bg-indigo-700 border border-transparent rounded-xs text-xs font-semibold text-white transition-all active:scale-98 cursor-pointer shadow-none">
                    <i class="fa-solid fa-file-import text-[10px]"></i> Import EBD
                </button>
            </div>
            <span class="text-[11px] font-mono font-bold text-slate-650 dark:text-slate-350 uppercase tracking-wider mt-0.5">
                Revision: {{ $ebdHeader->revision }}
            </span>
        </div>

        {{-- Metadata Grid --}}
        <div class="flex-1 grid grid-cols-2 md:grid-cols-4 gap-6">
            <div>
                <span class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-0.5">WO Number</span>
                <span class="font-medium text-slate-850 dark:text-slate-100 text-xs">{{ $ebdHeader->workOrder->wo_number ?? '—' }}</span>
            </div>
            <div>
                <span class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-0.5">Customer</span>
                <span class="font-medium text-slate-850 dark:text-slate-100 text-xs">
                    {{ $ebdHeader->customer->name ?? '—' }} 
                    @if(isset($ebdHeader->customer->code))
                        ({{ $ebdHeader->customer->code }})
                    @endif
                </span>
            </div>
            <div>
                <span class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-0.5">Project Model</span>
                <span class="font-medium text-slate-850 dark:text-slate-100 text-xs">{{ $ebdHeader->projectModel->name ?? '—' }}</span>
            </div>
            <div>
                <span class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-0.5">EBD Date</span>
                <span class="font-mono text-slate-900 dark:text-slate-100 font-medium text-xs">{{ $ebdHeader->date ? $ebdHeader->date->format('d M Y') : '—' }}</span>
            </div>
        </div>

        {{-- Global Status --}}
        <div class="pl-6 border-l border-slate-300 dark:border-slate-700 flex-shrink-0">
            @php
                $statusCls = match($ebdHeader->status) {
                    'Released' => 'bg-emerald-100/70 text-emerald-700 border-emerald-350/60 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-900/30',
                    default    => 'bg-blue-100/70 text-blue-700 border-blue-350/60 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-900/30',
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
        <div class="w-[30%] max-w-[360px] min-w-[280px] flex flex-col bg-slate-100/50 dark:bg-slate-900/30 border-r border-slate-300 overflow-hidden h-full">
            <div class="px-4 py-3 bg-slate-100/50 dark:bg-slate-900/80 border-b border-slate-300 flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-200 flex items-center gap-2">
                    <i class="fa-solid fa-sitemap text-indigo-550 text-[10px]"></i> BOM List
                </span>
                <div class="flex items-center gap-1.5">
                    <button type="button" id="btn-add-root-part" title="Add Root Part"
                            class="w-6 h-6 flex items-center justify-center bg-white hover:bg-slate-100 dark:bg-slate-900 dark:hover:bg-slate-750 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 rounded-xs transition-colors cursor-pointer">
                        <i class="fa-solid fa-plus text-[10px]"></i>
                    </button>
                    <span class="text-[10px] bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-100 px-2 py-0.5 rounded-xs font-mono font-semibold">
                        {{ $ebdHeader->items->count() }} items
                    </span>
                </div>
            </div>
            
            <div class="flex-1 overflow-y-auto p-2 space-y-0.5">
                @php
                    $levels = [];
                @endphp
                @forelse ($ebdHeader->items as $item)
                    @php
                        $currentLevel = $item->bom_level ?? $item->active_level ?? 1;
                        
                        if (!isset($levels[$currentLevel])) {
                            $levels[$currentLevel] = 1;
                        } else {
                            $levels[$currentLevel]++;
                        }
                        
                        foreach ($levels as $lvl => $val) {
                            if ($lvl > $currentLevel) {
                                unset($levels[$lvl]);
                            }
                        }
                        
                        $outlineParts = [];
                        for ($i = 1; $i <= $currentLevel; $i++) {
                            $outlineParts[] = $levels[$i] ?? 1;
                        }
                        $outlineNumber = implode('.', $outlineParts);
                        
                        $depth = $currentLevel - 1;
                        $paddingLeft = $depth * 16;
                    @endphp
                    <div data-item-id="{{ $item->id }}"
                         data-outline="{{ $outlineNumber }}"
                         class="bom-item-row group flex items-center gap-2.5 p-2 rounded-xs cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700/50 border-l-2 border-transparent transition-all"
                         style="padding-left: {{ max($paddingLeft + 8, 8) }}px">
                        
                        <span class="flex-shrink-0 px-1.5 py-0.5 min-w-5 h-5 flex items-center justify-center rounded-xs text-[9px] font-bold bg-slate-200 dark:bg-slate-700 text-slate-750 dark:text-slate-300 group-hover:bg-slate-300 dark:group-hover:bg-slate-600 transition-colors">
                            {{ $outlineNumber }}
                        </span>
                        
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-mono font-semibold text-slate-800 dark:text-slate-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 truncate leading-none mb-1 transition-colors">
                                {{ $item->part_no }}
                            </p>
                            <p class="text-[10px] text-slate-600 dark:text-slate-400 truncate">
                                {{ $item->part_name }}
                            </p>
                        </div>
                        
                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity ml-1 flex-shrink-0">
                            <button type="button" class="btn-add-sub-part w-5 h-5 flex items-center justify-center bg-white dark:bg-slate-800 border border-slate-250 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-500 dark:text-slate-450 rounded-xs transition-colors cursor-pointer" title="Add sub-part" data-parent-id="{{ $item->id }}">
                                <i class="fa-solid fa-plus text-[9px]"></i>
                            </button>
                        </div>
                        
                        <i class="fa-solid fa-chevron-right text-[9px] text-slate-450 group-hover:text-slate-500 transition-colors flex-shrink-0"></i>
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
            <div class="px-5 py-4 bg-slate-100 dark:bg-slate-900/50 border-b border-slate-300 dark:border-slate-600 flex items-center justify-between flex-shrink-0">
                <div class="flex items-stretch gap-3">
                    <div id="active-part-outline" class="flex-shrink-0 px-2.5 flex items-center justify-center rounded-xs text-xs font-bold bg-slate-200 dark:bg-slate-700 text-slate-750 dark:text-slate-300 min-w-[54px]">
                        —
                    </div>
                    <div class="flex flex-col justify-center">
                        <h2 id="active-part-no" class="text-base font-mono font-bold text-slate-800 dark:text-slate-100 leading-tight">Select a part...</h2>
                        <p id="active-part-name" class="text-xs text-slate-500 dark:text-slate-450 mt-0.5 font-medium leading-normal">Click any part on the left BOM list to view details.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="btn-edit-specs" class="hidden w-8 h-8 flex items-center justify-center bg-white hover:bg-slate-100 border border-slate-300 dark:border-slate-700 text-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-750 rounded-xs transition-all cursor-pointer" title="Edit Specs">
                        <i class="fa-regular fa-pen-to-square text-[13px]"></i>
                    </button>
                    <button type="button" id="btn-delete-part" class="hidden w-8 h-8 flex items-center justify-center bg-white hover:bg-rose-50 border border-slate-300 dark:border-slate-700 text-rose-600 dark:bg-slate-900 dark:text-rose-400 dark:hover:bg-rose-950/20 rounded-xs transition-all cursor-pointer" title="Delete Part">
                        <i class="fa-regular fa-trash-can text-[13px]"></i>
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
                <div id="card-tab-specs" class="card-tab-panel">
                    <div class="flex flex-col lg:flex-row gap-6 items-start">
                        
                        {{-- Specs Details (Left Side) --}}
                        <div class="flex-1 space-y-6 w-full">
                            {{-- Part Specs Grid --}}
                            <div>
                                <h4 class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3 border-b border-slate-300 dark:border-slate-600 pb-1.5">Part Dimensions & Quantities</h4>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs">
                                    <div class="p-3 bg-slate-100/50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs">
                                        <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">PART RANK</span>
                                        <span id="info-part-rank" class="font-medium text-slate-900 dark:text-slate-100 text-sm">—</span>
                                    </div>
                                    <div class="p-3 bg-slate-100/50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs">
                                        <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">PART STATUS</span>
                                        <span id="info-part-status" class="font-medium text-slate-900 dark:text-slate-100 text-sm">—</span>
                                    </div>
                                    <div class="p-3 bg-slate-100/50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs">
                                        <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">QTY/UNIT</span>
                                        <span id="spec-qty-unit" class="font-medium text-slate-900 dark:text-slate-100 text-sm">—</span>
                                    </div>
                                    <div class="p-3 bg-slate-100/50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs">
                                        <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">PCS/MONTH</span>
                                        <span id="spec-pcs-month" class="font-medium text-slate-900 dark:text-slate-100 text-sm">—</span>
                                    </div>
                                    <div class="p-3 bg-slate-100/50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs col-span-2 sm:col-span-1">
                                        <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">DIMENSIONS (W×L×H mm)</span>
                                        <span id="spec-dimensions" class="font-medium text-slate-900 dark:text-slate-100 text-sm">—</span>
                                    </div>
                                    <div class="p-3 bg-slate-100/50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs">
                                        <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">WEIGHT (kg)</span>
                                        <span id="spec-weight" class="font-medium text-slate-900 dark:text-slate-100 text-sm">—</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Material Specifications --}}
                            <div>
                                <h4 class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3 border-b border-slate-300 dark:border-slate-600 pb-1.5">Material Details</h4>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs">
                                    <div class="p-3 bg-slate-100/50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs">
                                        <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">MATERIAL SPEC</span>
                                        <span id="mat-spec" class="font-semibold text-emerald-700 dark:text-emerald-300 text-sm">—</span>
                                    </div>
                                    <div class="p-3 bg-slate-100/50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs">
                                        <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">THICKNESS (mm)</span>
                                        <span id="mat-thick" class="font-medium text-slate-900 dark:text-slate-100 text-sm font-mono">—</span>
                                    </div>
                                    <div class="p-3 bg-slate-100/50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs">
                                        <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">SHEET WIDTH × LENGTH</span>
                                        <span id="mat-size" class="font-medium text-slate-900 dark:text-slate-100 text-sm font-mono">—</span>
                                    </div>
                                    <div class="p-3 bg-slate-100/50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs">
                                        <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">PCS/SHEET</span>
                                        <span id="mat-pcs-sheet" class="font-medium text-slate-900 dark:text-slate-100 text-sm font-mono">—</span>
                                    </div>
                                    <div class="p-3 bg-slate-100/50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs">
                                        <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">WEIGHT/PCS</span>
                                        <span id="mat-weight-pcs" class="font-medium text-slate-900 dark:text-slate-100 text-sm font-mono">—</span>
                                    </div>
                                    <div class="p-3 bg-slate-100/50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs">
                                        <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">YIELD RATIO</span>
                                        <span id="mat-yield" class="font-semibold text-indigo-600 dark:text-indigo-400 text-sm">—</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Standard Parts --}}
                            <div>
                                <h4 class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3 border-b border-slate-300 dark:border-slate-600 pb-1.5">Standard Parts</h4>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs">
                                    <div class="p-3 bg-slate-100/50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs">
                                        <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">STD PART NO</span>
                                        <span id="std-part-no" class="font-medium text-slate-900 dark:text-slate-100 text-sm font-mono">—</span>
                                    </div>
                                    <div class="p-3 bg-slate-100/50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs">
                                        <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">STD QTY</span>
                                        <span id="std-qty" class="font-medium text-slate-900 dark:text-slate-100 text-sm">—</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Packing & Transport Cost --}}
                            <div>
                                <h4 class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3 border-b border-slate-300 dark:border-slate-600 pb-1.5">Packing & Transport</h4>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                                    <div class="p-3 bg-slate-100/50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs">
                                        <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">PACKING TYPE</span>
                                        <span id="pack-type" class="font-medium text-slate-900 dark:text-slate-100 text-sm">—</span>
                                    </div>
                                    <div class="p-3 bg-slate-100/50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs">
                                        <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">PCS/PACK</span>
                                        <span id="pack-pcs" class="font-medium text-slate-900 dark:text-slate-100 text-sm">—</span>
                                    </div>
                                    <div class="p-3 bg-slate-100/50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs">
                                        <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">PART VOL (m²)</span>
                                        <span id="part-vol-m2" class="font-medium text-slate-900 dark:text-slate-100 text-sm font-mono">—</span>
                                    </div>
                                    <div class="p-3 bg-slate-100/50 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-600 rounded-xs">
                                        <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase mb-1">TRUCK VOL (m²)</span>
                                        <span id="truck-vol-m2" class="font-medium text-slate-900 dark:text-slate-100 text-sm font-mono">—</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Sketch / Drawing (Right Side) --}}
                        <div class="w-full lg:w-[320px] flex-shrink-0">
                            <div id="sketch-card" class="border border-slate-200 dark:border-slate-700 bg-slate-100/50 dark:bg-slate-900/30 p-4 rounded-xs w-full flex flex-col min-h-[300px]">
                                <h4 class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3 border-b border-slate-350 dark:border-slate-650 pb-1.5">Sketch / Drawing</h4>
                                <div id="sketch-wrapper" class="flex-1 flex items-center justify-center bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-850 rounded-xs p-3 min-h-[220px]">
                                    <x-image-viewer 
                                        id="sketch-img"
                                        placeholderId="sketch-placeholder"
                                        placeholderText="No sketch available"
                                        placeholderSubtext="Import EBD spreadsheet containing drawing files"
                                    />
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- TAB: TOOLING PROCESSES --}}
                <div id="card-tab-tooling" class="card-tab-panel hidden space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tooling Processes</span>
                        <button type="button" id="btn-add-tooling" class="px-3.5 py-1.5 text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white rounded-xs shadow-sm flex items-center gap-1.5 transition-all cursor-pointer">
                            <i class="fa-solid fa-plus text-[10px]"></i> Add Tooling
                        </button>
                    </div>
                    <div class="overflow-x-auto border border-slate-200 dark:border-slate-700 rounded-xs bg-white dark:bg-slate-900">
                        <table class="w-full text-xs text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-100 dark:bg-slate-900 font-bold text-slate-500 uppercase tracking-wider text-[10px] divide-x divide-slate-200 dark:divide-slate-700 border-b border-slate-200 dark:border-slate-700">
                                    <th class="p-2.5 w-8 text-center">#</th>
                                    <th class="p-2.5">Rank</th>
                                    <th class="p-2.5">Cat</th>
                                    <th class="p-2.5">OP</th>
                                    <th class="p-2.5">Process Name</th>
                                    <th class="p-2.5">Homeline</th>
                                    <th class="p-2.5 text-right">Tonnage</th>
                                    <th class="p-2.5 text-right">Die Ht</th>
                                    <th class="p-2.5 text-center">Cav</th>
                                    <th class="p-2.5 text-center">Qty</th>
                                    <th class="p-2.5 text-right">Price</th>
                                    <th class="p-2.5">Status</th>
                                    <th class="p-2.5">Information</th>
                                    <th class="p-2.5 w-16 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="tooling-tbody" class="divide-y divide-slate-200 dark:divide-slate-700">
                                {{-- Filled via JS --}}
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- TAB: ADD PROCESSES --}}
                <div id="card-tab-addprocess" class="card-tab-panel hidden space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Additional Processes</span>
                        <button type="button" id="btn-add-addprocess" class="px-3.5 py-1.5 text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white rounded-xs shadow-sm flex items-center gap-1.5 transition-all cursor-pointer">
                            <i class="fa-solid fa-plus text-[10px]"></i> Add Process
                        </button>
                    </div>
                    <div class="overflow-x-auto border border-slate-200 dark:border-slate-700 rounded-xs bg-white dark:bg-slate-900">
                        <table class="w-full text-xs text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-100 dark:bg-slate-900 font-bold text-slate-500 uppercase tracking-wider text-[10px] divide-x divide-slate-200 dark:divide-slate-700 border-b border-slate-200 dark:border-slate-700">
                                    <th class="p-2.5 w-8 text-center">#</th>
                                    <th class="p-2.5">Process Name</th>
                                    <th class="p-2.5 text-center">Qty</th>
                                    <th class="p-2.5 text-center">Unit</th>
                                    <th class="p-2.5 text-right">Cost (IDR)</th>
                                    <th class="p-2.5 w-16 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="addprocess-tbody" class="divide-y divide-slate-200 dark:divide-slate-700">
                                {{-- Filled via JS --}}
                            </tbody>
                        </table>
                    </div>
                </div>
{{-- ===== IMPORT MODAL ===== --}}
<div id="import-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-705 rounded-xs shadow-2xl w-full max-w-lg mx-4 animate-fade-in">

        {{-- Modal Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-700">
            <div>
                <h2 class="text-sm font-bold text-slate-850 dark:text-white">Import EBD File</h2>
                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Upload an XLSX file to update or create a new EBD revision</p>
            </div>
            <button type="button" id="btn-close-import-modal"
                    class="w-7 h-7 flex items-center justify-center rounded-xs text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        {{-- Modal Body --}}
        <form id="form-import-ebd" enctype="multipart/form-data">
            @csrf
            {{-- Hidden inputs with current EBD metadata to preserve relationship context --}}
            <input type="hidden" name="wo_id" value="{{ $ebdHeader->wo_id }}">
            <input type="hidden" name="customer_id" value="{{ $ebdHeader->customer_id }}">
            <input type="hidden" name="model_id" value="{{ $ebdHeader->model_id }}">
            <input type="hidden" name="ebd_id" id="input-ebd-id" value="{{ $ebdHeader->id }}">

            <div class="px-5 py-4 space-y-4">
                {{-- Import Mode Selection --}}
                <div class="space-y-2">
                    <label class="block text-[11px] font-semibold text-slate-605 dark:text-slate-400 uppercase tracking-wider">
                        Import Action
                    </label>
                    <div class="flex flex-col gap-2 bg-slate-50 dark:bg-slate-900/30 p-2.5 border border-slate-200 dark:border-slate-700 rounded-xs">
                        <label class="flex items-center gap-2.5 cursor-pointer text-xs font-semibold text-slate-700 dark:text-slate-205 select-none">
                            <input type="radio" name="import_mode" value="overwrite" checked
                                   class="h-3.5 w-3.5 border-slate-300 text-indigo-600 focus:ring-0 cursor-pointer">
                            <span>Update / Overwrite current revision (Rev. {{ $ebdHeader->revision }})</span>
                        </label>
                        <label class="flex items-center gap-2.5 cursor-pointer text-xs font-semibold text-slate-700 dark:text-slate-205 select-none">
                            <input type="radio" name="import_mode" value="new_revision"
                                   class="h-3.5 w-3.5 border-slate-300 text-indigo-600 focus:ring-0 cursor-pointer">
                            <span>Create a new revision</span>
                        </label>
                    </div>
                </div>

                {{-- Metadata Info Display --}}
                <div class="bg-slate-50 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-700 rounded-xs p-3 text-xs space-y-1.5 select-none">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Work Order (SPK):</span>
                        <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $ebdHeader->workOrder->wo_number ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Customer:</span>
                        <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $ebdHeader->customer->code ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Project Model:</span>
                        <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $ebdHeader->projectModel->name ?? '—' }}</span>
                    </div>
                </div>

                {{-- Date & Revision --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                            EBD Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="date" id="input-date" required
                               value="{{ date('Y-m-d') }}"
                               class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-205 focus:outline-none focus:border-indigo-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                            Revision
                        </label>
                        <input type="text" name="revision" id="input-revision" readonly
                               value="{{ $ebdHeader->revision }}" placeholder="e.g. 0, 1, A"
                               class="w-full px-3 py-2 text-xs bg-slate-100 dark:bg-slate-950 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-205 focus:outline-none focus:border-indigo-500 transition-all">
                    </div>
                </div>

                {{-- File Upload --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                        EBD File (XLSX) <span class="text-rose-500">*</span>
                    </label>
                    <div id="drop-zone"
                         class="relative border border-dashed border-slate-300 dark:border-slate-600 rounded-xs p-5 text-center cursor-pointer hover:border-indigo-500 hover:bg-indigo-50/20 dark:hover:bg-indigo-950/20 transition-all group">
                        <input type="file" name="file_ebd" id="input-file-ebd" required
                               accept=".xlsx,.zip"
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                        <div id="drop-zone-content">
                            <i class="fa-solid fa-cloud-arrow-up text-2xl text-slate-300 dark:text-slate-600 group-hover:text-indigo-400 transition-colors mb-2 block"></i>
                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                                Drop your XLSX file here, or <span class="text-indigo-500">browse</span>
                            </p>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">Accepts .xlsx or .zip (max 20 MB)</p>
                        </div>
                        <div id="file-selected-info" class="hidden">
                            <i class="fa-solid fa-file-excel text-2xl text-emerald-500 mb-1 block"></i>
                            <p class="text-xs font-bold text-slate-700 dark:text-slate-200" id="file-selected-name"></p>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500" id="file-selected-size"></p>
                        </div>
                    </div>
                </div>

                {{-- Import Result Alert Container (Errors/Warnings) --}}
                <div id="importResult" class="hidden text-xs rounded-xs border"></div>

            </div>

            {{-- Modal Footer --}}
            <div class="px-5 py-3.5 bg-slate-50 dark:bg-slate-900/30 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-2 rounded-b-xs">
                <button type="button" id="btn-cancel-import"
                        class="px-4 py-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-slate-600 rounded-xs hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                    Cancel
                </button>
                <button type="submit" id="btn-submit-import"
                        class="px-4 py-1.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xs shadow-sm hover:shadow transition-all flex items-center gap-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                    <i class="fa-solid fa-file-import text-[10px]"></i>
                    <span id="btn-submit-text">Start Import</span>
                    <span id="btn-submit-spinner" class="hidden">
                        <i class="fa-solid fa-spinner animate-spin text-[10px]"></i>
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

<x-sweetalert />

{{-- ===== PART MODAL ===== --}}
<div id="part-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xs shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] flex flex-col animate-fade-in">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-700 flex-shrink-0">
            <div>
                <h2 id="part-modal-title" class="text-sm font-bold text-slate-850 dark:text-white">Add Part</h2>
                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Specify part details, dimensions, and specifications</p>
            </div>
            <button type="button" class="btn-close-part-modal w-7 h-7 flex items-center justify-center rounded-xs text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>
        <form id="form-part-ebd" enctype="multipart/form-data" class="flex-1 overflow-y-auto">
            @csrf
            <input type="hidden" name="parent_id" id="part-parent-id">
            <input type="hidden" name="item_id" id="part-item-id">
            
            <div class="px-5 py-4 space-y-5">
                <!-- Section 1: Identification -->
                <div>
                    <h3 class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-2.5 pb-1 border-b border-slate-200 dark:border-slate-700">Part Identification</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Part No <span class="text-rose-500">*</span></label>
                            <input type="text" name="part_no" id="part-input-no" required class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Part Name <span class="text-rose-500">*</span></label>
                            <input type="text" name="part_name" id="part-input-name" required class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                        </div>
                    </div>
                </div>

                <!-- Section 2: Quantities & Dimensions -->
                <div>
                    <h3 class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-2.5 pb-1 border-b border-slate-200 dark:border-slate-700">Dimensions & Status</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Part Rank</label>
                            <select name="part_rank" id="part-input-rank" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                                <option value="">—</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Part Status</label>
                            <input type="text" name="status" id="part-input-status" placeholder="e.g. NEW PART" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Qty/Unit</label>
                            <input type="number" name="qty_unit" id="part-input-qty" min="1" value="1" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Pcs/Month</label>
                            <input type="text" name="pcs_month" id="part-input-pcs-month" value="0" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Width (mm)</label>
                            <input type="number" step="0.1" name="width" id="part-input-width" value="0.0" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Length (mm)</label>
                            <input type="number" step="0.1" name="length" id="part-input-length" value="0.0" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Height (mm)</label>
                            <input type="number" step="0.1" name="height" id="part-input-height" value="0.0" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Weight (kg)</label>
                            <input type="number" step="0.001" name="weight" id="part-input-weight" value="0.000" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                        </div>
                    </div>
                </div>

                <!-- Section 3: Material Specification -->
                <div>
                    <h3 class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-2.5 pb-1 border-b border-slate-200 dark:border-slate-700">Material Details</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="col-span-2">
                            <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Material Spec</label>
                            <input type="text" name="mat_spec" id="part-input-mat-spec" placeholder="e.g. SPCC, SAPH440" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-405 uppercase tracking-wider mb-1">Thickness (mm)</label>
                            <input type="number" step="0.01" name="mat_thick" id="part-input-mat-thick" value="0.00" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-405 uppercase tracking-wider mb-1">Yield Ratio (%)</label>
                            <input type="number" step="0.01" name="mat_yield_ratio" id="part-input-mat-yield" value="0.00" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-405 uppercase tracking-wider mb-1">Sheet Width (mm)</label>
                            <input type="number" step="1" name="mat_width" id="part-input-mat-width" value="0" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-405 uppercase tracking-wider mb-1">Sheet Length (mm)</label>
                            <input type="number" step="1" name="mat_length" id="part-input-mat-length" value="0" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-405 uppercase tracking-wider mb-1">Pcs / Sheet</label>
                            <input type="number" step="1" name="mat_pcs_sheet" id="part-input-mat-pcs" value="0" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-450 uppercase tracking-wider mb-1">Weight / Pcs (kg)</label>
                            <input type="number" step="0.001" name="mat_weight_pcs" id="part-input-mat-weight" value="0.000" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                        </div>
                    </div>
                </div>

                <!-- Section 4: Packing & Standard Parts & Sketch Drawing -->
                <div>
                    <h3 class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-2.5 pb-1 border-b border-slate-200 dark:border-slate-700">Packing & Drawings</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Packing Type</label>
                            <input type="text" name="packing_type" id="part-input-packing-type" placeholder="e.g. Box" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Pcs / Packing</label>
                            <input type="number" step="1" name="pcs_packing" id="part-input-pcs-packing" value="0" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Std Part No</label>
                            <input type="text" name="std_part_no" id="part-input-std-no" placeholder="e.g. Std-01" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Std Qty</label>
                            <input type="number" step="1" name="std_qty" id="part-input-std-qty" value="0" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Std UoM</label>
                            <input type="text" name="std_uom" id="part-input-std-uom" placeholder="e.g. pcs, meter" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                        </div>
                    </div>
                </div>

                <!-- Sketch File Upload -->
                <div>
                    <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Sketch Image (PNG/JPG)</label>
                    <input type="file" name="sketch" id="part-input-sketch" accept="image/*" class="w-full text-xs text-slate-500 dark:text-slate-400 file:mr-3 file:py-1 file:px-2.5 file:rounded-xs file:border file:border-slate-300 dark:file:border-slate-600 file:text-[10px] file:font-semibold file:bg-slate-50 dark:file:bg-slate-900 file:text-slate-700 dark:file:text-slate-300 hover:file:bg-slate-100 transition-all cursor-pointer">
                </div>
            </div>
            
            <div class="px-5 py-3.5 bg-slate-50 dark:bg-slate-900/30 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-2 rounded-b-xs flex-shrink-0">
                <button type="button" class="btn-close-part-modal px-4 py-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-slate-600 rounded-xs hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-1.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xs shadow-sm hover:shadow transition-all flex items-center gap-1.5 cursor-pointer">
                    <i class="fa-regular fa-floppy-disk text-[10px]"></i> Save Part
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ===== TOOLING PROCESS MODAL ===== --}}
<div id="tooling-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xs shadow-2xl w-full max-w-lg mx-4 flex flex-col animate-fade-in">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-700">
            <div>
                <h2 id="tooling-modal-title" class="text-sm font-bold text-slate-850 dark:text-white">Add Tooling Process</h2>
                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Specify press tooling operations and dimensions</p>
            </div>
            <button type="button" class="btn-close-tooling-modal w-7 h-7 flex items-center justify-center rounded-xs text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>
        <form id="form-tooling-ebd">
            @csrf
            <input type="hidden" name="tooling_id" id="tooling-id">
            
            <div class="px-5 py-4 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Rank</label>
                        <input type="text" name="tool_rank" id="tooling-input-rank" placeholder="e.g. A" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Category</label>
                        <input type="text" name="category" id="tooling-input-cat" placeholder="e.g. Press" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">OP <span class="text-rose-500">*</span></label>
                        <input type="text" name="op" id="tooling-input-op" required placeholder="e.g. OP10" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Process Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="process_name" id="tooling-input-name" required placeholder="e.g. Blanking" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Homeline</label>
                        <input type="text" name="prod_homeline" id="tooling-input-home" placeholder="e.g. L-01" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Tonnage (T)</label>
                        <input type="number" name="tonnage" id="tooling-input-ton" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Die Height (mm)</label>
                        <input type="number" step="0.1" name="die_height" id="tooling-input-height" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Status</label>
                        <input type="text" name="tooling_status" id="tooling-input-status" placeholder="e.g. New" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Cavity</label>
                        <input type="number" name="cavity" id="tooling-input-cav" min="1" value="1" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Qty</label>
                        <input type="number" name="qty" id="tooling-input-qty" min="1" value="1" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Price (IDR)</label>
                        <input type="text" name="price_idr" id="tooling-input-price" placeholder="0" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                    </div>
                </div>
            </div>
            
            <div class="px-5 py-3.5 bg-slate-50 dark:bg-slate-900/30 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-2 rounded-b-xs">
                <button type="button" class="btn-close-tooling-modal px-4 py-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-slate-600 rounded-xs hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-1.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xs shadow-sm hover:shadow transition-all flex items-center gap-1.5 cursor-pointer">
                    <i class="fa-regular fa-floppy-disk text-[10px]"></i> Save Tooling
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ===== ADDITIONAL PROCESS MODAL ===== --}}
<div id="addprocess-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xs shadow-2xl w-full max-w-sm mx-4 flex flex-col animate-fade-in">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-700">
            <div>
                <h2 id="addprocess-modal-title" class="text-sm font-bold text-slate-850 dark:text-white">Add Process</h2>
                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Specify auxiliary/manual operation cost details</p>
            </div>
            <button type="button" class="btn-close-addprocess-modal w-7 h-7 flex items-center justify-center rounded-xs text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>
        <form id="form-addprocess-ebd">
            @csrf
            <input type="hidden" name="addprocess_id" id="addprocess-id">
            
            <div class="px-5 py-4 space-y-4">
                <div>
                    <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Process Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="process_name" id="addprocess-input-name" required placeholder="e.g. Spot Welding" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Qty</label>
                        <input type="number" name="qty" id="addprocess-input-qty" min="1" value="1" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Unit</label>
                        <input type="text" name="unit" id="addprocess-input-unit" placeholder="pcs" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Cost (IDR)</label>
                    <input type="text" name="cost_idr" id="addprocess-input-cost" placeholder="0" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                </div>
            </div>
            
            <div class="px-5 py-3.5 bg-slate-50 dark:bg-slate-900/30 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-2 rounded-b-xs">
                <button type="button" class="btn-close-addprocess-modal px-4 py-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-slate-600 rounded-xs hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-1.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xs shadow-sm hover:shadow transition-all flex items-center gap-1.5 cursor-pointer">
                    <i class="fa-regular fa-floppy-disk text-[10px]"></i> Save Process
                </button>
            </div>
        </form>
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

    // Active Item reference helper
    let currentSelectedItemId = null;

    // 2. Tab switching logic inside the right details card
    $('.card-tab-btn').on('click', function () {
        const target = $(this).data('tab');
        localStorage.setItem('ebd_active_tab', target);
        
        $('.card-tab-btn').removeClass('active-tab border-indigo-600 text-indigo-600 dark:text-indigo-400')
                          .addClass('border-transparent text-slate-500');
        $(this).addClass('active-tab border-indigo-600 text-indigo-600 dark:text-indigo-400')
               .removeClass('border-transparent text-slate-500');

        $('.card-tab-panel').addClass('hidden');
        $('#card-tab-' + target).removeClass('hidden');

        // Show/hide Edit Specs button based on active tab
        if (target === 'specs' && currentSelectedItemId) {
            $('#btn-edit-specs').removeClass('hidden');
        } else {
            $('#btn-edit-specs').addClass('hidden');
        }
    });

    // 3. Selection handler for BOM Tree items
    $('.bom-item-row').on('click', function () {
        const itemId = $(this).data('item-id');
        selectBomItem(itemId);
    });

    function selectBomItem(itemId) {
        currentSelectedItemId = itemId;
        localStorage.setItem('ebd_active_item_id', itemId);
        const activeTab = $('.card-tab-btn.active-tab').data('tab') || 'specs';
        if (activeTab === 'specs') {
            $('#btn-edit-specs').removeClass('hidden');
        } else {
            $('#btn-edit-specs').addClass('hidden');
        }
        $('#btn-delete-part').removeClass('hidden');

        const item = itemsLookup[itemId];
        if (!item) return;

        // Highlight selected left panel item
        $('.bom-item-row').removeClass('bg-indigo-100/60 dark:bg-indigo-950/20 border-indigo-600 dark:border-indigo-500 text-indigo-900 dark:text-indigo-200').addClass('border-transparent');
        $(`.bom-item-row[data-item-id="${itemId}"]`).addClass('bg-indigo-100/60 dark:bg-indigo-950/20 border-indigo-600 dark:border-indigo-500 text-indigo-900 dark:text-indigo-200').removeClass('border-transparent');

        // Populate Identifiers
        const outlineNumber = $(`.bom-item-row[data-item-id="${itemId}"]`).attr('data-outline') || '—';
        $('#active-part-outline').text(outlineNumber);
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
        $('#std-qty').text(item.std_qty ? `${item.std_qty} ${item.std_uom || ''}`.trim() : '—');

        // Transport Cost
        $('#part-vol-m2').text(item.part_vol_m2 ? Number(item.part_vol_m2).toFixed(4) : '—');
        $('#truck-vol-m2').text(item.truck_vol_m2 ? Number(item.truck_vol_m2).toFixed(4) : '—');

        // Display sketch card if image path is present in DB
        if (item.sketch && item.sketch.trim() !== '') {
            let sketchPath = item.sketch.trim();
            if (sketchPath.startsWith('http://') || sketchPath.startsWith('https://')) {
                // Keep external URLs as-is
            } else {
                sketchPath = sketchPath.replace(/^\/+/, '');
                if (sketchPath.startsWith('storage/')) {
                    sketchPath = sketchPath.substring(8);
                }
                const storageBase = '{{ asset("storage") }}';
                sketchPath = storageBase + '/' + sketchPath;
            }

            // Ensure protocol matches current page to avoid mixed content / firewall blocks
            if (window.location.protocol === 'https:' && sketchPath.startsWith('http://')) {
                sketchPath = sketchPath.replace('http://', 'https://');
            }

            if (typeof window.initializeImageViewer === 'function') {
                window.initializeImageViewer(sketchPath);
            } else {
                $('#sketch-img').attr('src', sketchPath).removeClass('hidden');
                $('#sketch-placeholder').addClass('hidden');
            }
        } else {
            if (typeof window.initializeImageViewer === 'function') {
                window.initializeImageViewer('');
            } else {
                $('#sketch-img').addClass('hidden').attr('src', '');
                $('#sketch-placeholder').removeClass('hidden');
            }
        }

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
                    <tr class="hover:bg-slate-100/50 dark:hover:bg-slate-700/30 divide-x divide-slate-200 dark:divide-slate-700 border-b border-slate-200 dark:border-slate-700">
                        <td class="p-2 text-slate-400 font-mono text-[10px] text-center">${idx + 1}</td>
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
                            ${tp.tooling_status ? `<span class="px-1.5 py-0.5 text-[9px] font-bold bg-amber-100/70 text-amber-700 dark:bg-amber-955/40 dark:text-amber-400 rounded-xs">${tp.tooling_status}</span>` : '—'}
                        </td>
                        <td class="p-2 text-slate-500 dark:text-slate-400 text-[10px] max-w-[160px] truncate" title="${tp.information || ''}">${tp.information || '—'}</td>
                        <td class="p-2 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                <button type="button" class="btn-edit-tooling w-7 h-7 flex items-center justify-center bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xs transition-colors cursor-pointer" title="Edit Tooling" data-id="${tp.id}">
                                    <i class="fa-regular fa-pen-to-square text-xs"></i>
                                </button>
                                <button type="button" class="btn-delete-tooling w-7 h-7 flex items-center justify-center bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-rose-600 dark:text-rose-450 hover:bg-rose-50 dark:hover:bg-rose-950/20 rounded-xs transition-colors cursor-pointer" title="Delete Tooling" data-id="${tp.id}">
                                    <i class="fa-regular fa-trash-can text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
        } else {
            toolingHtml = '<tr><td colspan="14" class="p-4 text-center text-slate-400 border border-slate-200 dark:border-slate-700">No tooling process data.</td></tr>';
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
                    <tr class="hover:bg-slate-100/50 dark:hover:bg-slate-700/30 divide-x divide-slate-200 dark:divide-slate-700 border-b border-slate-200 dark:border-slate-700">
                        <td class="p-2 text-slate-400 font-mono text-[10px] text-center">${idx + 1}</td>
                        <td class="p-2 font-bold text-slate-800 dark:text-slate-200">${ap.process_name || '—'}</td>
                        <td class="p-2 text-center">${ap.qty ?? 0}</td>
                        <td class="p-2 text-center font-mono text-slate-400">${ap.unit ?? 'pcs'}</td>
                        <td class="p-2 text-right font-bold text-teal-600 dark:text-teal-400 font-mono">${costVal}</td>
                        <td class="p-2 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                <button type="button" class="btn-edit-addprocess w-7 h-7 flex items-center justify-center bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xs transition-colors cursor-pointer" title="Edit Process" data-id="${ap.id}">
                                    <i class="fa-regular fa-pen-to-square text-xs"></i>
                                </button>
                                <button type="button" class="btn-delete-addprocess w-7 h-7 flex items-center justify-center bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-rose-600 dark:text-rose-450 hover:bg-rose-50 dark:hover:bg-rose-950/20 rounded-xs transition-colors cursor-pointer" title="Delete Process" data-id="${ap.id}">
                                    <i class="fa-regular fa-trash-can text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
        } else {
            addProcessHtml = '<tr><td colspan="5" class="p-4 text-center text-slate-400 border border-slate-200 dark:border-slate-700">No additional process data.</td></tr>';
        }
        $('#addprocess-tbody').html(addProcessHtml);
    }

    // 4. Automatically select the first or saved active item on load
    const savedActiveId = localStorage.getItem('ebd_active_item_id');
    let activeRow = $(`.bom-item-row[data-item-id="${savedActiveId}"]`);
    if (activeRow.length === 0) {
        activeRow = $('.bom-item-row').first();
    }
    if (activeRow.length > 0) {
        selectBomItem(activeRow.data('item-id'));
    }

    // Tab retention: auto switch back to the last active tab
    const savedActiveTab = localStorage.getItem('ebd_active_tab');
    if (savedActiveTab) {
        $(`.card-tab-btn[data-tab="${savedActiveTab}"]`).trigger('click');
    }

    // =========================================================================
    // IMPORT MODAL HANDLER
    // =========================================================================
    const EBD_IMPORT_URL = '{{ route("management.ebd.import") }}';
    const EBD_SHOW_BASE_URL = '{{ url("management/ebd") }}';

    function openImportModal() {
        $('#import-modal').removeClass('hidden').addClass('flex');
        $('#importResult').addClass('hidden').removeClass('bg-rose-50 text-rose-900 border-rose-100 p-4').html('');
    }

    function closeImportModal() {
        $('#import-modal').addClass('hidden').removeClass('flex');
        $('#form-import-ebd')[0].reset();
        resetDropZone();
        setSubmitLoading(false);
        $('#importResult').addClass('hidden').removeClass('bg-rose-50 text-rose-900 border-rose-100 p-4').html('');
    }

    $('#btn-open-import-modal').on('click', openImportModal);
    $('#btn-close-import-modal, #btn-cancel-import').on('click', closeImportModal);

    // Toggle between update/overwrite and new revision mode
    $('input[name="import_mode"]').on('change', function() {
        const mode = $(this).val();
        if (mode === 'overwrite') {
            $('#input-ebd-id').val('{{ $ebdHeader->id }}');
            $('#input-revision').val('{{ $ebdHeader->revision }}').prop('readonly', true).addClass('bg-slate-100 dark:bg-slate-950');
        } else {
            $('#input-ebd-id').val('');
            const nextRev = '{{ is_numeric($ebdHeader->revision) ? intval($ebdHeader->revision) + 1 : "" }}';
            $('#input-revision').val(nextRev).prop('readonly', false).removeClass('bg-slate-100 dark:bg-slate-950');
        }
    });

    // Close on backdrop click
    $('#import-modal').on('click', function (e) {
        if ($(e.target).is('#import-modal')) closeImportModal();
    });

    // File selection drop zone behavior
    $('#input-file-ebd').on('change', function () {
        const file = this.files[0];
        if (!file) { resetDropZone(); return; }

        const sizeMB = (file.size / 1024 / 1024).toFixed(2);
        $('#drop-zone-content').addClass('hidden');
        $('#file-selected-info').removeClass('hidden');
        $('#file-selected-name').text(file.name);
        $('#file-selected-size').text(sizeMB + ' MB');
    });

    function resetDropZone() {
        $('#drop-zone-content').removeClass('hidden');
        $('#file-selected-info').addClass('hidden');
        $('#file-selected-name').text('');
        $('#file-selected-size').text('');
    }

    function setSubmitLoading(state) {
        if (state) {
            $('#btn-submit-text').text('Importing...');
            $('#btn-submit-spinner').removeClass('hidden');
            $('#btn-submit-import').prop('disabled', true);
        } else {
            $('#btn-submit-text').text('Start Import');
            $('#btn-submit-spinner').addClass('hidden');
            $('#btn-submit-import').prop('disabled', false);
        }
    }

    $('#form-import-ebd').on('submit', function (e) {
        e.preventDefault();
        setSubmitLoading(true);
        $('#importResult').addClass('hidden').removeClass('bg-rose-50 text-rose-900 border-rose-100 p-4').html('');

        const formData = new FormData(this);

        $.ajax({
            url: EBD_IMPORT_URL,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                closeImportModal();
                showToast(response.message || 'EBD imported successfully!', 'success');
                // Redirect to the newly generated EBD detail screen
                setTimeout(function () {
                    if (response.id) {
                        window.location.href = EBD_SHOW_BASE_URL + '/' + response.id;
                    } else {
                        window.location.reload();
                    }
                }, 1200);
            },
            error: function (xhr) {
                setSubmitLoading(false);
                const res = xhr.responseJSON;
                let errorHtml = `<div class="font-bold mb-1"><i class="fa-solid fa-circle-exclamation mr-1"></i> ${res?.message || 'Import failed. Please check the file format.'}</div>`;

                if (res?.errors && Array.isArray(res.errors)) {
                    errorHtml += '<ul class="list-disc pl-5 mt-2 space-y-1">';
                    res.errors.forEach(function (err) {
                        if (err.errors && Array.isArray(err.errors)) {
                            err.errors.forEach(function (msg) {
                                errorHtml += `<li>Row ${err.row || '?'}: ${msg}</li>`;
                            });
                        }
                    });
                    errorHtml += '</ul>';
                }

                $('#importResult')
                    .removeClass('hidden')
                    .addClass('bg-rose-50 text-rose-900 border-rose-100 p-4')
                    .html(errorHtml);

                showToast('Import failed - check error details', 'error');
            }
        });
    });

    // =========================================================================
    // PART MODAL HANDLERS (ADD/EDIT PART)
    // =========================================================================
    function openPartModal(mode, data = {}) {
        $('#form-part-ebd')[0].reset();
        $('#part-input-sketch').val('');
        $('#part-modal').removeClass('hidden').addClass('flex');
        
        if (mode === 'edit') {
            $('#part-modal-title').text('Edit Part Specifications');
            $('#part-item-id').val(data.id || '');
            $('#part-parent-id').val(data.parent_id || '');
            
            // Populate fields
            $('#part-input-no').val(data.part_no || '');
            $('#part-input-name').val(data.part_name || '');
            $('#part-input-rank').val(data.part_rank || '');
            $('#part-input-status').val(data.status || '');
            $('#part-input-qty').val(data.qty_unit ?? 1);
            $('#part-input-pcs-month').val(data.pcs_month ? Number(data.pcs_month).toLocaleString('id-ID') : '0');
            
            const w = data.width ? Number(data.width).toFixed(1) : '0.0';
            const l = data.length ? Number(data.length).toFixed(1) : '0.0';
            const h = data.height ? Number(data.height).toFixed(1) : '0.0';
            $('#part-input-width').val(w);
            $('#part-input-length').val(l);
            $('#part-input-height').val(h);
            $('#part-input-weight').val(data.weight ? Number(data.weight).toFixed(3) : '0.000');
            
            $('#part-input-mat-spec').val(data.mat_spec || '');
            $('#part-input-mat-thick').val(data.mat_thick ? Number(data.mat_thick).toFixed(2) : '0.00');
            $('#part-input-mat-yield').val(data.mat_yield_ratio ? Number(data.mat_yield_ratio).toFixed(2) : '0.00');
            
            const mw = data.mat_width ? Number(data.mat_width).toFixed(0) : '0';
            const ml = data.mat_length ? Number(data.mat_length).toFixed(0) : '0';
            $('#part-input-mat-width').val(mw);
            $('#part-input-mat-length').val(ml);
            
            $('#part-input-mat-pcs').val(data.mat_pcs_sheet || '0');
            $('#part-input-mat-weight').val(data.mat_weight_pcs ? Number(data.mat_weight_pcs).toFixed(3) : '0.000');
            $('#part-input-packing-type').val(data.packing_type || '');
            $('#part-input-pcs-packing').val(data.pcs_packing || '0');
            $('#part-input-std-no').val(data.std_part_no || '');
            $('#part-input-std-qty').val(data.std_qty || '0');
            $('#part-input-std-uom').val(data.std_uom || '');
        } else {
            // Add mode
            $('#part-modal-title').text(data.parent_id ? 'Add Sub-Part' : 'Add Root Part');
            $('#part-item-id').val('');
            $('#part-parent-id').val(data.parent_id || '');
            
            // Set defaults
            $('#part-input-status').val('NEW PART');
            $('#part-input-qty').val(1);
            $('#part-input-pcs-month').val('0');
        }
    }

    function closePartModal() {
        $('#part-modal').addClass('hidden').removeClass('flex');
    }

    $('#btn-add-root-part').on('click', function () {
        openPartModal('add');
    });

    $(document).on('click', '.btn-add-sub-part', function (e) {
        e.stopPropagation(); // Avoid selecting row
        const parentId = $(this).data('parent-id');
        openPartModal('add', { parent_id: parentId });
    });

    $('#btn-edit-specs').on('click', function () {
        if (!currentSelectedItemId) return;
        const item = itemsLookup[currentSelectedItemId];
        if (item) {
            openPartModal('edit', item);
        }
    });

    $('.btn-close-part-modal').on('click', closePartModal);

    $('#form-part-ebd').on('submit', function (e) {
        e.preventDefault();
        const itemId = $('#part-item-id').val();
        let url = '';
        if (itemId) {
            url = `{{ url('management/ebd/items') }}/${itemId}/update`;
        } else {
            url = `{{ url('management/ebd') }}/{{ $ebdHeader->id }}/items`;
        }

        const formData = new FormData(this);
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                closePartModal();
                showToast(response.message || 'Part saved successfully!', 'success');
                if (response.item) {
                    localStorage.setItem('ebd_active_item_id', response.item.id);
                }
                setTimeout(() => window.location.reload(), 1000);
            },
            error: function (xhr) {
                const res = xhr.responseJSON;
                showToast(res?.message || 'Failed to save part specifications.', 'error');
            }
        });
    });

    // Delete part action from header
    $('#btn-delete-part').on('click', function () {
        if (!currentSelectedItemId) return;
        const itemId = currentSelectedItemId;
        
        Swal.fire({
            title: 'Delete Part?',
            text: 'This will delete this part and all of its descendants recursively! This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#475569',
            confirmButtonText: 'Yes, delete it!',
            customClass: {
                popup: 'rounded-xs border border-slate-205 dark:border-slate-705 bg-white dark:bg-slate-805 text-slate-805 dark:text-white',
                confirmButton: 'rounded-xs font-bold text-xs px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white',
                cancelButton: 'rounded-xs font-semibold text-xs px-4 py-2 bg-slate-200 hover:bg-slate-350 text-slate-700'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('management/ebd/items') }}/${itemId}/delete`,
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function (response) {
                        showToast(response.message || 'Part deleted successfully!', 'success');
                        localStorage.removeItem('ebd_active_item_id');
                        setTimeout(() => window.location.reload(), 1000);
                    },
                    error: function (xhr) {
                        showToast('Failed to delete part.', 'error');
                    }
                });
            }
        });
    });

    // =========================================================================
    // TOOLING PROCESS CRUD HANDLERS
    // =========================================================================
    function openToolingModal(mode, data = {}) {
        $('#form-tooling-ebd')[0].reset();
        $('#tooling-modal').removeClass('hidden').addClass('flex');
        
        if (mode === 'edit') {
            $('#tooling-modal-title').text('Edit Tooling Process');
            $('#tooling-id').val(data.id || '');
            
            $('#tooling-input-rank').val(data.tool_rank || '');
            $('#tooling-input-cat').val(data.category || '');
            $('#tooling-input-op').val(data.op || '');
            $('#tooling-input-name').val(data.process_name || '');
            $('#tooling-input-home').val(data.prod_homeline || '');
            $('#tooling-input-ton').val(data.tonnage || '');
            $('#tooling-input-height').val(data.die_height || '');
            $('#tooling-input-status').val(data.tooling_status || '');
            $('#tooling-input-cav').val(data.cavity ?? 1);
            $('#tooling-input-qty').val(data.qty ?? 1);
            $('#tooling-input-price').val(data.price_idr ? Number(data.price_idr).toLocaleString('id-ID') : '0');
        } else {
            $('#tooling-modal-title').text('Add Tooling Process');
            $('#tooling-id').val('');
            $('#tooling-input-cav').val(1);
            $('#tooling-input-qty').val(1);
        }
    }

    function closeToolingModal() {
        $('#tooling-modal').addClass('hidden').removeClass('flex');
    }

    $('#btn-add-tooling').on('click', function () {
        if (!currentSelectedItemId) {
            showToast('Please select a component first.', 'warning');
            return;
        }
        openToolingModal('add');
    });

    $(document).on('click', '.btn-edit-tooling', function () {
        const id = $(this).data('id');
        const item = itemsLookup[currentSelectedItemId];
        const tp = item?.tooling_processes?.find(p => p.id == id);
        if (tp) {
            openToolingModal('edit', tp);
        }
    });

    $('.btn-close-tooling-modal').on('click', closeToolingModal);

    $('#form-tooling-ebd').on('submit', function (e) {
        e.preventDefault();
        const id = $('#tooling-id').val();
        let url = '';
        if (id) {
            url = `{{ url('management/ebd/tooling') }}/${id}/update`;
        } else {
            url = `{{ url('management/ebd/items') }}/${currentSelectedItemId}/tooling`;
        }

        $.ajax({
            url: url,
            type: 'POST',
            data: $(this).serialize(),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                closeToolingModal();
                showToast(response.message || 'Tooling process saved successfully!', 'success');
                setTimeout(() => window.location.reload(), 1000);
            },
            error: function (xhr) {
                showToast('Failed to save tooling process.', 'error');
            }
        });
    });

    $(document).on('click', '.btn-delete-tooling', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Delete Tooling Process?',
            text: 'Are you sure you want to delete this operation?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#475569',
            confirmButtonText: 'Yes, delete it!',
            customClass: {
                popup: 'rounded-xs border border-slate-205 dark:border-slate-705 bg-white dark:bg-slate-805 text-slate-805 dark:text-white',
                confirmButton: 'rounded-xs font-bold text-xs px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white',
                cancelButton: 'rounded-xs font-semibold text-xs px-4 py-2 bg-slate-200 hover:bg-slate-355 text-slate-700'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('management/ebd/tooling') }}/${id}/delete`,
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function (response) {
                        showToast(response.message || 'Tooling process deleted!', 'success');
                        setTimeout(() => window.location.reload(), 1000);
                    },
                    error: function (xhr) {
                        showToast('Failed to delete tooling process.', 'error');
                    }
                });
            }
        });
    });

    // =========================================================================
    // ADDITIONAL PROCESS CRUD HANDLERS
    // =========================================================================
    function openAddProcessModal(mode, data = {}) {
        $('#form-addprocess-ebd')[0].reset();
        $('#addprocess-modal').removeClass('hidden').addClass('flex');
        
        if (mode === 'edit') {
            $('#addprocess-modal-title').text('Edit Process');
            $('#addprocess-id').val(data.id || '');
            $('#addprocess-input-name').val(data.process_name || '');
            $('#addprocess-input-qty').val(data.qty ?? 1);
            $('#addprocess-input-unit').val(data.unit || 'pcs');
            $('#addprocess-input-cost').val(data.cost_idr ? Number(data.cost_idr).toLocaleString('id-ID') : '0');
        } else {
            $('#addprocess-modal-title').text('Add Process');
            $('#addprocess-id').val('');
            $('#addprocess-input-qty').val(1);
            $('#addprocess-input-unit').val('pcs');
        }
    }

    function closeAddProcessModal() {
        $('#addprocess-modal').addClass('hidden').removeClass('flex');
    }

    $('#btn-add-addprocess').on('click', function () {
        if (!currentSelectedItemId) {
            showToast('Please select a component first.', 'warning');
            return;
        }
        openAddProcessModal('add');
    });

    $(document).on('click', '.btn-edit-addprocess', function () {
        const id = $(this).data('id');
        const item = itemsLookup[currentSelectedItemId];
        const ap = item?.add_processes?.find(p => p.id == id);
        if (ap) {
            openAddProcessModal('edit', ap);
        }
    });

    $('.btn-close-addprocess-modal').on('click', closeAddProcessModal);

    $('#form-addprocess-ebd').on('submit', function (e) {
        e.preventDefault();
        const id = $('#addprocess-id').val();
        let url = '';
        if (id) {
            url = `{{ url('management/ebd/addprocess') }}/${id}/update`;
        } else {
            url = `{{ url('management/ebd/items') }}/${currentSelectedItemId}/addprocess`;
        }

        $.ajax({
            url: url,
            type: 'POST',
            data: $(this).serialize(),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                closeAddProcessModal();
                showToast(response.message || 'Process saved successfully!', 'success');
                setTimeout(() => window.location.reload(), 1000);
            },
            error: function (xhr) {
                showToast('Failed to save process details.', 'error');
            }
        });
    });

    $(document).on('click', '.btn-delete-addprocess', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Delete Process?',
            text: 'Are you sure you want to delete this process?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#475569',
            confirmButtonText: 'Yes, delete it!',
            customClass: {
                popup: 'rounded-xs border border-slate-205 dark:border-slate-705 bg-white dark:bg-slate-805 text-slate-805 dark:text-white',
                confirmButton: 'rounded-xs font-bold text-xs px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white',
                cancelButton: 'rounded-xs font-semibold text-xs px-4 py-2 bg-slate-200 hover:bg-slate-355 text-slate-700'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('management/ebd/addprocess') }}/${id}/delete`,
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function (response) {
                        showToast(response.message || 'Process deleted!', 'success');
                        setTimeout(() => window.location.reload(), 1000);
                    },
                    error: function (xhr) {
                        showToast('Failed to delete process.', 'error');
                    }
                });
            }
        });
    });

    // Helper: auto format thousands separators on inputs
    function maskNumber(val) {
        return val.replace(/\D/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
    
    $('#part-input-pcs-month, #tooling-input-price, #addprocess-input-cost').on('input', function() {
        const raw = $(this).val();
        $(this).val(maskNumber(raw));
    });
});
</script>
@endpush
@endsection