@extends('layouts.app')

@section('title', 'Supplier Quotation Comparison Detail · Promise Management')
@section('page_title', 'Supplier Quotation Comparison Detail')
@section('header-title', 'Cost Comparison')

@section('content')
<div class="flex h-[calc(100vh-64px)] mt-16 overflow-hidden bg-white dark:bg-slate-900 flex-col border-t border-slate-300 dark:border-slate-800">

    {{-- ===== METADATA TOP BAR ===== --}}
    <div class="flex items-center gap-6 px-6 py-3 bg-slate-100 dark:bg-slate-900 border-b border-slate-300 dark:border-slate-800 flex-shrink-0 text-xs text-slate-800 dark:text-slate-100">
        
        {{-- Back Action & Action Buttons --}}
        <div class="flex items-center pr-6 border-r border-slate-300 dark:border-slate-700 flex-shrink-0">
            <div class="flex gap-1.5">
                <a href="{{ route('management.tooling-quotation.index') }}"
                   class="inline-flex items-center justify-center gap-2 px-3 h-8 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-750 border border-slate-300 dark:border-slate-600 rounded-sm text-xs font-medium text-slate-700 dark:text-slate-200 transition-all active:scale-98">
                    <i class="fa-solid fa-arrow-left text-[10px]"></i> Back
                </a>
                @if($selectedEbd)
                    <button type="button" onclick="openQuotationExportModal('{{ route('management.work-order-tooling.quotation', $encryptedWoId) }}')"
                            class="inline-flex items-center justify-center gap-1.5 px-3 h-8 bg-emerald-600 hover:bg-emerald-700 border border-transparent rounded-sm text-xs font-semibold text-white shadow-xs transition-all active:scale-98 cursor-pointer" title="Download Excel Template">
                        <i class="fa-solid fa-file-excel text-[10px]"></i> Template
                    </button>
                    <button type="button" onclick="$('#import-customer-modal').removeClass('hidden').addClass('flex')"
                            class="inline-flex items-center justify-center gap-1.5 px-3 h-8 bg-amber-600 hover:bg-amber-700 border border-transparent rounded-sm text-xs font-semibold text-white shadow-xs transition-all active:scale-98 cursor-pointer" title="Import Customer Target Quotation">
                        <i class="fa-solid fa-building-user text-[10px]"></i> Customer
                    </button>
                    <button type="button" onclick="$('#import-supplier-modal').removeClass('hidden').addClass('flex')"
                            class="inline-flex items-center justify-center gap-1.5 px-3 h-8 bg-indigo-600 hover:bg-indigo-700 border border-transparent rounded-sm text-xs font-semibold text-white shadow-xs transition-all active:scale-98 cursor-pointer" title="Import Supplier Quotation">
                        <i class="fa-solid fa-file-import text-[10px]"></i> Supplier
                    </button>
                @endif
            </div>
        </div>

        {{-- Metadata Row --}}
        <div class="flex-1 flex items-center justify-between gap-4 px-4 py-2 border border-slate-300 dark:border-slate-800 rounded-sm bg-slate-50/60 dark:bg-slate-950/40 mx-2 overflow-hidden">
            
            {{-- WO Number --}}
            <div class="flex items-center gap-2 flex-shrink-0">
                <div class="w-8 h-8 flex items-center justify-center rounded-sm bg-slate-200/80 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex-shrink-0">
                    <i class="fa-solid fa-file-invoice text-xs"></i>
                </div>
                <div class="min-w-0">
                    <span class="block text-[10px] font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-0.5 whitespace-nowrap">WO Number</span>
                    <span class="block font-medium text-slate-800 dark:text-slate-100 text-xs whitespace-nowrap">{{ $selectedEbd?->workOrder?->wo_number ?? $workOrder?->wo_number ?? '—' }}</span>
                </div>
            </div>

            {{-- Divider --}}
            <div class="w-px h-8 bg-slate-300 dark:border-slate-800 self-center flex-shrink-0"></div>

            {{-- Customer --}}
            <div class="flex items-center gap-2 min-w-0 flex-1 px-1">
                <div class="w-8 h-8 flex items-center justify-center rounded-sm bg-slate-200/80 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex-shrink-0">
                    <i class="fa-solid fa-building text-xs"></i>
                </div>
                <div class="min-w-0 w-full">
                    <span class="block text-[10px] font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-0.5 whitespace-nowrap">Customer</span>
                    <span class="block font-medium text-slate-800 dark:text-slate-100 text-xs truncate">
                        @if(isset($selectedEbd?->customer?->code))
                            {{ $selectedEbd->customer->code }} -
                        @elseif(isset($workOrder?->customer?->code))
                            {{ $workOrder->customer->code }} -
                        @endif
                        {{ $selectedEbd?->customer?->name ?? $workOrder?->customer?->name ?? '—' }}
                    </span>
                </div>
            </div>

            {{-- Divider --}}
            <div class="w-px h-8 bg-slate-300 dark:border-slate-800 self-center flex-shrink-0"></div>

            {{-- Project Model --}}
            <div class="flex items-center gap-2 flex-shrink-0 px-1">
                <div class="w-8 h-8 flex items-center justify-center rounded-sm bg-slate-200/80 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex-shrink-0">
                    <i class="fa-solid fa-tags text-xs"></i>
                </div>
                <div class="min-w-0">
                    <span class="block text-[10px] font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-0.5 whitespace-nowrap">Project Model</span>
                    <span class="block font-medium text-slate-800 dark:text-slate-100 text-xs whitespace-nowrap">{{ $selectedEbd?->projectModel?->name ?? $workOrder?->projectModel?->name ?? '—' }}</span>
                </div>
            </div>

            {{-- Divider --}}
            <div class="w-px h-8 bg-slate-300 dark:border-slate-800 self-center flex-shrink-0"></div>

            {{-- Total Items --}}
            <div class="flex items-center gap-2 flex-shrink-0 pl-1">
                <div class="w-8 h-8 flex items-center justify-center rounded-sm bg-slate-200/80 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex-shrink-0">
                    <i class="fa-solid fa-cubes text-xs"></i>
                </div>
                <div class="min-w-0">
                    <span class="block text-[10px] font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-0.5 whitespace-nowrap">Part Count</span>
                    <span class="block font-semibold text-slate-800 dark:text-slate-100 text-xs whitespace-nowrap">{{ $ebdItems ? $ebdItems->count() : 0 }} Parts</span>
                </div>
            </div>
        </div>

        {{-- Global Revision & Status --}}
        <div class="pl-6 border-l border-slate-300 dark:border-slate-700 flex-shrink-0 flex items-center gap-2.5">
            <span class="px-2.5 py-1 text-xs font-mono font-medium border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 rounded-sm">
                REV: {{ $selectedEbd?->revision ?? '0' }}
            </span>
            <span class="px-2.5 py-1 text-xs font-medium border rounded-sm bg-emerald-100/70 text-emerald-700 border-emerald-300 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-900/30">
                {{ $selectedEbd?->status ?? 'Released' }}
            </span>
        </div>
    </div>

    {{-- ===== TWO-COLUMN SPLIT PANEL CONTAINER ===== --}}
    <div class="flex-1 flex min-h-0 overflow-hidden">
        
        {{-- ===== LEFT PANEL: PART NUMBERS LIST ===== --}}
        <div class="w-[28%] max-w-[340px] min-w-[260px] flex flex-col bg-slate-100/50 dark:bg-slate-900/30 border-r border-slate-300 dark:border-slate-800 overflow-hidden h-full">
            <div class="px-4 py-3 bg-slate-100/70 dark:bg-slate-900/80 border-b border-slate-300 dark:border-slate-800 flex items-center justify-between flex-shrink-0">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200 flex items-center gap-2">
                    <i class="fa-solid fa-cubes text-slate-500 dark:text-slate-400 text-xs"></i> Part Numbers
                </span>
                <span class="text-[10px] bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-100 px-2 py-0.5 rounded-sm font-semibold">
                    {{ $ebdItems->count() }} items
                </span>
            </div>
            
            <div class="flex-1 overflow-y-auto p-2 space-y-1">
                {{-- ALL PARTS SUMMARY BUTTON --}}
                <button type="button" 
                        data-part-id="all-summary"
                        class="btn-select-part w-full text-left p-3 rounded-sm border transition-all cursor-pointer flex flex-col gap-1 active-part bg-indigo-50 dark:bg-indigo-950/60 border-indigo-500 dark:border-indigo-500 shadow-xs mb-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fa-solid fa-chart-pie text-xs text-indigo-600 dark:text-indigo-400"></i> All Parts Summary
                        </span>
                        <span class="text-[9px] font-bold text-indigo-500 dark:text-indigo-400">GLOBAL</span>
                    </div>
                    <div class="text-[11px] font-normal text-slate-600 dark:text-slate-400">Total accumulation of all parts & suppliers</div>
                </button>

                <div class="h-px bg-slate-200 dark:bg-slate-700 my-1"></div>

                @foreach($ebdItems as $idx => $item)
                    <button type="button" 
                            data-part-id="{{ $item->id }}"
                            class="btn-select-part w-full text-left p-3 rounded-sm border transition-all cursor-pointer flex flex-col gap-1 bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700/80 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-750">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-100">{{ $item->part_no }}</span>
                            <span class="text-[10px] text-slate-400 font-medium">#{{ $idx + 1 }}</span>
                        </div>
                        <div class="text-xs font-normal truncate text-slate-600 dark:text-slate-300">{{ $item->part_name }}</div>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- ===== RIGHT PANEL: TOOLING PROCESS MATRIX WITH SUPPLIER TABS ===== --}}
        <div class="flex-1 flex flex-col min-w-0 bg-white dark:bg-slate-900 overflow-hidden">
            
            {{-- TOP NAVIGATION BAR (MULTI-SELECT CHECKBOXES + SUMMARY vs DETAILED COMPARISON) --}}
            <div class="px-6 py-0 bg-slate-50 dark:bg-slate-900/80 border-b border-slate-300 dark:border-slate-800 flex items-center justify-between flex-shrink-0 gap-4 relative z-20">
                
                {{-- CUSTOM DROPDOWN MULTI-SELECT WITH REVISIONS, SEARCH & CHECKBOXES --}}
                <div class="relative min-w-[240px] py-2">
                    <button type="button" 
                            id="btn-custom-supplier-dropdown"
                            class="w-full flex items-center justify-between gap-2 px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-sm text-xs text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-750 transition-all cursor-pointer">
                        <span id="lbl-custom-supplier-dropdown" class="truncate font-semibold text-slate-700 dark:text-slate-200">
                            <i class="fa-solid fa-filter text-[10px] text-slate-500 mr-1"></i> Filter & Revisions
                        </span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-slate-400"></i>
                    </button>

                    {{-- DROPDOWN MENU PANEL (FLOATING) --}}
                    <div id="panel-custom-supplier-dropdown" class="hidden absolute top-full left-0 mt-1 w-72 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-sm shadow-2xl z-50 overflow-hidden">
                        
                        {{-- 1. EBD REVISION SELECTOR --}}
                        <div class="p-2.5 bg-slate-100/70 dark:bg-slate-900/80 border-b border-slate-200 dark:border-slate-700">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                                1. Target EBD Revision
                            </label>
                            @if(isset($availableEbdRevisions) && $availableEbdRevisions->count() > 1)
                                <select onchange="window.location.href='?ebd_id=' + this.value" class="w-full text-xs font-mono font-bold px-2 py-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:border-indigo-500 cursor-pointer">
                                    @foreach($availableEbdRevisions as $revEbd)
                                        <option value="{{ $revEbd->id }}" {{ $revEbd->id == ($selectedEbd?->id ?? null) ? 'selected' : '' }}>
                                            Rev {{ $revEbd->revision }} {{ $revEbd->id == ($workOrder?->ebd_header_id ?? null) ? '★ (Active WO)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <div class="text-xs font-mono font-semibold text-slate-700 dark:text-slate-300 px-2 py-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-sm">
                                    Rev {{ $selectedEbd?->revision ?? '0' }} (Latest)
                                </div>
                            @endif
                        </div>

                        {{-- 2. CUSTOMER TARGET QUOTATION SELECTOR (IF IMPORTED) --}}
                        @if(isset($activeCustomerQuote))
                            <div class="p-2.5 bg-amber-50/70 dark:bg-amber-950/40 border-b border-amber-200 dark:border-amber-800/60">
                                <div class="flex items-center justify-between mb-1">
                                    <label class="text-[10px] font-bold uppercase tracking-wider text-amber-800 dark:text-amber-300 flex items-center gap-1">
                                        <i class="fa-solid fa-building-user text-[10px]"></i> 2. Customer Target
                                    </label>
                                    <span class="px-1.5 py-0.2 text-[9px] font-bold bg-amber-200 dark:bg-amber-900/60 text-amber-800 dark:text-amber-200 rounded-sm">
                                        Rev {{ $activeCustomerQuote->revision }}
                                    </span>
                                </div>
                                @if(isset($activeCustomerQuote->all_revisions) && count($activeCustomerQuote->all_revisions) > 1)
                                    <select onchange="switchCustomerRevision(this.value)" class="w-full text-[10px] font-mono font-bold px-2 py-1 bg-white dark:bg-slate-900 border border-amber-300 dark:border-amber-700 rounded-sm text-amber-800 dark:text-amber-200 focus:outline-none cursor-pointer">
                                        @foreach($activeCustomerQuote->all_revisions as $cRev)
                                            <option value="{{ $cRev->id }}" {{ $cRev->id == $activeCustomerQuote->id ? 'selected' : '' }}>
                                                Rev {{ $cRev->revision }} (Rp {{ number_format($cRev->total_cost_idr, 0, ',', '.') }})
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <div class="text-[11px] font-semibold text-slate-700 dark:text-slate-200 truncate">
                                        {{ $activeCustomerQuote->supplier_name }}
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- 3. SORT SUPPLIERS BY --}}
                        <div class="p-2.5 bg-slate-100/70 dark:bg-slate-900/80 border-b border-slate-200 dark:border-slate-700">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                                3. Sort Suppliers By
                            </label>
                            <select onchange="window.location.href=updateQueryStringParam('sort', this.value)" class="w-full text-xs font-semibold px-2 py-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:border-indigo-500 cursor-pointer">
                                <option value="worth" {{ request('sort', 'worth') == 'worth' ? 'selected' : '' }}>Lowest Cost (Best Price #1)</option>
                                <option value="highest" {{ request('sort') == 'highest' ? 'selected' : '' }}>Highest Cost</option>
                                <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Supplier Name (A-Z)</option>
                            </select>
                        </div>

                        {{-- 4. SUPPLIER FILTER SEARCH & HEADER --}}
                        <div class="p-2 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 flex items-center justify-between gap-2">
                            <div class="relative flex-1">
                                <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
                                <input type="text" id="input-search-supplier-filter" placeholder="Search supplier..." class="w-full pl-7 pr-2 py-1 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                            </div>
                            <label class="flex items-center gap-1 text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 cursor-pointer flex-shrink-0 pr-1">
                                <input type="checkbox" id="chk-toggle-all-suppliers" checked class="rounded-sm border-slate-300 text-indigo-600 focus:ring-indigo-500" {{ $quotations->count() === 0 ? 'disabled' : '' }}>
                                <span>All</span>
                            </label>
                        </div>

                        {{-- 5. QUOTATION CHECKBOX ITEMS WITH REVISION DROPDOWN & RANK BADGE --}}
                        <div class="max-h-64 overflow-y-auto p-1.5 space-y-1">
                            <div id="supplier-items-container" class="space-y-1">
                                @forelse($quotations as $q)
                                    @php
                                        $isCustomer = ($q->source_type === 'customer' || (!empty($q->customer_id) && empty($q->supplier_id)));
                                    @endphp
                                    <div class="supp-item-label p-2 rounded-sm hover:bg-slate-50 dark:hover:bg-slate-750/60 border {{ $isCustomer ? 'border-amber-200 dark:border-amber-800/60 bg-amber-50/30 dark:bg-amber-950/20' : 'border-slate-100 dark:border-slate-750 bg-slate-50/30 dark:bg-slate-900/30' }} space-y-1">
                                        <div class="flex items-center justify-between gap-2">
                                            <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold {{ $isCustomer ? 'text-amber-900 dark:text-amber-200' : 'text-slate-800 dark:text-slate-100' }} min-w-0">
                                                <input type="checkbox" class="chk-supp-filter rounded-sm border-slate-300 text-indigo-600 focus:ring-indigo-500" data-supp-id="{{ $q->id }}" data-supp-name="{{ $q->supplier_name }}" checked>
                                                <span class="supp-name-text truncate">
                                                    @if($isCustomer)
                                                        <i class="fa-solid fa-building-user text-amber-500 text-[10px] mr-0.5"></i>
                                                    @endif
                                                    {{ $q->supplier_name }}
                                                </span>
                                            </label>
                                            <div class="flex items-center gap-1 flex-shrink-0">
                                                @if($isCustomer)
                                                    <span class="px-1.5 py-0.5 text-[9px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800 rounded-sm">Customer</span>
                                                @elseif(isset($q->worth_rank))
                                                    @if($q->worth_rank === 1)
                                                        <span class="px-1.5 py-0.5 text-[9px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 rounded-sm">Best Price #1</span>
                                                    @else
                                                        <span class="px-1.5 py-0.5 text-[9px] font-medium bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700 rounded-sm">#{{ $q->worth_rank }}</span>
                                                    @endif
                                                @endif
                                                <span class="text-[10px] text-slate-400 font-mono">({{ $q->currency_code ?? 'IDR' }})</span>
                                            </div>
                                        </div>

                                        @if(isset($q->all_revisions) && count($q->all_revisions) > 1)
                                            <div class="flex items-center gap-1.5 pl-6">
                                                <span class="text-[9px] font-medium text-slate-400 uppercase">Rev:</span>
                                                @if($isCustomer)
                                                    <select onchange="switchCustomerRevision(this.value)" class="w-full text-[10px] font-mono font-bold px-1.5 py-0.5 bg-white dark:bg-slate-900 border border-amber-300 dark:border-amber-700 rounded-sm text-amber-700 dark:text-amber-300 focus:outline-none cursor-pointer">
                                                        @foreach($q->all_revisions as $revQuote)
                                                            <option value="{{ $revQuote->id }}" {{ $revQuote->id == $q->id ? 'selected' : '' }}>
                                                                Rev {{ $revQuote->revision }} ({{ $revQuote->created_at ? $revQuote->created_at->format('d/m') : '' }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    <select onchange="switchSupplierRevision('{{ $q->supplier_id }}', this.value)" class="w-full text-[10px] font-mono font-bold px-1.5 py-0.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm text-indigo-600 dark:text-indigo-400 focus:outline-none cursor-pointer">
                                                        @foreach($q->all_revisions as $revQuote)
                                                            <option value="{{ $revQuote->id }}" {{ $revQuote->id == $q->id ? 'selected' : '' }}>
                                                                Rev {{ $revQuote->revision }} ({{ $revQuote->created_at ? $revQuote->created_at->format('d/m') : '' }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                @endif
                                            </div>
                                        @else
                                            <div class="pl-6 text-[10px] font-mono text-slate-400">
                                                Rev {{ $q->revision }} • {{ $q->created_at ? $q->created_at->format('d/m/Y') : '-' }}
                                            </div>
                                        @endif
                                    </div>
                                @empty
                                    <div class="p-4 text-center text-xs text-slate-400">
                                        No customer or supplier quotations imported yet.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                {{-- UNDERLINED TABS (SUMMARY vs DETAILED COMPARISON) --}}
                <div class="flex items-center gap-6 self-stretch">
                    {{-- Summary Overview Tab --}}
                    <button type="button" 
                            data-tab-target="tab-summary"
                            class="btn-tab-quote inline-flex items-center gap-2 h-full py-3 px-1 border-b-2 border-indigo-600 text-indigo-600 dark:text-indigo-400 text-xs font-bold transition-all cursor-pointer">
                        <i class="fa-solid fa-chart-pie text-xs"></i>
                        <span>Summary Matrix</span>
                    </button>

                    {{-- Side-by-Side Detailed Comparison Tab --}}
                    <button type="button" 
                            data-tab-target="tab-comparison-matrix"
                            class="btn-tab-quote inline-flex items-center gap-2 h-full py-3 px-1 border-b-2 border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:border-slate-300 text-xs font-semibold transition-all cursor-pointer">
                        <i class="fa-solid fa-code-compare text-xs"></i>
                        <span>Detail Matrix</span>
                    </button>
                </div>
            </div>

            {{-- PANEL CONTENT VIEWS PER SELECTED PART --}}
            <div class="flex-1 overflow-auto p-4">
                
                {{-- ===== GLOBAL ALL PARTS SUMMARY CONTAINER ===== --}}
                <div id="part-container-all-summary" class="part-container space-y-4">
                    <div class="px-4 py-3 bg-slate-100/80 dark:bg-slate-800/60 border border-slate-300 dark:border-slate-700 rounded-sm flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Global Comparison Matrix (EBD &gt; Customer &gt; Supplier Best Price)</span>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-slate-900 dark:text-slate-100 text-sm">ALL PARTS SUMMARY</span>
                                <span class="text-slate-400">•</span>
                                <span class="text-slate-600 dark:text-slate-300 text-xs font-normal">Perbandingan biaya total proses EBD, target Customer, dan Quotation Supplier</span>
                            </div>
                        </div>
                        <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-1 rounded-sm">
                            {{ $ebdItems->count() }} Parts Total
                        </span>
                    </div>

                    {{-- GLOBAL SUMMARY TABLE --}}
                    <div class="tab-content-view tab-summary border border-slate-300 dark:border-slate-800 rounded-sm overflow-hidden bg-white dark:bg-slate-900 shadow-xs">
                        <table class="w-full text-xs text-left border-collapse">
                            <thead class="bg-slate-50 dark:bg-slate-900/80 border-b border-slate-300 dark:border-slate-800 text-[10px] uppercase font-semibold text-slate-500 dark:text-slate-400">
                                <tr>
                                    <th class="p-3 border-r border-slate-300 dark:border-slate-800 font-bold text-slate-700 dark:text-slate-200 w-44">Global Parameter</th>
                                    {{-- 1. EBD Target Baseline --}}
                                    <th class="p-3 border-r border-slate-300 dark:border-slate-800 w-48 bg-slate-100/80 dark:bg-slate-800/80 text-slate-800 dark:text-slate-100 font-bold">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full bg-slate-500"></span>
                                            <span>Estimasi EBD (Target)</span>
                                        </div>
                                    </th>
                                    
                                    {{-- 2. Customer Target & 3. Suppliers (Ranked by Best Price) --}}
                                    @foreach($quotations as $q)
                                        @php
                                            $isCustomer = ($q->source_type === 'customer' || (!empty($q->customer_id) && empty($q->supplier_id)));
                                        @endphp
                                        <th class="col-supp-{{ $q->id }} p-3 border-r border-slate-300 dark:border-slate-800 {{ $isCustomer ? 'bg-amber-50/80 dark:bg-amber-950/40 text-amber-900 dark:text-amber-200 border-b-2 border-b-amber-500' : 'bg-slate-50 dark:bg-slate-900/60 text-slate-800 dark:text-slate-200 border-b-2 border-b-indigo-500' }} min-w-[200px]">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    @if($isCustomer)
                                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.2 text-[9px] font-extrabold uppercase tracking-wider rounded-sm bg-amber-200 text-amber-900 dark:bg-amber-800 dark:text-amber-100 mb-0.5">
                                                            <i class="fa-solid fa-building-user text-[8px]"></i> Customer Target
                                                        </span>
                                                    @else
                                                        @if(isset($q->worth_rank) && $q->worth_rank === 1)
                                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.2 text-[9px] font-extrabold uppercase tracking-wider rounded-sm bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700 mb-0.5">
                                                                <i class="fa-solid fa-trophy text-[8px] text-amber-500"></i> Best Price #1
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.2 text-[9px] font-bold uppercase tracking-wider rounded-sm bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300 mb-0.5">
                                                                Supplier #{{ $q->worth_rank ?? '' }}
                                                            </span>
                                                        @endif
                                                    @endif
                                                    <div class="font-bold text-xs">{{ $q->supplier_name }}</div>
                                                </div>
                                                <span class="text-[10px] text-slate-400 font-mono">({{ $q->currency_code ?? 'IDR' }})</span>
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800 font-normal">
                                @php
                                    $allProcs = $ebdItems->flatMap(function($i){ return $i->toolingProcesses ?? collect(); });
                                    $gEbdDieCount = $allProcs->filter(function($tp){ return strtoupper($tp->category ?? '') === 'DIE' || $tp->op !== null; })->count();
                                    $gEbdJigCount = $allProcs->filter(function($tp){ return str_contains(strtoupper($tp->process_name ?? ''), 'JIG'); })->count();
                                    $gEbdCfCount  = $allProcs->filter(function($tp){ return str_contains(strtoupper($tp->process_name ?? ''), 'CF'); })->count();
                                    $gEbdTotalCost = $allProcs->sum('price_idr');
                                @endphp

                                {{-- Row: Total DIE All Parts --}}
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                    <td class="p-3 border-r border-slate-300 dark:border-slate-800 font-medium text-slate-700 dark:text-slate-300 bg-slate-50/40 dark:bg-slate-900/30">Total DIE Process (All Parts)</td>
                                    <td class="p-3 border-r border-slate-300 dark:border-slate-800 font-mono font-medium text-slate-800 dark:text-slate-100 bg-slate-50/20 dark:bg-slate-900/10">
                                        {{ $gEbdDieCount }} Dies
                                    </td>
                                    @foreach($quotations as $q)
                                        @php
                                            $gSDieCount = $q->details->filter(function($sd){ return strtoupper($sd->tooling_type ?? '') === 'DIE' || $sd->op !== null; })->count();
                                        @endphp
                                        <td class="col-supp-{{ $q->id }} p-3 border-r border-slate-300 dark:border-slate-800 font-mono font-medium text-slate-800 dark:text-slate-100">
                                            {{ $gSDieCount }} Dies
                                        </td>
                                    @endforeach
                                </tr>

                                {{-- Row: Total JIG All Parts --}}
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                    <td class="p-3 border-r border-slate-300 dark:border-slate-800 font-medium text-slate-700 dark:text-slate-300 bg-slate-50/40 dark:bg-slate-900/30">Total JIG Process (All Parts)</td>
                                    <td class="p-3 border-r border-slate-300 dark:border-slate-800 font-mono font-medium text-slate-800 dark:text-slate-100 bg-slate-50/20 dark:bg-slate-900/10">
                                        {{ $gEbdJigCount }} Jigs
                                    </td>
                                    @foreach($quotations as $q)
                                        @php
                                            $gSJigCount = $q->details->filter(function($sd){ return str_contains(strtoupper($sd->tooling_process_name ?? ''), 'JIG') || strtoupper($sd->tooling_type ?? '') === 'JIG'; })->count();
                                        @endphp
                                        <td class="col-supp-{{ $q->id }} p-3 border-r border-slate-300 dark:border-slate-800 font-mono font-medium text-slate-800 dark:text-slate-100">
                                            {{ $gSJigCount }} Jigs
                                        </td>
                                    @endforeach
                                </tr>

                                {{-- Row: Total CF All Parts --}}
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                    <td class="p-3 border-r border-slate-300 dark:border-slate-800 font-medium text-slate-700 dark:text-slate-300 bg-slate-50/40 dark:bg-slate-900/30">Total Checking Fixture (All Parts)</td>
                                    <td class="p-3 border-r border-slate-300 dark:border-slate-800 font-mono font-medium text-slate-800 dark:text-slate-100 bg-slate-50/20 dark:bg-slate-900/10">
                                        {{ $gEbdCfCount }} CF
                                    </td>
                                    @foreach($quotations as $q)
                                        @php
                                            $gSCfCount = $q->details->filter(function($sd){ return str_contains(strtoupper($sd->tooling_process_name ?? ''), 'CF') || strtoupper($sd->tooling_type ?? '') === 'CF'; })->count();
                                        @endphp
                                        <td class="col-supp-{{ $q->id }} p-3 border-r border-slate-300 dark:border-slate-800 font-mono font-medium text-slate-800 dark:text-slate-100">
                                            {{ $gSCfCount }} CF
                                        </td>
                                    @endforeach
                                </tr>

                                {{-- Row: Global Foreign Cost --}}
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                    <td class="p-3 border-r border-slate-300 dark:border-slate-800 font-medium text-slate-700 dark:text-slate-300 bg-slate-50/40 dark:bg-slate-900/30">Total Foreign Cost (All Parts)</td>
                                    <td class="p-3 border-r border-slate-300 dark:border-slate-800 font-mono text-slate-400 bg-slate-50/20 dark:bg-slate-900/10">
                                        —
                                    </td>
                                    @foreach($quotations as $q)
                                        @php
                                            $gSForeignCost = $q->details->sum('cost_foreign');
                                            $currSymbol = \App\Helpers\CurrencyHelper::getSymbol($q->currency_code);
                                        @endphp
                                        <td class="col-supp-{{ $q->id }} p-3 border-r border-slate-300 dark:border-slate-800 font-mono font-semibold text-slate-800 dark:text-slate-100">
                                            {{ $currSymbol }} {{ number_format($gSForeignCost, 2, ',', '.') }}
                                        </td>
                                    @endforeach
                                </tr>

                                {{-- Row: Global Total Cost IDR --}}
                                <tr class="bg-slate-100/60 dark:bg-slate-800/50">
                                    <td class="p-3 border-r border-slate-300 dark:border-slate-800 font-bold text-slate-900 dark:text-white">GRAND TOTAL TOOLING COST (IDR)</td>
                                    <td class="p-3 border-r border-slate-300 dark:border-slate-800 font-mono font-bold text-slate-900 dark:text-white text-base bg-slate-200/50 dark:bg-slate-800/80">
                                        Rp. {{ number_format($gEbdTotalCost, 0, ',', '.') }}
                                    </td>
                                    @foreach($quotations as $q)
                                        @php
                                            $gSCostIdr = $q->total_cost_idr ?: $q->details->sum('cost_idr');
                                        @endphp
                                        <td class="col-supp-{{ $q->id }} p-3 border-r border-slate-300 dark:border-slate-800 font-mono">
                                            <div class="font-bold text-slate-900 dark:text-white text-base">
                                                Rp. {{ number_format($gSCostIdr, 0, ',', '.') }}
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>

                                {{-- Row: Global Cost Variance / Gap (IDR) --}}
                                <tr class="bg-slate-50/60 dark:bg-slate-900/40">
                                    <td class="p-3 border-r border-slate-300 dark:border-slate-800 font-bold text-slate-700 dark:text-slate-300">COST VARIANCE / GAP (IDR)</td>
                                    <td class="p-3 border-r border-slate-300 dark:border-slate-800 font-mono text-slate-400 bg-slate-100/30 dark:bg-slate-800/30">
                                        —
                                    </td>
                                    @foreach($quotations as $q)
                                        @php
                                            $gSCostIdr = $q->total_cost_idr ?: $q->details->sum('cost_idr');
                                            $gVariance = $gSCostIdr > 0 ? ($gSCostIdr - $gEbdTotalCost) : 0;
                                        @endphp
                                        <td class="col-supp-{{ $q->id }} p-3 border-r border-slate-300 dark:border-slate-800 font-mono text-xs font-bold">
                                            @if($gVariance > 0)
                                                <span class="text-rose-600 dark:text-rose-400 flex items-center gap-1 inline-flex">+ Rp. {{ number_format($gVariance, 0, ',', '.') }} <i class="fa-solid fa-arrow-up text-[10px]"></i></span>
                                            @elseif($gVariance < 0)
                                                <span class="text-emerald-600 dark:text-emerald-400 flex items-center gap-1 inline-flex">- Rp. {{ number_format(abs($gVariance), 0, ',', '.') }} <i class="fa-solid fa-arrow-down text-[10px]"></i></span>
                                            @else
                                                <span class="text-slate-400 font-medium">Match (0)</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>

                                {{-- Row: Global Variance Rate (%) --}}
                                <tr class="bg-slate-50/60 dark:bg-slate-900/40 border-t border-slate-200 dark:border-slate-800">
                                    <td class="p-3 border-r border-slate-300 dark:border-slate-800 font-bold text-slate-700 dark:text-slate-300">VARIANCE RATE (%)</td>
                                    <td class="p-3 border-r border-slate-300 dark:border-slate-800 font-mono text-slate-400 bg-slate-100/30 dark:bg-slate-800/30">
                                        —
                                    </td>
                                    @foreach($quotations as $q)
                                        @php
                                            $gSCostIdr = $q->total_cost_idr ?: $q->details->sum('cost_idr');
                                            $gVariance = $gSCostIdr > 0 ? ($gSCostIdr - $gEbdTotalCost) : 0;
                                            $gVariancePct = ($gEbdTotalCost > 0 && $gSCostIdr > 0) ? (($gVariance / $gEbdTotalCost) * 100) : 0;
                                        @endphp
                                        <td class="col-supp-{{ $q->id }} p-3 border-r border-slate-300 dark:border-slate-800 font-mono text-xs font-bold">
                                            @if($gVariance > 0)
                                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-sm font-mono font-bold text-xs bg-rose-100 text-rose-700 dark:bg-rose-950/80 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                                    + {{ number_format($gVariancePct, 1, ',', '.') }}% <i class="fa-solid fa-arrow-up text-[10px]"></i>
                                                </span>
                                            @elseif($gVariance < 0)
                                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-sm font-mono font-bold text-xs bg-emerald-100 text-emerald-700 dark:bg-emerald-950/80 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                                    - {{ number_format(abs($gVariancePct), 1, ',', '.') }}% <i class="fa-solid fa-arrow-down text-[10px]"></i>
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-sm font-mono font-medium text-xs bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700">0.0%</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                @foreach($ebdItems as $idx => $item)
                    @php
                        $toolingProcs = $item->toolingProcesses ?? collect();
                    @endphp

                    <div id="part-container-{{ $item->id }}" class="part-container space-y-4 hidden">
                        
                        {{-- PART INFO BANNER --}}
                        <div class="px-4 py-3 bg-slate-100/70 dark:bg-slate-800/60 border border-slate-300 dark:border-slate-700 rounded-sm flex items-center justify-between">
                            <div>
                                <span class="text-[10px] font-medium uppercase tracking-wider text-slate-400 dark:text-slate-500">Selected Part</span>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-900 dark:text-white text-sm">{{ $item->part_no }}</span>
                                    <span class="text-slate-400">•</span>
                                    <span class="text-slate-800 dark:text-slate-200 text-xs font-medium">{{ $item->part_name }}</span>
                                </div>
                            </div>
                            <span class="text-xs font-semibold text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-2.5 py-1 rounded-sm">
                                {{ $toolingProcs->count() }} Process Items
                            </span>
                        </div>

                        {{-- 1. TAB SUMMARY VIEW (PARAMETERS TOTAL DIE, JIG, CF, CURRENCY FOR SELECTED SUPPLIERS) --}}
                        <div class="tab-content-view tab-summary border border-slate-300 dark:border-slate-800 rounded-sm overflow-hidden bg-white dark:bg-slate-900 shadow-xs">
                            <table class="w-full text-xs text-left border-collapse">
                                <thead class="bg-slate-50 dark:bg-slate-900/80 border-b border-slate-300 dark:border-slate-800 text-[10px] uppercase font-semibold text-slate-500 dark:text-slate-400">
                                    <tr>
                                        <th class="p-3 border-r border-slate-300 dark:border-slate-800 font-bold text-slate-700 dark:text-slate-200 w-44">Parameter</th>
                                        <th class="p-3 border-r border-slate-300 dark:border-slate-800 w-48 bg-slate-100/80 dark:bg-slate-800/80 text-slate-800 dark:text-slate-100 font-bold">
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-2 h-2 rounded-full bg-slate-500"></span>
                                                <span>Estimasi EBD (Target)</span>
                                            </div>
                                        </th>
                                        
                                        @foreach($quotations as $q)
                                            @php
                                                $isCustomer = ($q->source_type === 'customer' || (!empty($q->customer_id) && empty($q->supplier_id)));
                                            @endphp
                                            <th class="col-supp-{{ $q->id }} p-3 border-r border-slate-300 dark:border-slate-800 {{ $isCustomer ? 'bg-amber-50/80 dark:bg-amber-950/40 text-amber-900 dark:text-amber-200 border-b-2 border-b-amber-500' : 'bg-slate-50 dark:bg-slate-900/60 text-slate-800 dark:text-slate-200 border-b-2 border-b-indigo-500' }} min-w-[200px]">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        @if($isCustomer)
                                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.2 text-[9px] font-extrabold uppercase tracking-wider rounded-sm bg-amber-200 text-amber-900 dark:bg-amber-800 dark:text-amber-100 mb-0.5">
                                                                <i class="fa-solid fa-building-user text-[8px]"></i> Customer Target
                                                            </span>
                                                        @else
                                                            @if(isset($q->worth_rank) && $q->worth_rank === 1)
                                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.2 text-[9px] font-extrabold uppercase tracking-wider rounded-sm bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700 mb-0.5">
                                                                    <i class="fa-solid fa-trophy text-[8px] text-amber-500"></i> Best Price #1
                                                                </span>
                                                            @else
                                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.2 text-[9px] font-bold uppercase tracking-wider rounded-sm bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300 mb-0.5">
                                                                    Supplier #{{ $q->worth_rank ?? '' }}
                                                                </span>
                                                            @endif
                                                        @endif
                                                        <div class="font-bold text-xs">{{ $q->supplier_name }}</div>
                                                    </div>
                                                    <form action="{{ route('management.tooling-quotation.destroy', $q->id) }}" method="POST" onsubmit="return confirm('Remove this quotation?')" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-slate-400 hover:text-rose-500 text-[11px] cursor-pointer" title="Delete Quote">
                                                            <i class="fa-solid fa-xmark"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800 font-normal">
                                    @php
                                        // Calculations for EBD
                                        $ebdDieCount = $toolingProcs->filter(function($tp){ return strtoupper($tp->category ?? '') === 'DIE' || $tp->op !== null; })->count();
                                        $ebdJigCount = $toolingProcs->filter(function($tp){ return str_contains(strtoupper($tp->process_name ?? ''), 'JIG'); })->count();
                                        $ebdCfCount  = $toolingProcs->filter(function($tp){ return str_contains(strtoupper($tp->process_name ?? ''), 'CF'); })->count();
                                        $ebdTotalCost = $toolingProcs->sum('price_idr');
                                    @endphp

                                    {{-- Row: Total DIE --}}
                                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                        <td class="p-3 border-r border-slate-300 dark:border-slate-800 font-medium text-slate-700 dark:text-slate-300 bg-slate-50/40 dark:bg-slate-900/30">Total DIE Process</td>
                                        <td class="p-3 border-r border-slate-300 dark:border-slate-800 font-mono font-medium text-slate-800 dark:text-slate-100 bg-slate-50/20 dark:bg-slate-900/10">
                                            {{ $ebdDieCount }} Dies
                                        </td>
                                        @foreach($quotations as $q)
                                            @php
                                                $sDetails = $q->details->where('ebd_item_id', $item->id);
                                                $sDieCount = $sDetails->filter(function($sd){ return strtoupper($sd->tooling_type ?? '') === 'DIE' || $sd->op !== null; })->count();
                                            @endphp
                                            <td class="col-supp-{{ $q->id }} p-3 border-r border-slate-300 dark:border-slate-800 font-mono font-medium text-slate-800 dark:text-slate-100">
                                                {{ $sDieCount }} Dies
                                            </td>
                                        @endforeach
                                    </tr>

                                    {{-- Row: Total JIG --}}
                                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                        <td class="p-3 border-r border-slate-300 dark:border-slate-800 font-medium text-slate-700 dark:text-slate-300 bg-slate-50/40 dark:bg-slate-900/30">Total JIG Process</td>
                                        <td class="p-3 border-r border-slate-300 dark:border-slate-800 font-mono font-medium text-slate-800 dark:text-slate-100 bg-slate-50/20 dark:bg-slate-900/10">
                                            {{ $ebdJigCount }} Jigs
                                        </td>
                                        @foreach($quotations as $q)
                                            @php
                                                $sDetails = $q->details->where('ebd_item_id', $item->id);
                                                $sJigCount = $sDetails->filter(function($sd){ return str_contains(strtoupper($sd->tooling_process_name ?? ''), 'JIG') || strtoupper($sd->tooling_type ?? '') === 'JIG'; })->count();
                                            @endphp
                                            <td class="col-supp-{{ $q->id }} p-3 border-r border-slate-300 dark:border-slate-800 font-mono font-medium text-slate-800 dark:text-slate-100">
                                                {{ $sJigCount }} Jigs
                                            </td>
                                        @endforeach
                                    </tr>

                                    {{-- Row: Total CF --}}
                                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                        <td class="p-3 border-r border-slate-300 dark:border-slate-800 font-medium text-slate-700 dark:text-slate-300 bg-slate-50/40 dark:bg-slate-900/30">Total Checking Fixture (CF)</td>
                                        <td class="p-3 border-r border-slate-300 dark:border-slate-800 font-mono font-medium text-slate-800 dark:text-slate-100 bg-slate-50/20 dark:bg-slate-900/10">
                                            {{ $ebdCfCount }} CF
                                        </td>
                                        @foreach($quotations as $q)
                                            @php
                                                $sDetails = $q->details->where('ebd_item_id', $item->id);
                                                $sCfCount = $sDetails->filter(function($sd){ return str_contains(strtoupper($sd->tooling_process_name ?? ''), 'CF') || strtoupper($sd->tooling_type ?? '') === 'CF'; })->count();
                                            @endphp
                                            <td class="col-supp-{{ $q->id }} p-3 border-r border-slate-300 dark:border-slate-800 font-mono font-medium text-slate-800 dark:text-slate-100">
                                                {{ $sCfCount }} CF
                                            </td>
                                        @endforeach
                                    </tr>

                                    {{-- Row: Currency --}}
                                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                        <td class="p-3 border-r border-slate-300 dark:border-slate-800 font-medium text-slate-700 dark:text-slate-300 bg-slate-50/40 dark:bg-slate-900/30">Currency & Exchange Rate</td>
                                        <td class="p-3 border-r border-slate-300 dark:border-slate-800 font-mono text-slate-600 dark:text-slate-400 bg-slate-50/20 dark:bg-slate-900/10">
                                            IDR (Rupiah)
                                        </td>
                                        @foreach($quotations as $q)
                                            <td class="col-supp-{{ $q->id }} p-3 border-r border-slate-300 dark:border-slate-800 font-mono text-slate-600 dark:text-slate-400">
                                                {{ $q->currency_code ?? 'IDR' }} <span class="text-[10px] text-slate-400">(@ Rp. {{ number_format($q->exchange_rate, 0, ',', '.') }})</span>
                                            </td>
                                        @endforeach
                                    </tr>

                                    {{-- Row: Total Foreign Cost --}}
                                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                        <td class="p-3 border-r border-slate-300 dark:border-slate-800 font-medium text-slate-700 dark:text-slate-300 bg-slate-50/40 dark:bg-slate-900/30">Total Foreign Cost</td>
                                        <td class="p-3 border-r border-slate-300 dark:border-slate-800 font-mono text-slate-400 bg-slate-50/20 dark:bg-slate-900/10">
                                            —
                                        </td>
                                        @foreach($quotations as $q)
                                            @php
                                                $sDetails = $q->details->where('ebd_item_id', $item->id);
                                                $sForeignCost = $sDetails->sum('cost_foreign');
                                                $currSymbol = \App\Helpers\CurrencyHelper::getSymbol($q->currency_code);
                                            @endphp
                                            <td class="col-supp-{{ $q->id }} p-3 border-r border-slate-300 dark:border-slate-800 font-mono font-semibold text-slate-800 dark:text-slate-100">
                                                {{ $currSymbol }} {{ number_format($sForeignCost, 2, ',', '.') }}
                                            </td>
                                        @endforeach
                                    </tr>

                                    {{-- Row: Total Cost (IDR) --}}
                                    <tr class="bg-indigo-50/30 dark:bg-indigo-950/20">
                                        <td class="p-3 border-r border-slate-300 dark:border-slate-800 font-bold text-slate-800 dark:text-slate-100">Total Tooling Cost (IDR)</td>
                                        <td class="p-3 border-r border-slate-300 dark:border-slate-800 font-mono font-bold text-slate-900 dark:text-white text-sm bg-slate-100/50 dark:bg-slate-800/50">
                                            Rp. {{ number_format($ebdTotalCost, 0, ',', '.') }}
                                        </td>
                                        @foreach($quotations as $q)
                                            @php
                                                $sDetails = $q->details->where('ebd_item_id', $item->id);
                                                $sCostIdr = $sDetails->sum('cost_idr');
                                            @endphp
                                            <td class="col-supp-{{ $q->id }} p-3 border-r border-slate-300 dark:border-slate-800 font-mono font-bold text-slate-900 dark:text-white text-sm">
                                                Rp. {{ number_format($sCostIdr, 0, ',', '.') }}
                                            </td>
                                        @endforeach
                                    </tr>

                                    {{-- Row: Cost Variance / Gap (IDR) --}}
                                    <tr class="bg-slate-50/60 dark:bg-slate-900/40">
                                        <td class="p-3 border-r border-slate-300 dark:border-slate-800 font-bold text-slate-700 dark:text-slate-300">Cost Variance / Gap (IDR)</td>
                                        <td class="p-3 border-r border-slate-300 dark:border-slate-800 font-mono text-slate-400 bg-slate-100/30 dark:bg-slate-800/30">
                                            —
                                        </td>
                                        @foreach($quotations as $q)
                                            @php
                                                $sDetails = $q->details->where('ebd_item_id', $item->id);
                                                $sCostIdr = $sDetails->sum('cost_idr');
                                                $variance = $sCostIdr > 0 ? ($sCostIdr - $ebdTotalCost) : 0;
                                            @endphp
                                            <td class="col-supp-{{ $q->id }} p-3 border-r border-slate-300 dark:border-slate-800 font-mono text-xs font-bold">
                                                @if($variance > 0)
                                                    <span class="text-rose-600 dark:text-rose-400 flex items-center gap-1 inline-flex">+ Rp. {{ number_format($variance, 0, ',', '.') }} <i class="fa-solid fa-arrow-up text-[10px]"></i></span>
                                                @elseif($variance < 0)
                                                    <span class="text-emerald-600 dark:text-emerald-400 flex items-center gap-1 inline-flex">- Rp. {{ number_format(abs($variance), 0, ',', '.') }} <i class="fa-solid fa-arrow-down text-[10px]"></i></span>
                                                @else
                                                    <span class="text-slate-400 font-medium">Match (0)</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>

                                    {{-- Row: Variance Rate (%) --}}
                                    <tr class="bg-slate-50/60 dark:bg-slate-900/40 border-t border-slate-200 dark:border-slate-800">
                                        <td class="p-3 border-r border-slate-300 dark:border-slate-800 font-bold text-slate-700 dark:text-slate-300">Variance Rate (%)</td>
                                        <td class="p-3 border-r border-slate-300 dark:border-slate-800 font-mono text-slate-400 bg-slate-100/30 dark:bg-slate-800/30">
                                            —
                                        </td>
                                        @foreach($quotations as $q)
                                            @php
                                                $sDetails = $q->details->where('ebd_item_id', $item->id);
                                                $sCostIdr = $sDetails->sum('cost_idr');
                                                $variance = $sCostIdr > 0 ? ($sCostIdr - $ebdTotalCost) : 0;
                                                $variancePct = ($ebdTotalCost > 0 && $sCostIdr > 0) ? (($variance / $ebdTotalCost) * 100) : 0;
                                            @endphp
                                            <td class="col-supp-{{ $q->id }} p-3 border-r border-slate-300 dark:border-slate-800 font-mono text-xs font-bold">
                                                @if($variance > 0)
                                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-sm font-mono font-bold text-xs bg-rose-100 text-rose-700 dark:bg-rose-950/80 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                                        + {{ number_format($variancePct, 1, ',', '.') }}% <i class="fa-solid fa-arrow-up text-[10px]"></i>
                                                    </span>
                                                @elseif($variance < 0)
                                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-sm font-mono font-bold text-xs bg-emerald-100 text-emerald-700 dark:bg-emerald-950/80 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                                        - {{ number_format(abs($variancePct), 1, ',', '.') }}% <i class="fa-solid fa-arrow-down text-[10px]"></i>
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-sm font-mono font-medium text-xs bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700">0.0%</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- 2. SIDE-BY-SIDE DETAILED COMPARISON MATRIX VIEW --}}
                        <div class="tab-content-view tab-comparison-matrix hidden border border-slate-300 dark:border-slate-800 rounded-sm overflow-hidden bg-white dark:bg-slate-900 shadow-xs">
                            <table class="w-full text-xs text-left border-collapse">
                                <thead class="bg-slate-50 dark:bg-slate-900/80 border-b border-slate-300 dark:border-slate-800 text-[10px] uppercase font-semibold text-slate-500 dark:text-slate-400">
                                    <tr>
                                        <th class="p-3 border-r border-slate-300 dark:border-slate-800 text-center w-16">OP No</th>
                                        <th class="p-3 border-r border-slate-300 dark:border-slate-800 w-32 font-bold text-slate-600 dark:text-slate-300">Parameter</th>
                                        <th class="p-3 border-r border-slate-300 dark:border-slate-800 w-48 bg-slate-100/80 dark:bg-slate-800/80 text-slate-800 dark:text-slate-100 font-bold">
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-2 h-2 rounded-full bg-slate-500"></span>
                                                <span>Estimasi EBD (Target)</span>
                                            </div>
                                        </th>
                                        
                                        @foreach($quotations as $q)
                                            @php
                                                $isCustomer = ($q->source_type === 'customer' || (!empty($q->customer_id) && empty($q->supplier_id)));
                                            @endphp
                                            <th class="col-supp-{{ $q->id }} p-3 border-r border-slate-300 dark:border-slate-800 {{ $isCustomer ? 'bg-amber-50/80 dark:bg-amber-950/40 text-amber-900 dark:text-amber-200 border-b-2 border-b-amber-500' : 'bg-slate-50 dark:bg-slate-900/60 text-slate-800 dark:text-slate-200 border-b-2 border-b-indigo-500' }} min-w-[200px]">
                                                <div>
                                                    @if($isCustomer)
                                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.2 text-[9px] font-extrabold uppercase tracking-wider rounded-sm bg-amber-200 text-amber-900 dark:bg-amber-800 dark:text-amber-100 mb-0.5">
                                                            <i class="fa-solid fa-building-user text-[8px]"></i> Customer Target
                                                        </span>
                                                    @else
                                                        @if(isset($q->worth_rank) && $q->worth_rank === 1)
                                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.2 text-[9px] font-extrabold uppercase tracking-wider rounded-sm bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700 mb-0.5">
                                                                <i class="fa-solid fa-trophy text-[8px] text-amber-500"></i> Best Price #1
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.2 text-[9px] font-bold uppercase tracking-wider rounded-sm bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300 mb-0.5">
                                                                Supplier #{{ $q->worth_rank ?? '' }}
                                                            </span>
                                                        @endif
                                                    @endif
                                                    <div class="font-bold text-xs">{{ $q->supplier_name }}</div>
                                                </div>
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800 font-normal">
                                    @php
                                        $opGroups = $toolingProcs->groupBy(function($tp) {
                                            return $tp->op !== null ? 'OP_' . $tp->op : 'PROC_' . $tp->id;
                                        });
                                    @endphp

                                    @foreach($opGroups as $opKey => $procsInOp)
                                        @php
                                            $isOp = str_starts_with($opKey, 'OP_');
                                            $rawOpNo = $isOp ? substr($opKey, 3) : null;
                                            $opLabel = $isOp ? 'OP ' . $rawOpNo : (str_contains(strtoupper($procsInOp->pluck('process_name')->first() ?? ''), 'JIG') ? 'JIG' : 'CF');

                                            $ebdProcNames = $procsInOp->pluck('process_name')->filter()->implode(' + ');
                                            $ebdTypes = $procsInOp->pluck('category')->filter()->unique()->implode(' / ') ?: ($isOp ? 'DIE' : $opLabel);
                                            $ebdTonnage = $procsInOp->pluck('tonnage')->filter()->max();
                                            $ebdDieHeight = $procsInOp->pluck('die_height')->filter()->max();
                                            $ebdTotalCost = $procsInOp->sum('price_idr');

                                            $paramRows = [
                                                ['key' => 'process_name', 'label' => 'Process Name'],
                                                ['key' => 'tooling_type', 'label' => 'Tooling Type'],
                                                ['key' => 'tonnage',      'label' => 'Tonnage (T)'],
                                                ['key' => 'die_height',   'label' => 'Die Height (mm)'],
                                                ['key' => 'cost',         'label' => 'Cost (IDR)'],
                                            ];
                                        @endphp

                                        @foreach($paramRows as $pIdx => $param)
                                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 {{ $pIdx === 4 ? 'border-b border-slate-300 dark:border-slate-800' : '' }}">
                                                @if($pIdx === 0)
                                                    <td rowspan="5" class="p-2.5 text-center border-r border-slate-300 dark:border-slate-800 align-top font-mono font-medium text-slate-800 dark:text-slate-200 bg-slate-50/30 dark:bg-slate-900/20">
                                                        {{ $opLabel }}
                                                    </td>
                                                @endif

                                                <td class="p-2.5 border-r border-slate-300 dark:border-slate-800 text-[11px] font-medium text-slate-500 dark:text-slate-400 bg-slate-50/40 dark:bg-slate-900/30">
                                                    {{ $param['label'] }}
                                                </td>

                                                <td class="p-2.5 border-r border-slate-300 dark:border-slate-800 font-mono bg-slate-50/20 dark:bg-slate-900/10">
                                                    @if($param['key'] === 'process_name')
                                                        <span class="font-normal text-slate-800 dark:text-slate-100 font-sans">{{ $ebdProcNames ?: '—' }}</span>
                                                    @elseif($param['key'] === 'tooling_type')
                                                        <span class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-sm text-[10px]">{{ $ebdTypes }}</span>
                                                    @elseif($param['key'] === 'tonnage')
                                                        {{ $ebdTonnage ? $ebdTonnage . ' T' : '—' }}
                                                    @elseif($param['key'] === 'die_height')
                                                        {{ $ebdDieHeight ? number_format($ebdDieHeight, 0) . ' mm' : '—' }}
                                                    @elseif($param['key'] === 'cost')
                                                        <span class="font-bold text-slate-800 dark:text-slate-100">Rp{{ number_format($ebdTotalCost, 0, ',', '.') }}</span>
                                                    @endif
                                                </td>

                                                @foreach($quotations as $q)
                                                    @php
                                                        $firstProc = $procsInOp->first();
                                                        $suppDetailsInOp = $q->details->filter(function($d) use ($isOp, $rawOpNo, $firstProc, $item) {
                                                            if ($d->ebd_item_id != $item->id) return false;
                                                            if ($d->ebd_tooling_process_id == $firstProc->id) return true;
                                                            return $isOp ? ((int)$d->op === (int)$rawOpNo) : ($d->op === null && strtolower(trim($d->tooling_process_name)) === strtolower(trim($firstProc->process_name)));
                                                        });

                                                        $suppProcNames = $suppDetailsInOp->pluck('tooling_process_name')->filter()->implode(' + ');
                                                        $suppTypes = $suppDetailsInOp->pluck('tooling_type')->filter()->unique()->implode(' / ');
                                                        $suppTonnage = $suppDetailsInOp->pluck('tonnage')->filter()->first();
                                                        $suppDieHeight = $suppDetailsInOp->pluck('die_height')->filter()->max();
                                                        $suppCost = $suppDetailsInOp->sum('cost_idr');
                                                        $variance = $suppCost > 0 ? ($suppCost - $ebdTotalCost) : 0;
                                                    @endphp

                                                    <td class="col-supp-{{ $q->id }} p-2.5 border-r border-slate-300 dark:border-slate-800 font-mono">
                                                        @if($suppDetailsInOp->count() > 0)
                                                            @if($param['key'] === 'process_name')
                                                                <div class="font-normal text-slate-800 dark:text-slate-100 font-sans">{{ $suppProcNames ?: '—' }}</div>
                                                            @elseif($param['key'] === 'tooling_type')
                                                                <span class="px-1.5 py-0.5 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 rounded-sm text-[10px]">{{ $suppTypes ?: 'DIE' }}</span>
                                                            @elseif($param['key'] === 'tonnage')
                                                                {{ $suppTonnage ? $suppTonnage . ' T' : '—' }}
                                                            @elseif($param['key'] === 'die_height')
                                                                {{ $suppDieHeight ? number_format($suppDieHeight, 0) . ' mm' : '—' }}
                                                            @elseif($param['key'] === 'cost')
                                                                <div>
                                                                    <span class="font-bold text-slate-800 dark:text-slate-100">Rp{{ number_format($suppCost, 0, ',', '.') }}</span>
                                                                    <div class="mt-0.5 text-[10px]">
                                                                        @if($variance > 0)
                                                                            <span class="text-rose-600 dark:text-rose-400 font-bold flex items-center gap-0.5 inline-flex">+Rp{{ number_format($variance, 0, ',', '.') }} <i class="fa-solid fa-arrow-up text-[9px]"></i></span>
                                                                        @elseif($variance < 0)
                                                                            <span class="text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-0.5 inline-flex">-Rp{{ number_format(abs($variance), 0, ',', '.') }} <i class="fa-solid fa-arrow-down text-[9px]"></i></span>
                                                                        @else
                                                                            <span class="text-slate-400">Match (0)</span>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        @else
                                                            <span class="text-slate-400 italic text-xs">—</span>
                                                        @endif
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ===== 1. IMPORT CUSTOMER TARGET MODAL ===== --}}
@if($selectedEbd)
<div id="import-customer-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-sm shadow-2xl w-full max-w-md mx-4 overflow-hidden animate-fade-in">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-700">
            <div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-building-user text-amber-500"></i> Import Customer Target Quotation
                </h3>
                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Upload completed Excel quotation file from customer</p>
            </div>
            <button type="button" onclick="$('#import-customer-modal').addClass('hidden').removeClass('flex')" class="w-7 h-7 flex items-center justify-center rounded-sm text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <form class="form-import-quotation" action="{{ route('management.tooling-quotation.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="ebd_header_id" value="{{ $selectedEbd->id }}">
            <input type="hidden" name="source_type" value="customer">
            <input type="hidden" name="customer_id" value="{{ $selectedEbd->customer_id ?? '' }}">

            <div class="px-5 py-4 space-y-4">
                {{-- Customer Info Box --}}
                <div class="p-3 bg-amber-50/70 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 rounded-sm flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-sm bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-300 flex items-center justify-center">
                            <i class="fa-solid fa-building text-sm"></i>
                        </div>
                        <div>
                            <span class="text-[10px] text-amber-700 dark:text-amber-300 font-semibold uppercase tracking-wider block">Target Customer:</span>
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-100">{{ $selectedEbd?->customer?->code ? "[{$selectedEbd->customer->code}] " : "" }}{{ $selectedEbd?->customer?->name ?? 'Customer' }}</span>
                        </div>
                    </div>
                    <span class="text-[9px] font-bold bg-amber-200 dark:bg-amber-900/60 text-amber-800 dark:text-amber-200 px-2 py-0.5 rounded-sm">CUSTOMER</span>
                </div>

                {{-- Template Configuration --}}
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">
                            Import Mapping Template <span class="text-rose-500">*</span>
                        </label>
                        @if($defaultImportTemplateId)
                            <span class="text-[9px] font-semibold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/50 px-1.5 py-0.5 rounded-sm border border-amber-200 dark:border-amber-800">
                                <i class="fa-solid fa-wand-magic-sparkles text-[8px]"></i> Auto-Selected by Customer
                            </span>
                        @endif
                    </div>
                    <select name="template_id" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-xs font-medium text-slate-800 dark:text-slate-100 rounded-sm focus:ring-2 focus:ring-amber-500">
                        <option value="">-- Standard System Template (Default Format) --</option>
                        @foreach($importTemplates ?? [] as $tpl)
                            @php
                                $isCustomerMatch = ($selectedEbd?->customer_id && $tpl->customer_id == $selectedEbd->customer_id);
                                $isSelected = ($tpl->id == ($defaultImportTemplateId ?? null));
                            @endphp
                            <option value="{{ $tpl->id }}" {{ $isSelected ? 'selected' : '' }}>
                                {{ $tpl->template_name }} (Rev {{ $tpl->revision ?? '0' }})
                                @if($tpl->customer)
                                    [{{ $tpl->customer->code ?? $tpl->customer->name }}]
                                @else
                                    [Universal]
                                @endif
                                @if($isCustomerMatch)
                                    ★ (Matched Customer)
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Mode Import --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">
                        Import Mode <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <label class="flex items-center gap-2 p-2.5 border border-amber-200 dark:border-amber-800 rounded-sm bg-amber-50/40 dark:bg-amber-950/40 cursor-pointer hover:border-amber-400">
                            <input type="radio" name="import_mode" value="new_revision" checked class="text-amber-600 focus:ring-amber-500">
                            <div>
                                <span class="block font-bold text-slate-800 dark:text-slate-100 text-[11px]">New Revision</span>
                                <span class="block text-[9px] text-slate-500">Save as Rev 1, Rev 2...</span>
                            </div>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 border border-slate-200 dark:border-slate-700 rounded-sm bg-white dark:bg-slate-900 cursor-pointer hover:border-amber-400">
                            <input type="radio" name="import_mode" value="overwrite" class="text-amber-600 focus:ring-amber-500">
                            <div>
                                <span class="block font-bold text-slate-800 dark:text-slate-100 text-[11px]">Overwrite</span>
                                <span class="block text-[9px] text-slate-500">Update active revision</span>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- File Upload --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">
                        Customer Quotation File (.xlsx) <span class="text-rose-500">*</span>
                    </label>
                    <div class="dropzone-area relative flex flex-col items-center justify-center p-6 border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-sm bg-slate-50/50 dark:bg-slate-900/50 hover:bg-slate-100/70 dark:hover:bg-slate-800/60 transition-all cursor-pointer group text-center">
                        <input type="file" name="quotation_file" accept=".xlsx,.xls,.csv" class="input-quotation-file absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required>
                        
                        <div class="dropzone-prompt flex flex-col items-center justify-center space-y-2 pointer-events-none">
                            <div class="w-9 h-9 rounded-full bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                                <i class="fa-solid fa-cloud-arrow-up text-base"></i>
                            </div>
                            <div class="text-xs text-slate-600 dark:text-slate-300">
                                <span class="font-semibold text-amber-600 dark:text-amber-400">Click to browse file</span> or drag & drop here
                            </div>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500">Supported formats: Excel (.xlsx, .xls, .csv)</p>
                        </div>

                        <div class="dropzone-file-info hidden flex items-center gap-3 p-2 pl-3 pr-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-sm shadow-xs relative z-20 max-w-full">
                            <div class="w-7 h-7 rounded bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                                <i class="fa-solid fa-file-excel text-sm"></i>
                            </div>
                            <div class="min-w-0 text-left pr-2">
                                <p class="dropzone-file-name text-xs font-semibold text-slate-700 dark:text-slate-200 truncate max-w-[180px]"></p>
                                <p class="dropzone-file-size text-[10px] text-slate-400 dark:text-slate-500"></p>
                            </div>
                            <button type="button" class="btn-remove-file w-6 h-6 rounded-full hover:bg-rose-50 dark:hover:bg-rose-950/60 text-slate-400 hover:text-rose-600 flex items-center justify-center transition-colors cursor-pointer ml-auto flex-shrink-0 z-30" title="Remove File">
                                <i class="fa-solid fa-xmark text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Result Alert --}}
                <div class="importResult hidden text-xs font-medium p-3.5 rounded-sm border"></div>
            </div>

            <div class="flex items-center justify-end gap-2 px-5 py-3.5 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-200 dark:border-slate-700">
                <button type="button" onclick="$('#import-customer-modal').addClass('hidden').removeClass('flex')" class="px-3.5 h-8 text-xs font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-sm transition-colors cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="btn-submit-import inline-flex items-center justify-center gap-1.5 px-4 h-8 bg-amber-600 hover:bg-amber-700 text-white rounded-sm text-xs font-semibold shadow-xs active:scale-98 transition-all cursor-pointer">
                    <i class="fa-solid fa-cloud-arrow-up text-xs"></i>
                    <span>Import Customer Target</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ===== 2. IMPORT SUPPLIER QUOTATION MODAL ===== --}}
<div id="import-supplier-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-sm shadow-2xl w-full max-w-md mx-4 overflow-hidden animate-fade-in">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-700">
            <div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-file-import text-indigo-600 dark:text-indigo-400"></i> Import Supplier Quotation
                </h3>
                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Upload quotation file from vendor or supplier</p>
            </div>
            <button type="button" onclick="$('#import-supplier-modal').addClass('hidden').removeClass('flex')" class="w-7 h-7 flex items-center justify-center rounded-sm text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <form class="form-import-quotation" action="{{ route('management.tooling-quotation.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="ebd_header_id" value="{{ $selectedEbd->id }}">
            <input type="hidden" name="source_type" value="supplier">

            <div class="px-5 py-4 space-y-4">
                {{-- Supplier Select --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">
                        Select Supplier <span class="text-rose-500">*</span>
                    </label>
                    <select name="supplier_id" id="supplier_select_id" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-xs font-medium text-slate-800 dark:text-slate-100 rounded-sm focus:ring-2 focus:ring-indigo-500" required>
                        <option value="">Choose Supplier...</option>
                        @foreach($suppliers ?? [] as $supp)
                            <option value="{{ $supp->id }}">{{ $supp->name }} ({{ $supp->code ?? 'SUP' }})</option>
                        @endforeach
                    </select>
                </div>

                {{-- Template Configuration --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">
                        Import Mapping Template <span class="text-rose-500">*</span>
                    </label>
                    <select name="template_id" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-xs font-medium text-slate-800 dark:text-slate-100 rounded-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="">-- Standard System Template (Default Format) --</option>
                        @foreach($importTemplates ?? [] as $tpl)
                            <option value="{{ $tpl->id }}">
                                {{ $tpl->template_name }} (Rev {{ $tpl->revision ?? '0' }})
                                @if($tpl->customer)
                                    [{{ $tpl->customer->code ?? $tpl->customer->name }}]
                                @else
                                    [Universal]
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Mode Import --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">
                        Import Mode <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <label class="flex items-center gap-2 p-2.5 border border-indigo-200 dark:border-indigo-800 rounded-sm bg-indigo-50/40 dark:bg-indigo-950/40 cursor-pointer hover:border-indigo-400">
                            <input type="radio" name="import_mode" value="new_revision" checked class="text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="block font-bold text-slate-800 dark:text-slate-100 text-[11px]">New Revision</span>
                                <span class="block text-[9px] text-slate-500">Save as Rev 1, Rev 2...</span>
                            </div>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 border border-slate-200 dark:border-slate-700 rounded-sm bg-white dark:bg-slate-900 cursor-pointer hover:border-indigo-400">
                            <input type="radio" name="import_mode" value="overwrite" class="text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="block font-bold text-slate-800 dark:text-slate-100 text-[11px]">Overwrite</span>
                                <span class="block text-[9px] text-slate-500">Update active revision</span>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- File Upload --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">
                        Supplier Quotation File (.xlsx) <span class="text-rose-500">*</span>
                    </label>
                    <div class="dropzone-area relative flex flex-col items-center justify-center p-6 border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-sm bg-slate-50/50 dark:bg-slate-900/50 hover:bg-slate-100/70 dark:hover:bg-slate-800/60 transition-all cursor-pointer group text-center">
                        <input type="file" name="quotation_file" accept=".xlsx,.xls,.csv" class="input-quotation-file absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required>
                        
                        <div class="dropzone-prompt flex flex-col items-center justify-center space-y-2 pointer-events-none">
                            <div class="w-9 h-9 rounded-full bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                                <i class="fa-solid fa-cloud-arrow-up text-base"></i>
                            </div>
                            <div class="text-xs text-slate-600 dark:text-slate-300">
                                <span class="font-semibold text-indigo-600 dark:text-indigo-400">Click to browse file</span> or drag & drop here
                            </div>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500">Supported formats: Excel (.xlsx, .xls, .csv)</p>
                        </div>

                        <div class="dropzone-file-info hidden flex items-center gap-3 p-2 pl-3 pr-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-sm shadow-xs relative z-20 max-w-full">
                            <div class="w-7 h-7 rounded bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                                <i class="fa-solid fa-file-excel text-sm"></i>
                            </div>
                            <div class="min-w-0 text-left pr-2">
                                <p class="dropzone-file-name text-xs font-semibold text-slate-700 dark:text-slate-200 truncate max-w-[180px]"></p>
                                <p class="dropzone-file-size text-[10px] text-slate-400 dark:text-slate-500"></p>
                            </div>
                            <button type="button" class="btn-remove-file w-6 h-6 rounded-full hover:bg-rose-50 dark:hover:bg-rose-950/60 text-slate-400 hover:text-rose-600 flex items-center justify-center transition-colors cursor-pointer ml-auto flex-shrink-0 z-30" title="Remove File">
                                <i class="fa-solid fa-xmark text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Result Alert --}}
                <div class="importResult hidden text-xs font-medium p-3.5 rounded-sm border"></div>
            </div>

            <div class="flex items-center justify-end gap-2 px-5 py-3.5 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-200 dark:border-slate-700">
                <button type="button" onclick="$('#import-supplier-modal').addClass('hidden').removeClass('flex')" class="px-3.5 h-8 text-xs font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-sm transition-colors cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="btn-submit-import inline-flex items-center justify-center gap-1.5 px-4 h-8 bg-indigo-600 hover:bg-indigo-700 text-white rounded-sm text-xs font-semibold shadow-xs active:scale-98 transition-all cursor-pointer">
                    <i class="fa-solid fa-cloud-arrow-up text-xs"></i>
                    <span>Import Supplier Quote</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endif

@push('scripts')
<script>
    function toggleSourceType(type) {
        if (type === 'customer') {
            $('#container-source-customer').removeClass('hidden');
            $('#container-source-supplier').addClass('hidden');
            $('#supplier_id').prop('required', false);
            $('#lbl-source-customer').addClass('border-amber-400 bg-amber-50/40 dark:bg-amber-950/40').removeClass('border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900');
            $('#lbl-source-supplier').removeClass('border-indigo-400 bg-indigo-50/40 dark:bg-indigo-950/40').addClass('border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900');
        } else {
            $('#container-source-customer').addClass('hidden');
            $('#container-source-supplier').removeClass('hidden');
            $('#supplier_id').prop('required', true);
            $('#lbl-source-supplier').addClass('border-indigo-400 bg-indigo-50/40 dark:bg-indigo-950/40').removeClass('border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900');
            $('#lbl-source-customer').removeClass('border-amber-400 bg-amber-50/40 dark:bg-amber-950/40').addClass('border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900');
        }
    }

    function switchCustomerRevision(quoteId) {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('cust_quote', quoteId);
        window.location.search = urlParams.toString();
    }
    $(function() {
        // Part Selection Logic
        $('.btn-select-part').on('click', function() {
            const partId = $(this).data('part-id');
            
            // Highlight selected part button with blue background
            $('.btn-select-part')
                .removeClass('active-part bg-indigo-50 dark:bg-indigo-950/60 border-indigo-500 dark:border-indigo-500 shadow-xs')
                .addClass('bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700/80 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-750');
            
            $(this)
                .addClass('active-part bg-indigo-50 dark:bg-indigo-950/60 border-indigo-500 dark:border-indigo-500 shadow-xs')
                .removeClass('bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700/80 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-750');

            // Show selected part container
            $('.part-container').addClass('hidden');
            $(`#part-container-${partId}`).removeClass('hidden');
        });

        // Tab Switching Logic (Summary vs Side-by-Side Detailed Comparison)
        $('.btn-tab-quote').on('click', function() {
            const targetClass = $(this).data('tab-target');

            // Tab button styles (underlined active tab)
            $('.btn-tab-quote')
                .removeClass('border-indigo-600 text-indigo-600 dark:text-indigo-400 font-bold')
                .addClass('border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:border-slate-300 font-semibold');
            
            $(this)
                .addClass('border-indigo-600 text-indigo-600 dark:text-indigo-400 font-bold')
                .removeClass('border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:border-slate-300 font-semibold');

            // View panel switching
            $('.tab-content-view').addClass('hidden');
            $(`.${targetClass}`).removeClass('hidden');
        });

        // Custom Supplier Dropdown Toggle
        $('#btn-custom-supplier-dropdown').on('click', function(e) {
            e.stopPropagation();
            $('#panel-custom-supplier-dropdown').toggleClass('hidden');
        });

        // Close Custom Dropdown on Outside Click
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#btn-custom-supplier-dropdown, #panel-custom-supplier-dropdown').length) {
                $('#panel-custom-supplier-dropdown').addClass('hidden');
            }
        });

        // Live Search Filter Inside Dropdown
        $('#input-search-supplier-filter').on('input', function() {
            const query = $(this).val().toLowerCase();
            $('.supp-item-label').each(function() {
                const text = $(this).find('.supp-name-text').text().toLowerCase();
                if (text.includes(query)) {
                    $(this).removeClass('hidden').addClass('flex');
                } else {
                    $(this).addClass('hidden').removeClass('flex');
                }
            });
        });

        // Checkbox Item Change & Column Toggle
        $('.chk-supp-filter').on('change', function() {
            const suppId = $(this).data('supp-id');
            const isChecked = $(this).is(':checked');

            if (isChecked) {
                $(`.col-supp-${suppId}`).removeClass('hidden');
            } else {
                $(`.col-supp-${suppId}`).addClass('hidden');
            }

            updateDropdownLabel();
        });

        // Toggle Select All
        $('#chk-toggle-all-suppliers').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.chk-supp-filter').prop('checked', isChecked).trigger('change');
        });

        // Update Label Display on Button
        function updateDropdownLabel() {
            const total = $('.chk-supp-filter').length;
            const checked = $('.chk-supp-filter:checked').length;

            if (total === 0) {
                $('#lbl-custom-supplier-dropdown').text('No Suppliers');
                $('#chk-toggle-all-suppliers').prop('checked', false);
            } else if (checked === total) {
                $('#lbl-custom-supplier-dropdown').text('All Suppliers Selected');
                $('#chk-toggle-all-suppliers').prop('checked', true);
            } else if (checked === 0) {
                $('#lbl-custom-supplier-dropdown').text('No Suppliers Selected');
                $('#chk-toggle-all-suppliers').prop('checked', false);
            } else {
                $('#lbl-custom-supplier-dropdown').text(`${checked} of ${total} Selected`);
                $('#chk-toggle-all-suppliers').prop('checked', false);
            }
        }

        // Initialize Select2 for Supplier ID in Import Modal
        $('#supplier_select_id').select2({
            width: '100%',
            placeholder: 'Search supplier...',
            dropdownParent: $('#import-supplier-modal'),
            ajax: {
                url: '{{ route("management.api.suppliers") }}',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        search: params.term
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.results
                    };
                },
                cache: true
            }
        });

        // Drag and Drop File Upload Logic for all import forms
        $('.dropzone-area').each(function() {
            const $area = $(this);
            const $input = $area.find('.input-quotation-file');
            const $prompt = $area.find('.dropzone-prompt');
            const $fileInfo = $area.find('.dropzone-file-info');
            const $fileName = $area.find('.dropzone-file-name');
            const $fileSize = $area.find('.dropzone-file-size');
            const $btnRemove = $area.find('.btn-remove-file');

            $input.on('dragenter dragover', function() {
                $area.addClass('border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/40');
            }).on('dragleave drop', function() {
                $area.removeClass('border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/40');
            });

            $input.on('change', function() {
                const files = this.files;
                if (files && files.length > 0) {
                    const file = files[0];
                    $fileName.text(file.name);
                    
                    let sizeStr = (file.size / 1024).toFixed(1) + ' KB';
                    if (file.size > 1024 * 1024) {
                        sizeStr = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
                    }
                    $fileSize.text(sizeStr);

                    $prompt.addClass('hidden');
                    $fileInfo.removeClass('hidden').addClass('flex');
                } else {
                    $prompt.removeClass('hidden');
                    $fileInfo.addClass('hidden').removeClass('flex');
                }
            });

            $btnRemove.on('click', function(e) {
                e.stopPropagation();
                e.preventDefault();
                $input.val('');
                $prompt.removeClass('hidden');
                $fileInfo.addClass('hidden').removeClass('flex');
            });
        });

        // Handle AJAX Submit for Import Quotation Forms
        $('.form-import-quotation').on('submit', function(e) {
            e.preventDefault();
            const $form = $(this);
            const $btn = $form.find('.btn-submit-import');
            const $result = $form.find('.importResult');
            const originalHtml = $btn.html();

            $result.addClass('hidden').removeClass('bg-rose-50 text-rose-800 border-rose-200 bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800');
            $btn.prop('disabled', true).html('<i class="fa-solid fa-circle-notch fa-spin text-xs"></i> Processing Import...');

            const formData = new FormData(this);

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    $result.removeClass('hidden')
                           .addClass('bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800')
                           .html(`<div class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-emerald-600"></i><span>${res.message}</span></div>`);
                    
                    setTimeout(function() {
                        window.location.href = res.redirect_url || window.location.href;
                    }, 1000);
                },
                error: function(xhr) {
                    $btn.prop('disabled', false).html(originalHtml);
                    
                    let errorMsg = 'Failed to import quotation data.';
                    if (xhr.responseJSON) {
                        if (xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        } else if (xhr.responseJSON.errors) {
                            const errs = Object.values(xhr.responseJSON.errors).flat();
                            errorMsg = errs.join('<br>');
                        }
                    } else if (xhr.status) {
                        errorMsg = `[Error ${xhr.status}: ${xhr.statusText}] Failed to upload file.`;
                    }

                    $result.removeClass('hidden')
                           .addClass('bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800')
                           .html(`<div class="flex items-start gap-2"><i class="fa-solid fa-circle-exclamation text-rose-600 mt-0.5"></i><div>${errorMsg}</div></div>`);
                }
            });
        });

        // Initial Label Update
        updateDropdownLabel();
    });

    function switchSupplierRevision(supplierId, quoteId) {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('supp_quote_' + supplierId, quoteId);
        window.location.search = urlParams.toString();
    }

    function updateQueryStringParam(key, value) {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set(key, value);
        return window.location.pathname + '?' + urlParams.toString();
    }
</script>
@endpush
@endsection
