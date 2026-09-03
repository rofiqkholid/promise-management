@extends('layouts.app')

@section('title', 'Cost Comparison Matrix Detail · Promise Management')

@section('content')
<style>
    /* CSS-driven visibility for static matrix comparison tables */
    .hide-sales-rev .col-sales-rev {
        display: none !important;
    }
    .hide-customer .col-customer {
        display: none !important;
    }
    .hide-supplier .col-supplier {
        display: none !important;
    }
</style>

<div id="cost-comparison-container" class="flex-1 overflow-y-auto p-4 pt-17.5 space-y-5 transition-colors duration-200">

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- TOP RESPONSIVE HEADER & KPI SUMMARY GRID (40 : 60 RATIO)      --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-2.5 items-stretch">
        {{-- Header Card (lg:col-span-5) --}}
        <div class="lg:col-span-5 bg-white dark:bg-slate-800 rounded-sm border border-slate-300 dark:border-slate-700 overflow-visible relative flex flex-col justify-between">
            {{-- Header Action Strip (Single Row with Title & Grouped Import Dropdown) --}}
            <div class="px-3 py-2 bg-slate-50 dark:bg-slate-900/80 border-b border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider flex items-center justify-between gap-2 rounded-t-sm">
                <span class="whitespace-nowrap truncate text-[11px] font-extrabold text-slate-700 dark:text-slate-200">
                    Cost Estimation & Comparison
                </span>
                <div class="flex items-center gap-2 shrink-0">
                    {{-- Export Excel Button (Colorized) --}}
                    <button type="button" onclick="openCostComparisonExportModal()"
                            class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-sm text-xs font-bold transition-all cursor-pointer shadow-xs whitespace-nowrap active:scale-98" title="Export Customer Quotation to Excel">
                        Export Excel
                    </button>

                    {{-- Grouped Import Dropdown (Clean design with rounded-sm & rotating chevron) --}}
                    <div class="relative inline-block text-left" id="import-dropdown-container">
                        <button type="button" id="import-dropdown-btn" onclick="toggleImportDropdown(event)"
                                class="inline-flex items-center gap-2 px-2.5 py-1.5 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700/60 text-slate-800 dark:text-white border border-slate-300 dark:border-slate-600 rounded-sm text-xs font-semibold transition-colors cursor-pointer shadow-xs whitespace-nowrap active:scale-98">
                            <span>Import</span>
                            <svg id="import-chevron-icon" class="w-3.5 h-3.5 text-slate-500 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        {{-- Dropdown Popup Items (rounded-sm, unclipped, clean vertical list) --}}
                        <div id="import-dropdown-menu" class="hidden absolute right-0 z-50 mt-1.5 w-48 origin-top-right rounded-sm bg-white dark:bg-slate-800 shadow-xl border border-slate-200 dark:border-slate-700 py-1 transition-all">
                            <button type="button" onclick="openImportModal('sales-rev')"
                                    class="w-full text-left px-3.5 py-2 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/60 transition-colors cursor-pointer block">
                                Sales Adjustment
                            </button>
                            <button type="button" onclick="openImportModal('customer')"
                                    class="w-full text-left px-3.5 py-2 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/60 transition-colors cursor-pointer block">
                                Customer Quotation
                            </button>
                            <button type="button" onclick="openImportModal('supplier')"
                                    class="w-full text-left px-3.5 py-2 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/60 transition-colors cursor-pointer block">
                                Supplier Quotation
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Table Grid Structure --}}
            <div class="grid grid-cols-12 divide-x divide-slate-200 dark:divide-slate-700 flex-1">
                {{-- Cell 1: Back + Model & Customer --}}
                <div class="col-span-6 p-2.5 flex items-center gap-2.5 min-w-0 bg-white dark:bg-slate-800">
                    <a href="{{ route('management.product-cost-comparison.index') }}"
                       class="group shrink-0 inline-flex items-center gap-1.5 px-3 h-9 rounded-sm bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 border border-slate-300 dark:border-slate-600 font-bold text-xs text-slate-700 dark:text-slate-200 shadow-2xs transition-all active:scale-95"
                       title="Back to List">
                        <svg class="w-3.5 h-3.5 text-slate-600 dark:text-slate-300 transition-transform group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                        </svg>
                        <span>Back</span>
                    </a>
                    <div class="min-w-0 flex-1">
                        <h1 class="text-sm font-black tracking-tight text-slate-800 dark:text-white leading-tight truncate"
                            title="{{ $comparisonResult['project_model']->name ?? 'Model' }} — {{ $comparisonResult['customer']->code ?? $comparisonResult['customer']->name ?? 'Customer' }}">
                            {{ $comparisonResult['project_model']->name ?? 'Model' }} — {{ $comparisonResult['customer']->code ?? $comparisonResult['customer']->name ?? 'Customer' }}
                        </h1>
                        <span class="text-[11px] text-slate-400 dark:text-slate-500 truncate block">
                            {{ $comparisonResult['customer']->name ?? 'Customer' }}
                        </span>
                    </div>
                </div>

                {{-- Cell 2: Revision & Date --}}
                <div class="col-span-3 p-2 flex flex-col justify-center divide-y divide-slate-100 dark:divide-slate-700/60 bg-slate-50/50 dark:bg-slate-900/30 text-xs text-slate-600 dark:text-slate-300">
                    <div class="py-0.5 leading-tight">
                        EBD Rev: <strong class="text-slate-800 dark:text-white font-bold">{{ $comparisonResult['ebd_header']->revision ?? '0' }}</strong>
                    </div>
                    <div class="py-0.5 leading-tight">
                        Date: <strong class="text-slate-800 dark:text-white font-bold">{{ $comparisonResult['ebd_header']->date ? $comparisonResult['ebd_header']->date->format('d/m/Y') : '-' }}</strong>
                    </div>
                </div>

                {{-- Cell 3: Quantities --}}
                <div class="col-span-3 p-2 flex flex-col justify-center divide-y divide-slate-100 dark:divide-slate-700/60 bg-slate-50/50 dark:bg-slate-900/30 text-xs font-semibold">
                    <div class="py-0.5 text-slate-700 dark:text-slate-200 leading-tight">
                        <span class="font-bold">{{ count($comparisonResult['items']) }} Parts</span>
                    </div>
                    <div class="py-0.5 text-slate-700 dark:text-slate-200 leading-tight">
                        <span class="font-bold">{{ $comparisonResult['tooling']['total_items_count'] ?? 0 }} Tooling Items</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- KPI Cards (lg:col-span-7) --}}
        <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-3 gap-2.5">
            {{-- KPI 1: Engineering Cost (HPP) --}}
            <div class="p-3 bg-white dark:bg-slate-800 rounded-xs border border-slate-300 dark:border-slate-700 flex flex-col justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">
                    Engineering Cost (HPP)
                </span>
                <div class="mt-2">
                    <div id="kpi-val-cogs-eng" class="text-base sm:text-lg font-black text-blue-700 dark:text-blue-300">
                        Rp {{ number_format($comparisonResult['totals']['cogs_eng'], 0, ',', '.') }}
                    </div>
                    <span id="kpi-sub-cogs-eng" class="text-[11px] text-slate-400 block truncate">
                        Direct Manufacturing Cost Baseline
                    </span>
                </div>
            </div>

            {{-- KPI 2: Original Sales Quotation --}}
            <div class="p-3 bg-white dark:bg-slate-800 rounded-xs border border-slate-300 dark:border-slate-700 flex flex-col justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                    Sales Quotation (Base)
                </span>
                <div class="mt-2">
                    <div id="kpi-val-cogs-sales" class="text-base sm:text-lg font-black text-indigo-700 dark:text-indigo-300">
                        Rp {{ number_format($comparisonResult['totals']['cogs_sales'], 0, ',', '.') }}
                    </div>
                    <span id="kpi-sub-cogs-sales" class="text-[11px] text-slate-400 block truncate">
                        Commercial Quotation Baseline
                    </span>
                </div>
            </div>

            {{-- KPI 3: Commercial Profit Margin --}}
            <div class="p-3 bg-white dark:bg-slate-800 rounded-xs border border-slate-300 dark:border-slate-700 flex flex-col justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                    Commercial Profit Margin
                </span>
                <div class="mt-2">
                    <div id="kpi-val-margin-pct" class="text-base sm:text-lg font-black text-emerald-700 dark:text-emerald-300">
                        {{ number_format($comparisonResult['margin_pct'], 2) }}%
                    </div>
                    <span id="kpi-val-margin-idr" class="text-[11px] font-bold text-slate-500 dark:text-slate-400 block truncate">
                        +Rp {{ number_format($comparisonResult['margin_idr'], 0, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- MAIN CONTENT CARD: NAVIGATION TABS & COLUMN VISIBILITY TOGGLES --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    <div class="bg-white dark:bg-slate-800 rounded-xs border border-slate-300 dark:border-slate-700 overflow-hidden">
        {{-- Card Header: Underline Navigation Tabs & Column Visibility Controls --}}
        <div class="border-b border-slate-200 dark:border-slate-700 px-4 bg-slate-50/80 dark:bg-slate-900/50 flex flex-wrap items-center justify-between gap-3">
            {{-- Tabs --}}
            <nav class="flex space-x-6 -mb-px" aria-label="Tabs">
                {{-- Tab 1: Product Cost --}}
                <button type="button" onclick="switchTab('product-cost')" id="tab-btn-product-cost"
                        class="tab-btn inline-flex items-center gap-2 py-3 px-1 border-b-2 font-bold text-xs transition-all cursor-pointer border-blue-600 text-blue-600 dark:text-blue-400">
                    <span>Product Cost Comparison</span>
                    <span id="tab-badge-product-cost"
                          class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 transition-colors">
                        {{ count($comparisonResult['items']) }}
                    </span>
                </button>

                {{-- Tab 2: Tooling Cost --}}
                <button type="button" onclick="switchTab('tooling-cost')" id="tab-btn-tooling-cost"
                        class="tab-btn inline-flex items-center gap-2 py-3 px-1 border-b-2 font-bold text-xs transition-all cursor-pointer border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:border-slate-300 dark:hover:border-slate-600">
                    <span>Tooling Cost Comparison</span>
                    <span id="tab-badge-tooling-cost"
                          class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 transition-colors">
                        {{ $comparisonResult['tooling']['total_items_count'] ?? 0 }}
                    </span>
                </button>
            </nav>

            {{-- Column Visibility Toggles --}}
            <div class="flex items-center gap-3 py-2 text-xs flex-wrap">
                <span class="font-bold text-slate-500 dark:text-slate-400 text-[11px] uppercase tracking-wider">Visible Columns:</span>
                <label class="inline-flex items-center gap-1.5 cursor-pointer font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white">
                    <input type="checkbox" id="toggle-col-sales-rev" checked onchange="toggleSourceVisibility('sales-rev', this.checked)" class="rounded-xs text-violet-600 focus:ring-0">
                    <span>Sales Adjustment</span>
                </label>
                <label class="inline-flex items-center gap-1.5 cursor-pointer font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white">
                    <input type="checkbox" id="toggle-col-customer" checked onchange="toggleSourceVisibility('customer', this.checked)" class="rounded-xs text-amber-600 focus:ring-0">
                    <span>Customer Quotation</span>
                </label>
                <label id="toggle-col-supplier-wrapper" class="hidden inline-flex items-center gap-1.5 cursor-pointer font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white">
                    <input type="checkbox" id="toggle-col-supplier" checked onchange="toggleSourceVisibility('supplier', this.checked)" class="rounded-xs text-purple-600 focus:ring-0">
                    <span>Supplier Quotation</span>
                </label>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- TAB 1: PRODUCT COST COMPARISON CONTENT                        --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div id="tab-content-product-cost" class="p-4 space-y-5">
            {{-- Main Product Cost Matrix --}}
            <div class="overflow-x-auto rounded-xs">
                <table class="w-full text-xs text-left border-collapse" id="table-product-matrix">
                    <thead>
                        <tr class="bg-slate-100 dark:bg-slate-900 border-b border-slate-300 dark:border-slate-700">
                            <th class="w-24 border border-slate-300 dark:border-slate-700 p-2 text-center font-bold text-slate-500 dark:text-slate-400"></th>
                            <th class="text-slate-800 dark:text-slate-200 font-black text-xs uppercase text-center p-2 border border-slate-300 dark:border-slate-700 tracking-wider">
                                Product Cost Stream
                            </th>

                            {{-- 1. Engineering with Revision Selector --}}
                            <th colspan="2" class="col-eng text-slate-800 dark:text-slate-200 font-extrabold text-xs uppercase tracking-wider text-center p-2 border border-slate-300 dark:border-slate-700 bg-blue-50/30 dark:bg-blue-950/20">
                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                    <span>Engineering</span>
                                    @if(isset($comparisonResult['ebd_revisions']) && $comparisonResult['ebd_revisions']->count() > 1)
                                        <select onchange="switchEbdRevision(this.value)" class="text-[10px] font-bold py-0.5 px-1.5 bg-white dark:bg-slate-800 border border-blue-300 dark:border-blue-700 rounded-xs text-blue-700 dark:text-blue-300 cursor-pointer" title="Switch Engineering EBD Revision">
                                            @foreach($comparisonResult['ebd_revisions'] as $eRev)
                                                <option value="{{ $eRev->id }}" {{ ($comparisonResult['ebd_header']->id == $eRev->id) ? 'selected' : '' }}>
                                                    Rev {{ $eRev->revision }} {{ $eRev->is_latest ? '(Latest)' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @else
                                        <span class="px-1.5 py-0.5 bg-blue-100 dark:bg-blue-900/60 text-blue-800 dark:text-blue-200 text-[9px] font-bold rounded-xs">Rev {{ $comparisonResult['ebd_header']->revision ?? '0' }}</span>
                                    @endif
                                </div>
                            </th>

                            {{-- 2. Sales Baseline --}}
                            <th colspan="2" class="col-sales-orig text-slate-800 dark:text-slate-200 font-extrabold text-xs uppercase tracking-wider text-center p-2 border border-slate-300 dark:border-slate-700 bg-indigo-50/30 dark:bg-indigo-950/20">
                                <span>Sales</span>
                            </th>

                            {{-- 3. Sales Adjustment with Revision Selector --}}
                            <th colspan="2" class="col-sales-rev text-slate-800 dark:text-slate-200 font-extrabold text-xs uppercase tracking-wider text-center p-2 border border-slate-300 dark:border-slate-700 bg-violet-50/40 dark:bg-violet-950/20">
                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                    <span>Sales Adjustment</span>
                                    @if(isset($comparisonResult['sales_revisions']) && $comparisonResult['sales_revisions']->count() > 1)
                                        <select onchange="switchRevisionParam('sales_rev_id', this.value)" class="text-[10px] font-bold py-0.5 px-1.5 bg-white dark:bg-slate-800 border border-violet-300 dark:border-violet-700 rounded-xs text-violet-700 dark:text-violet-300 cursor-pointer" title="Select Sales Adjustment Revision">
                                            @foreach($comparisonResult['sales_revisions'] as $idx => $sRev)
                                                <option value="{{ $sRev->id }}" {{ ($comparisonResult['active_sales_rev']?->id == $sRev->id) ? 'selected' : '' }}>
                                                    Rev {{ $sRev->revision }} {{ $idx === 0 ? '(Latest)' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @elseif(isset($comparisonResult['active_sales_rev']))
                                        <span class="px-1.5 py-0.5 bg-violet-100 dark:bg-violet-900/60 text-violet-700 dark:text-violet-300 text-[9px] font-bold rounded-xs">Rev {{ $comparisonResult['active_sales_rev']->revision }}</span>
                                    @else
                                        <span class="text-[10px] font-normal text-slate-400 italic">Not Imported</span>
                                    @endif
                                </div>
                            </th>

                            {{-- 4. Customer Quotation with Revision Selector --}}
                            <th colspan="2" class="col-customer text-slate-800 dark:text-slate-200 font-extrabold text-xs uppercase tracking-wider text-center p-2 border border-slate-300 dark:border-slate-700 bg-amber-50/40 dark:bg-amber-950/20">
                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                    <span>Customer Quotation</span>
                                    @if(isset($comparisonResult['customer_quotations']) && $comparisonResult['customer_quotations']->count() > 1)
                                        <select onchange="switchRevisionParam('cust_quote_id', this.value)" class="text-[10px] font-bold py-0.5 px-1.5 bg-white dark:bg-slate-800 border border-amber-300 dark:border-amber-700 rounded-xs text-amber-700 dark:text-amber-300 cursor-pointer" title="Select Customer Quotation Revision">
                                            @foreach($comparisonResult['customer_quotations'] as $idx => $cQuote)
                                                <option value="{{ $cQuote->id }}" {{ ($comparisonResult['active_customer_quote']?->id == $cQuote->id) ? 'selected' : '' }}>
                                                    Rev {{ $cQuote->revision }} {{ $idx === 0 ? '(Latest)' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @elseif(isset($comparisonResult['active_customer_quote']))
                                        <span class="px-1.5 py-0.5 bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-300 text-[9px] font-bold rounded-xs">Rev {{ $comparisonResult['active_customer_quote']->revision }}</span>
                                    @else
                                        <span class="text-[10px] font-normal text-slate-400 italic">Not Imported</span>
                                    @endif
                                </div>
                            </th>
                        </tr>
                        <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-300 font-bold text-[10px] uppercase border-b border-slate-300 dark:border-slate-700">
                            <th class="border border-slate-300 dark:border-slate-700 p-2 text-center w-24">Stage</th>
                            <th class="border border-slate-300 dark:border-slate-700 p-2">Criteria</th>
                            <th class="col-eng border border-slate-300 dark:border-slate-700 p-1.5 text-center w-16 bg-blue-50/20">Rate</th>
                            <th class="col-eng border border-slate-300 dark:border-slate-700 p-1.5 text-right w-36 bg-blue-50/20">Amount (IDR)</th>
                            <th class="col-sales-orig border border-slate-300 dark:border-slate-700 p-1.5 text-center w-16 bg-indigo-50/20">Rate</th>
                            <th class="col-sales-orig border border-slate-300 dark:border-slate-700 p-1.5 text-right w-36 bg-indigo-50/20">Amount (IDR)</th>
                            <th class="col-sales-rev border border-slate-300 dark:border-slate-700 p-1.5 text-center w-16 bg-violet-50/20">Rate</th>
                            <th class="col-sales-rev border border-slate-300 dark:border-slate-700 p-1.5 text-right w-36 bg-violet-50/20">Amount (IDR)</th>
                            <th class="col-customer border border-slate-300 dark:border-slate-700 p-1.5 text-center w-16 bg-amber-50/20">Rate</th>
                            <th class="col-customer border border-slate-300 dark:border-slate-700 p-1.5 text-right w-36 bg-amber-50/20">Amount (IDR)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        {{-- 1. COGM: Material Cost --}}
                        <tr>
                            <td rowspan="3" class="border border-slate-300 dark:border-slate-700 p-2 text-center font-bold text-xs text-slate-600 dark:text-slate-400 bg-slate-50/50 dark:bg-slate-900/30 align-middle">
                                COGM
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2 font-medium text-slate-700 dark:text-slate-300">
                                Direct Material Cost
                            </td>
                            {{-- Eng --}}
                            <td class="col-eng border border-slate-300 dark:border-slate-700 p-2 text-center text-slate-400">-</td>
                            <td class="col-eng border border-slate-300 dark:border-slate-700 p-2 text-right font-medium text-slate-800 dark:text-slate-200">
                                Rp {{ number_format($comparisonResult['totals']['material_eng'], 2, ',', '.') }}
                            </td>
                            {{-- Sales Orig --}}
                            <td class="col-sales-orig border border-slate-300 dark:border-slate-700 p-2 text-center text-slate-400">-</td>
                            <td class="col-sales-orig border border-slate-300 dark:border-slate-700 p-2 text-right font-medium text-slate-800 dark:text-slate-200">
                                Rp {{ number_format($comparisonResult['totals']['material_sales'], 2, ',', '.') }}
                            </td>
                            {{-- Sales Adj --}}
                            <td class="col-sales-rev border border-slate-300 dark:border-slate-700 p-2 text-center text-slate-400">-</td>
                            <td class="col-sales-rev border border-slate-300 dark:border-slate-700 p-2 text-right font-medium text-slate-800 dark:text-slate-200">
                                @if(isset($comparisonResult['active_sales_rev']))
                                    Rp {{ number_format($comparisonResult['totals']['material_sales_rev'], 2, ',', '.') }}
                                @else
                                    <span class="text-slate-400 italic text-[11px]">—</span>
                                @endif
                            </td>
                            {{-- Customer --}}
                            <td class="col-customer border border-slate-300 dark:border-slate-700 p-2 text-center text-slate-400">-</td>
                            <td class="col-customer border border-slate-300 dark:border-slate-700 p-2 text-right font-medium text-slate-800 dark:text-slate-200">
                                @if(isset($comparisonResult['active_customer_quote']) && $comparisonResult['totals']['material_customer'] > 0)
                                    Rp {{ number_format($comparisonResult['totals']['material_customer'], 2, ',', '.') }}
                                @else
                                    <span class="text-slate-400 italic text-[11px]">—</span>
                                @endif
                            </td>
                        </tr>

                        {{-- 1. COGM: Mfg Process Cost --}}
                        <tr>
                            <td class="border border-slate-300 dark:border-slate-700 p-2 font-medium text-slate-700 dark:text-slate-300">
                                Direct Manufacturing Process Cost
                            </td>
                            {{-- Eng --}}
                            <td class="col-eng border border-slate-300 dark:border-slate-700 p-2 text-center text-slate-400">-</td>
                            <td class="col-eng border border-slate-300 dark:border-slate-700 p-2 text-right font-medium text-slate-800 dark:text-slate-200">
                                Rp {{ number_format($comparisonResult['totals']['mfg_eng'], 2, ',', '.') }}
                            </td>
                            {{-- Sales Orig --}}
                            <td class="col-sales-orig border border-slate-300 dark:border-slate-700 p-2 text-center text-slate-400">-</td>
                            <td class="col-sales-orig border border-slate-300 dark:border-slate-700 p-2 text-right font-medium text-slate-800 dark:text-slate-200">
                                Rp {{ number_format($comparisonResult['totals']['mfg_sales'], 2, ',', '.') }}
                            </td>
                            {{-- Sales Adj --}}
                            <td class="col-sales-rev border border-slate-300 dark:border-slate-700 p-2 text-center text-slate-400">-</td>
                            <td class="col-sales-rev border border-slate-300 dark:border-slate-700 p-2 text-right font-medium text-slate-800 dark:text-slate-200">
                                @if(isset($comparisonResult['active_sales_rev']))
                                    Rp {{ number_format($comparisonResult['totals']['mfg_sales_rev'], 2, ',', '.') }}
                                @else
                                    <span class="text-slate-400 italic text-[11px]">—</span>
                                @endif
                            </td>
                            {{-- Customer --}}
                            <td class="col-customer border border-slate-300 dark:border-slate-700 p-2 text-center text-slate-400">-</td>
                            <td class="col-customer border border-slate-300 dark:border-slate-700 p-2 text-right font-medium text-slate-800 dark:text-slate-200">
                                @if(isset($comparisonResult['active_customer_quote']) && $comparisonResult['totals']['mfg_customer'] > 0)
                                    Rp {{ number_format($comparisonResult['totals']['mfg_customer'], 2, ',', '.') }}
                                @else
                                    <span class="text-slate-400 italic text-[11px]">—</span>
                                @endif
                            </td>
                        </tr>

                        {{-- 1. COGM Subtotal --}}
                        <tr class="bg-slate-50/50 dark:bg-slate-800/40 font-bold">
                            <td class="border border-slate-300 dark:border-slate-700 p-2 text-slate-800 dark:text-slate-200">
                                Subtotal Direct Cost (Material + Mfg)
                            </td>
                            {{-- Eng --}}
                            <td class="col-eng border border-slate-300 dark:border-slate-700 p-2 text-center text-slate-400">-</td>
                            <td class="col-eng border border-slate-300 dark:border-slate-700 p-2 text-right font-bold text-slate-900 dark:text-white">
                                Rp {{ number_format($comparisonResult['totals']['cogm_eng'], 2, ',', '.') }}
                            </td>
                            {{-- Sales Orig --}}
                            <td class="col-sales-orig border border-slate-300 dark:border-slate-700 p-2 text-center text-slate-400">-</td>
                            <td class="col-sales-orig border border-slate-300 dark:border-slate-700 p-2 text-right font-bold text-slate-900 dark:text-white">
                                Rp {{ number_format($comparisonResult['totals']['cogm_sales'], 2, ',', '.') }}
                            </td>
                            {{-- Sales Adj --}}
                            <td class="col-sales-rev border border-slate-300 dark:border-slate-700 p-2 text-center text-slate-400">-</td>
                            <td class="col-sales-rev border border-slate-300 dark:border-slate-700 p-2 text-right font-bold text-slate-900 dark:text-white">
                                @if(isset($comparisonResult['active_sales_rev']))
                                    Rp {{ number_format($comparisonResult['totals']['cogm_sales_rev'], 2, ',', '.') }}
                                @else
                                    <span class="text-slate-400 italic text-[11px]">—</span>
                                @endif
                            </td>
                            {{-- Customer --}}
                            <td class="col-customer border border-slate-300 dark:border-slate-700 p-2 text-center text-slate-400">-</td>
                            <td class="col-customer border border-slate-300 dark:border-slate-700 p-2 text-right font-bold text-slate-900 dark:text-white">
                                @if(isset($comparisonResult['active_customer_quote']) && $comparisonResult['totals']['cogm_customer'] > 0)
                                    Rp {{ number_format($comparisonResult['totals']['cogm_customer'], 2, ',', '.') }}
                                @else
                                    <span class="text-slate-400 italic text-[11px]">—</span>
                                @endif
                            </td>
                        </tr>

                        {{-- 2. Administration: Material Admin --}}
                        <tr>
                            <td rowspan="2" class="border border-slate-300 dark:border-slate-700 p-2 text-center font-bold text-xs text-slate-600 dark:text-slate-400 bg-slate-50/50 dark:bg-slate-900/30 align-middle">
                                Admin
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2 font-medium text-slate-700 dark:text-slate-300">
                                Administration (Material)
                            </td>
                            {{-- Eng --}}
                            <td class="col-eng border border-slate-300 dark:border-slate-700 p-2 text-center font-bold text-slate-700 dark:text-slate-300 bg-slate-50/30 dark:bg-slate-900/20">
                                {{ number_format($comparisonResult['policy_eng']->admin_matrl_pct ?? 0, 2) }}%
                            </td>
                            <td class="col-eng border border-slate-300 dark:border-slate-700 p-2 text-right font-medium text-slate-800 dark:text-slate-200">
                                Rp {{ number_format($comparisonResult['totals']['admin_matrl_eng'], 2, ',', '.') }}
                            </td>
                            {{-- Sales Orig --}}
                            <td class="col-sales-orig border border-slate-300 dark:border-slate-700 p-2 text-center font-bold text-slate-700 dark:text-slate-300 bg-slate-50/30 dark:bg-slate-900/20">
                                {{ number_format($comparisonResult['policy_sales']->admin_matrl_pct ?? 0, 2) }}%
                            </td>
                            <td class="col-sales-orig border border-slate-300 dark:border-slate-700 p-2 text-right font-medium text-slate-800 dark:text-slate-200">
                                Rp {{ number_format($comparisonResult['totals']['admin_matrl_sales'], 2, ',', '.') }}
                            </td>
                            {{-- Sales Adj --}}
                            <td class="col-sales-rev border border-slate-300 dark:border-slate-700 p-2 text-center font-bold text-slate-700 dark:text-slate-300 bg-slate-50/30 dark:bg-slate-900/20">
                                @if(isset($comparisonResult['active_sales_rev']))
                                    {{ number_format($comparisonResult['active_sales_rev']->admin_matrl_pct ?? $comparisonResult['policy_sales']->admin_matrl_pct ?? 0, 2) }}%
                                @else
                                    -
                                @endif
                            </td>
                            <td class="col-sales-rev border border-slate-300 dark:border-slate-700 p-2 text-right font-medium text-slate-800 dark:text-slate-200">
                                @if(isset($comparisonResult['active_sales_rev']))
                                    Rp {{ number_format($comparisonResult['totals']['admin_matrl_sales_rev'], 2, ',', '.') }}
                                @else
                                    <span class="text-slate-400 italic text-[11px]">—</span>
                                @endif
                            </td>
                            {{-- Customer --}}
                            <td class="col-customer border border-slate-300 dark:border-slate-700 p-2 text-center font-bold text-slate-700 dark:text-slate-300 bg-slate-50/30 dark:bg-slate-900/20">
                                @if(isset($comparisonResult['active_customer_quote']))
                                    {{ number_format($comparisonResult['active_customer_quote']->admin_matrl_pct ?? 0, 2) }}%
                                @else
                                    -
                                @endif
                            </td>
                            <td class="col-customer border border-slate-300 dark:border-slate-700 p-2 text-right font-medium text-slate-800 dark:text-slate-200">
                                @if(isset($comparisonResult['active_customer_quote']) && $comparisonResult['totals']['admin_matrl_customer'] > 0)
                                    Rp {{ number_format($comparisonResult['totals']['admin_matrl_customer'], 2, ',', '.') }}
                                @else
                                    <span class="text-slate-400 italic text-[11px]">—</span>
                                @endif
                            </td>
                        </tr>

                        {{-- 2. Administration: Mfg Admin --}}
                        <tr>
                            <td class="border border-slate-300 dark:border-slate-700 p-2 font-medium text-slate-700 dark:text-slate-300">
                                Administration (Manufacturing)
                            </td>
                            {{-- Eng --}}
                            <td class="col-eng border border-slate-300 dark:border-slate-700 p-2 text-center font-bold text-slate-700 dark:text-slate-300 bg-slate-50/30 dark:bg-slate-900/20">
                                {{ number_format($comparisonResult['policy_eng']->admin_mfg_pct ?? 0, 2) }}%
                            </td>
                            <td class="col-eng border border-slate-300 dark:border-slate-700 p-2 text-right font-medium text-slate-800 dark:text-slate-200">
                                Rp {{ number_format($comparisonResult['totals']['admin_mfg_eng'], 2, ',', '.') }}
                            </td>
                            {{-- Sales Orig --}}
                            <td class="col-sales-orig border border-slate-300 dark:border-slate-700 p-2 text-center font-bold text-slate-700 dark:text-slate-300 bg-slate-50/30 dark:bg-slate-900/20">
                                {{ number_format($comparisonResult['policy_sales']->admin_mfg_pct ?? 0, 2) }}%
                            </td>
                            <td class="col-sales-orig border border-slate-300 dark:border-slate-700 p-2 text-right font-medium text-slate-800 dark:text-slate-200">
                                Rp {{ number_format($comparisonResult['totals']['admin_mfg_sales'], 2, ',', '.') }}
                            </td>
                            {{-- Sales Adj --}}
                            <td class="col-sales-rev border border-slate-300 dark:border-slate-700 p-2 text-center font-bold text-slate-700 dark:text-slate-300 bg-slate-50/30 dark:bg-slate-900/20">
                                @if(isset($comparisonResult['active_sales_rev']))
                                    {{ number_format($comparisonResult['active_sales_rev']->admin_mfg_pct ?? $comparisonResult['policy_sales']->admin_mfg_pct ?? 0, 2) }}%
                                @else
                                    -
                                @endif
                            </td>
                            <td class="col-sales-rev border border-slate-300 dark:border-slate-700 p-2 text-right font-medium text-slate-800 dark:text-slate-200">
                                @if(isset($comparisonResult['active_sales_rev']))
                                    Rp {{ number_format($comparisonResult['totals']['admin_mfg_sales_rev'], 2, ',', '.') }}
                                @else
                                    <span class="text-slate-400 italic text-[11px]">—</span>
                                @endif
                            </td>
                            {{-- Customer --}}
                            <td class="col-customer border border-slate-300 dark:border-slate-700 p-2 text-center font-bold text-slate-700 dark:text-slate-300 bg-slate-50/30 dark:bg-slate-900/20">
                                @if(isset($comparisonResult['active_customer_quote']))
                                    {{ number_format($comparisonResult['active_customer_quote']->admin_mfg_pct ?? 0, 2) }}%
                                @else
                                    -
                                @endif
                            </td>
                            <td class="col-customer border border-slate-300 dark:border-slate-700 p-2 text-right font-medium text-slate-800 dark:text-slate-200">
                                @if(isset($comparisonResult['active_customer_quote']) && $comparisonResult['totals']['admin_mfg_customer'] > 0)
                                    Rp {{ number_format($comparisonResult['totals']['admin_mfg_customer'], 2, ',', '.') }}
                                @else
                                    <span class="text-slate-400 italic text-[11px]">—</span>
                                @endif
                            </td>
                        </tr>

                        {{-- 3. Others: O/H + Profit --}}
                        <tr>
                            <td class="border border-slate-300 dark:border-slate-700 p-2 text-center font-bold text-xs text-slate-600 dark:text-slate-400 bg-slate-50/50 dark:bg-slate-900/30 align-middle">
                                Others
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2 font-medium text-slate-700 dark:text-slate-300">
                                Overhead & Profit (O/H)
                            </td>
                            {{-- Eng --}}
                            <td class="col-eng border border-slate-300 dark:border-slate-700 p-2 text-center font-bold text-slate-700 dark:text-slate-300 bg-slate-50/30 dark:bg-slate-900/20">
                                {{ number_format($comparisonResult['policy_eng']->oh_profit_pct ?? 0, 2) }}%
                            </td>
                            <td class="col-eng border border-slate-300 dark:border-slate-700 p-2 text-right font-semibold text-slate-800 dark:text-slate-200">
                                Rp {{ number_format($comparisonResult['totals']['oh_profit_eng'], 2, ',', '.') }}
                            </td>
                            {{-- Sales Orig --}}
                            <td class="col-sales-orig border border-slate-300 dark:border-slate-700 p-2 text-center font-bold text-slate-700 dark:text-slate-300 bg-slate-50/30 dark:bg-slate-900/20">
                                {{ number_format($comparisonResult['policy_sales']->oh_profit_pct ?? 0, 2) }}%
                            </td>
                            <td class="col-sales-orig border border-slate-300 dark:border-slate-700 p-2 text-right font-semibold text-slate-800 dark:text-slate-200">
                                Rp {{ number_format($comparisonResult['totals']['oh_profit_sales'], 2, ',', '.') }}
                            </td>
                            {{-- Sales Adj --}}
                            <td class="col-sales-rev border border-slate-300 dark:border-slate-700 p-2 text-center font-bold text-slate-700 dark:text-slate-300 bg-slate-50/30 dark:bg-slate-900/20">
                                @if(isset($comparisonResult['active_sales_rev']))
                                    {{ number_format($comparisonResult['active_sales_rev']->oh_profit_pct ?? $comparisonResult['policy_sales']->oh_profit_pct ?? 0, 2) }}%
                                @else
                                    -
                                @endif
                            </td>
                            <td class="col-sales-rev border border-slate-300 dark:border-slate-700 p-2 text-right font-semibold text-slate-800 dark:text-slate-200">
                                @if(isset($comparisonResult['active_sales_rev']))
                                    Rp {{ number_format($comparisonResult['totals']['oh_profit_sales_rev'], 2, ',', '.') }}
                                @else
                                    <span class="text-slate-400 italic text-[11px]">—</span>
                                @endif
                            </td>
                            {{-- Customer --}}
                            <td class="col-customer border border-slate-300 dark:border-slate-700 p-2 text-center font-bold text-slate-700 dark:text-slate-300 bg-slate-50/30 dark:bg-slate-900/20">
                                @if(isset($comparisonResult['active_customer_quote']))
                                    {{ number_format($comparisonResult['active_customer_quote']->oh_profit_pct ?? 0, 2) }}%
                                @else
                                    -
                                @endif
                            </td>
                            <td class="col-customer border border-slate-300 dark:border-slate-700 p-2 text-right font-semibold text-slate-800 dark:text-slate-200">
                                @if(isset($comparisonResult['active_customer_quote']) && $comparisonResult['totals']['oh_profit_customer'] > 0)
                                    Rp {{ number_format($comparisonResult['totals']['oh_profit_customer'], 2, ',', '.') }}
                                @else
                                    <span class="text-slate-400 italic text-[11px]">—</span>
                                @endif
                            </td>
                        </tr>

                        {{-- 4. COGS Total --}}
                        <tr class="bg-slate-50 dark:bg-slate-900/80 font-black text-xs">
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-center text-slate-800 dark:text-white uppercase tracking-wider">
                                COGS
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-slate-800 dark:text-white font-black">
                                Total Product COGS (COGM + Admin + OH)
                            </td>
                            {{-- Eng --}}
                            <td class="col-eng border border-slate-300 dark:border-slate-700 p-2 text-center text-slate-400">-</td>
                            <td class="col-eng border border-slate-300 dark:border-slate-700 p-2 text-right text-blue-700 dark:text-blue-300 font-black">
                                Rp {{ number_format($comparisonResult['totals']['cogs_eng'], 2, ',', '.') }}
                            </td>
                            {{-- Sales Orig --}}
                            <td class="col-sales-orig border border-slate-300 dark:border-slate-700 p-2 text-center text-slate-400">-</td>
                            <td class="col-sales-orig border border-slate-300 dark:border-slate-700 p-2 text-right text-indigo-700 dark:text-indigo-300 font-black">
                                Rp {{ number_format($comparisonResult['totals']['cogs_sales'], 2, ',', '.') }}
                            </td>
                            {{-- Sales Adj --}}
                            <td class="col-sales-rev border border-slate-300 dark:border-slate-700 p-2 text-center text-slate-400">-</td>
                            <td class="col-sales-rev border border-slate-300 dark:border-slate-700 p-2 text-right text-violet-700 dark:text-violet-300 font-black">
                                @if(isset($comparisonResult['active_sales_rev']))
                                    Rp {{ number_format($comparisonResult['totals']['cogs_sales_rev'], 2, ',', '.') }}
                                @else
                                    <span class="text-slate-400 italic text-[11px]">—</span>
                                @endif
                            </td>
                            {{-- Customer --}}
                            <td class="col-customer border border-slate-300 dark:border-slate-700 p-2 text-center text-slate-400">-</td>
                            <td class="col-customer border border-slate-300 dark:border-slate-700 p-2 text-right text-amber-700 dark:text-amber-300 font-black">
                                @if(isset($comparisonResult['active_customer_quote']) && $comparisonResult['totals']['cogs_customer'] > 0)
                                    Rp {{ number_format($comparisonResult['totals']['cogs_customer'], 2, ',', '.') }}
                                @else
                                    <span class="text-slate-400 italic text-[11px]">—</span>
                                @endif
                            </td>
                        </tr>

                        {{-- 5. Margin vs Engineering --}}
                        <tr class="bg-slate-50/70 dark:bg-slate-900/50 font-bold">
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-center text-slate-700 dark:text-slate-300 uppercase">
                                Margin
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 font-bold text-slate-800 dark:text-slate-200">
                                Margin vs Engineering
                            </td>
                            {{-- Eng --}}
                            <td colspan="2" class="col-eng border border-slate-300 dark:border-slate-700 p-2 text-center text-slate-400 font-medium italic">
                                Baseline
                            </td>
                            {{-- Sales Orig Margin --}}
                            <td colspan="2" class="col-sales-orig border border-slate-300 dark:border-slate-700 p-2 text-right">
                                @if($comparisonResult['margin_idr'] >= 0)
                                    <span class="text-emerald-600 dark:text-emerald-400 font-black">+Rp {{ number_format($comparisonResult['margin_idr'], 0, ',', '.') }}</span>
                                    <span class="ml-1 px-1.5 py-0.5 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 rounded-xs text-[10px] font-black">+{{ number_format($comparisonResult['margin_pct'], 2) }}%</span>
                                @else
                                    <span class="text-rose-600 dark:text-rose-400 font-black">Rp {{ number_format($comparisonResult['margin_idr'], 0, ',', '.') }}</span>
                                    <span class="ml-1 px-1.5 py-0.5 bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 rounded-xs text-[10px] font-black">{{ number_format($comparisonResult['margin_pct'], 2) }}%</span>
                                @endif
                            </td>
                            {{-- Sales Adj Margin --}}
                            <td colspan="2" class="col-sales-rev border border-slate-300 dark:border-slate-700 p-2 text-right">
                                @if(isset($comparisonResult['active_sales_rev']))
                                    @php
                                        $revMarginIdr = $comparisonResult['totals']['cogs_sales_rev'] - $comparisonResult['totals']['cogs_eng'];
                                        $revMarginPct = $comparisonResult['totals']['cogs_sales_rev'] > 0 ? ($revMarginIdr / $comparisonResult['totals']['cogs_sales_rev']) * 100 : 0;
                                    @endphp
                                    @if($revMarginIdr >= 0)
                                        <span class="text-emerald-600 dark:text-emerald-400 font-black">+Rp {{ number_format($revMarginIdr, 0, ',', '.') }}</span>
                                        <span class="ml-1 px-1.5 py-0.5 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 rounded-xs text-[10px] font-black">+{{ number_format($revMarginPct, 2) }}%</span>
                                    @else
                                        <span class="text-rose-600 dark:text-rose-400 font-black">Rp {{ number_format($revMarginIdr, 0, ',', '.') }}</span>
                                        <span class="ml-1 px-1.5 py-0.5 bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 rounded-xs text-[10px] font-black">{{ number_format($revMarginPct, 2) }}%</span>
                                    @endif
                                @else
                                    <span class="text-slate-400 italic text-[11px]">—</span>
                                @endif
                            </td>
                            {{-- Customer Margin --}}
                            <td colspan="2" class="col-customer border border-slate-300 dark:border-slate-700 p-2 text-right">
                                @if(isset($comparisonResult['active_customer_quote']) && $comparisonResult['totals']['cogs_customer'] > 0)
                                    @php
                                        $custMarginIdr = $comparisonResult['totals']['cogs_customer'] - $comparisonResult['totals']['cogs_eng'];
                                        $custMarginPct = $comparisonResult['totals']['cogs_customer'] > 0 ? ($custMarginIdr / $comparisonResult['totals']['cogs_customer']) * 100 : 0;
                                    @endphp
                                    @if($custMarginIdr >= 0)
                                        <span class="text-emerald-600 dark:text-emerald-400 font-black">+Rp {{ number_format($custMarginIdr, 0, ',', '.') }}</span>
                                        <span class="ml-1 px-1.5 py-0.5 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 rounded-xs text-[10px] font-black">+{{ number_format($custMarginPct, 2) }}%</span>
                                    @else
                                        <span class="text-rose-600 dark:text-rose-400 font-black">Rp {{ number_format($custMarginIdr, 0, ',', '.') }}</span>
                                        <span class="ml-1 px-1.5 py-0.5 bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 rounded-xs text-[10px] font-black">{{ number_format($custMarginPct, 2) }}%</span>
                                    @endif
                                @else
                                    <span class="text-slate-400 italic text-[11px]">—</span>
                                @endif
                            </td>
                        </tr>

                        {{-- 6. Standard Margin Status --}}
                        <tr class="bg-slate-50/70 dark:bg-slate-900/50 font-bold">
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-center text-slate-700 dark:text-slate-300 uppercase">
                                Std Margin
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-slate-800 dark:text-slate-200">
                                Target Policy: <strong>Min. {{ number_format($comparisonResult['target_margin_sales'], 2) }}%</strong>
                            </td>
                            <td colspan="2" class="col-eng border border-slate-300 dark:border-slate-700 p-2 text-center text-slate-400 italic text-[10px]">
                                —
                            </td>
                            <td colspan="2" class="col-sales-orig border border-slate-300 dark:border-slate-700 p-2 text-center">
                                <span class="px-2 py-0.5 text-[10px] font-black rounded-xs {{ $comparisonResult['status_badge'] }}">
                                    {{ $comparisonResult['status'] }}
                                </span>
                            </td>
                            <td colspan="2" class="col-sales-rev border border-slate-300 dark:border-slate-700 p-2 text-center">
                                @if(isset($comparisonResult['active_sales_rev']))
                                    @php
                                        $revMarginPct = $comparisonResult['totals']['cogs_sales_rev'] > 0 ? (($comparisonResult['totals']['cogs_sales_rev'] - $comparisonResult['totals']['cogs_eng']) / $comparisonResult['totals']['cogs_sales_rev']) * 100 : 0;
                                        $revPass = ($revMarginPct >= $comparisonResult['target_margin_sales']);
                                    @endphp
                                    <span class="px-2 py-0.5 text-[10px] font-black rounded-xs {{ $revPass ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300' }}">
                                        {{ $revPass ? 'PASSED' : 'ALERT' }}
                                    </span>
                                @else
                                    <span class="text-slate-400 italic text-[10px]">—</span>
                                @endif
                            </td>
                            <td colspan="2" class="col-customer border border-slate-300 dark:border-slate-700 p-2 text-center">
                                @if(isset($comparisonResult['active_customer_quote']) && $comparisonResult['totals']['cogs_customer'] > 0)
                                    @php
                                        $custMarginPct = $comparisonResult['totals']['cogs_customer'] > 0 ? (($comparisonResult['totals']['cogs_customer'] - $comparisonResult['totals']['cogs_eng']) / $comparisonResult['totals']['cogs_customer']) * 100 : 0;
                                        $custPass = ($custMarginPct >= $comparisonResult['target_margin_sales']);
                                    @endphp
                                    <span class="px-2 py-0.5 text-[10px] font-black rounded-xs {{ $custPass ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300' }}">
                                        {{ $custPass ? 'PASSED' : 'BELOW TARGET' }}
                                    </span>
                                @else
                                    <span class="text-slate-400 italic text-[10px]">—</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Detailed Per-Part Breakdown Table with x-table Component and DataTable --}}
            <div>
                <x-table id="part-breakdown-table" class="w-full text-xs text-left border-collapse">
                    <thead>
                        {{-- Row 1: Table Group Headers --}}
                        <tr class="border-b border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-900 text-[10px] font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                            <th colspan="2" class="px-2 py-2 text-center border-r border-slate-300 dark:border-slate-700 bg-slate-200/50 dark:bg-slate-800">
                                Part Specifications
                            </th>
                            <th colspan="3" class="px-2 py-2 text-center border-r border-slate-300 dark:border-slate-700 bg-blue-100/50 dark:bg-blue-950/40 text-blue-900 dark:text-blue-200">
                                Engineering
                            </th>
                            <th colspan="3" class="px-2 py-2 text-center border-r border-slate-300 dark:border-slate-700 bg-indigo-100/50 dark:bg-indigo-950/40 text-indigo-900 dark:text-indigo-200">
                                Sales
                            </th>
                            <th colspan="3" id="th-group-sales-rev" class="px-2 py-2 text-center border-r border-slate-300 dark:border-slate-700 bg-violet-100/50 dark:bg-violet-950/40 text-violet-900 dark:text-violet-200">
                                Sales Adjustment
                            </th>
                            <th colspan="3" id="th-group-customer" class="px-2 py-2 text-center border-r border-slate-300 dark:border-slate-700 bg-amber-100/50 dark:bg-amber-950/40 text-amber-900 dark:text-amber-200">
                                Customer Quotation
                            </th>
                            <th colspan="2" class="px-2 py-2 text-center bg-slate-200/50 dark:bg-slate-800 text-slate-800 dark:text-slate-200">
                                Profitability
                            </th>
                        </tr>
                        {{-- Row 2: Individual Column Headers (Exact 1-to-1 Column Mapping for DataTables) --}}
                        <tr class="border-b-2 border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 text-[10px] font-bold uppercase">
                            <th class="px-2 py-2 w-8 text-center border-r border-slate-200 dark:border-slate-700">No.</th>
                            <th class="px-2.5 py-2 border-r border-slate-200 dark:border-slate-700 min-w-[170px]">Part Number & Name</th>

                            {{-- Engineering (HPP) --}}
                            <th class="px-1.5 py-1.5 text-right border-r border-slate-200 dark:border-slate-700 bg-blue-50/30">Mat.</th>
                            <th class="px-1.5 py-1.5 text-right border-r border-slate-200 dark:border-slate-700 bg-blue-50/30">Mfg.</th>
                            <th class="px-1.5 py-1.5 text-right border-r border-slate-200 dark:border-slate-700 font-black text-blue-900 dark:text-blue-200 bg-blue-50/30">COGS</th>

                            {{-- Sales (Original) --}}
                            <th class="px-1.5 py-1.5 text-right border-r border-slate-200 dark:border-slate-700 bg-indigo-50/30">Mat.</th>
                            <th class="px-1.5 py-1.5 text-right border-r border-slate-200 dark:border-slate-700 bg-indigo-50/30">Mfg.</th>
                            <th class="px-1.5 py-1.5 text-right border-r border-slate-200 dark:border-slate-700 font-black text-indigo-900 dark:text-indigo-200 bg-indigo-50/30">COGS</th>

                            {{-- Sales Adjustment --}}
                            <th class="px-1.5 py-1.5 text-right border-r border-slate-200 dark:border-slate-700 bg-violet-50/30">Mat.</th>
                            <th class="px-1.5 py-1.5 text-right border-r border-slate-200 dark:border-slate-700 bg-violet-50/30">Mfg.</th>
                            <th class="px-1.5 py-1.5 text-right border-r border-slate-200 dark:border-slate-700 font-black text-violet-700 dark:text-violet-300 bg-violet-50/30">COGS</th>

                            {{-- Customer Quotation --}}
                            <th class="px-1.5 py-1.5 text-right border-r border-slate-200 dark:border-slate-700 bg-amber-50/30">Mat.</th>
                            <th class="px-1.5 py-1.5 text-right border-r border-slate-200 dark:border-slate-700 bg-amber-50/30">Mfg.</th>
                            <th class="px-1.5 py-1.5 text-right border-r border-slate-200 dark:border-slate-700 font-black text-amber-700 dark:text-amber-300 bg-amber-50/30">COGS</th>

                            {{-- Profitability --}}
                            <th class="px-1.5 py-1.5 text-right border-r border-slate-200 dark:border-slate-700">Margin (IDR)</th>
                            <th class="px-1.5 py-1.5 text-right font-black text-slate-800 dark:text-slate-100">Margin (%)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        {{-- Loaded via Server-Side DataTables AJAX --}}
                    </tbody>
                </x-table>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- TAB 2: TOOLING COST COMPARISON CONTENT                        --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div id="tab-content-tooling-cost" class="hidden p-4 space-y-5">
            {{-- Main Tooling Cost Matrix --}}
            <div class="overflow-x-auto rounded-xs">
                <table class="w-full text-xs text-left border-collapse" id="table-tooling-matrix">
                    <thead>
                        <tr class="bg-slate-100 dark:bg-slate-900 border-b border-slate-300 dark:border-slate-700">
                            <th class="w-24 border border-slate-300 dark:border-slate-700 p-2 text-center font-bold text-slate-500 dark:text-slate-400"></th>
                            <th class="text-slate-800 dark:text-slate-200 font-black text-xs uppercase text-center p-2 border border-slate-300 dark:border-slate-700 tracking-wider">
                                Tooling Cost Stream
                            </th>

                            {{-- 1. Engineering with Revision Selector --}}
                            <th colspan="2" class="col-eng text-slate-800 dark:text-slate-200 font-extrabold text-xs uppercase tracking-wider text-center p-2 border border-slate-300 dark:border-slate-700 bg-blue-50/30 dark:bg-blue-950/20">
                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                    <span>Engineering</span>
                                    @if(isset($comparisonResult['ebd_revisions']) && $comparisonResult['ebd_revisions']->count() > 1)
                                        <select onchange="switchEbdRevision(this.value)" class="text-[10px] font-bold py-0.5 px-1.5 bg-white dark:bg-slate-800 border border-blue-300 dark:border-blue-700 rounded-xs text-blue-700 dark:text-blue-300 cursor-pointer" title="Switch Engineering EBD Revision">
                                            @foreach($comparisonResult['ebd_revisions'] as $eRev)
                                                <option value="{{ $eRev->id }}" {{ ($comparisonResult['ebd_header']->id == $eRev->id) ? 'selected' : '' }}>
                                                    Rev {{ $eRev->revision }} {{ $eRev->is_latest ? '(Latest)' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @else
                                        <span class="px-1.5 py-0.5 bg-blue-100 dark:bg-blue-900/60 text-blue-800 dark:text-blue-200 text-[9px] font-bold rounded-xs">Rev {{ $comparisonResult['ebd_header']->revision ?? '0' }}</span>
                                    @endif
                                </div>
                            </th>

                            {{-- 2. Sales Baseline --}}
                            <th colspan="2" class="col-sales-orig text-slate-800 dark:text-slate-200 font-extrabold text-xs uppercase tracking-wider text-center p-2 border border-slate-300 dark:border-slate-700 bg-indigo-50/30 dark:bg-indigo-950/20">
                                <span>Sales</span>
                            </th>

                            {{-- 3. Sales Adjustment with Revision Selector --}}
                            <th colspan="2" class="col-sales-rev text-slate-800 dark:text-slate-200 font-extrabold text-xs uppercase tracking-wider text-center p-2 border border-slate-300 dark:border-slate-700 bg-violet-50/40 dark:bg-violet-950/20">
                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                    <span>Sales Adjustment</span>
                                    @if(isset($comparisonResult['sales_revisions']) && $comparisonResult['sales_revisions']->count() > 1)
                                        <select onchange="switchRevisionParam('sales_rev_id', this.value)" class="text-[10px] font-bold py-0.5 px-1.5 bg-white dark:bg-slate-800 border border-violet-300 dark:border-violet-700 rounded-xs text-violet-700 dark:text-violet-300 cursor-pointer" title="Select Sales Adjustment Revision">
                                            @foreach($comparisonResult['sales_revisions'] as $idx => $sRev)
                                                <option value="{{ $sRev->id }}" {{ ($comparisonResult['active_sales_rev']?->id == $sRev->id) ? 'selected' : '' }}>
                                                    Rev {{ $sRev->revision }} {{ $idx === 0 ? '(Latest)' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @elseif(isset($comparisonResult['active_sales_rev']))
                                        <span class="px-1.5 py-0.5 bg-violet-100 dark:bg-violet-900/60 text-violet-700 dark:text-violet-300 text-[9px] font-bold rounded-xs">Rev {{ $comparisonResult['active_sales_rev']->revision }}</span>
                                    @else
                                        <span class="text-[10px] font-normal text-slate-400 italic">Not Imported</span>
                                    @endif
                                </div>
                            </th>

                            {{-- 4. Customer Quotation with Revision Selector --}}
                            <th colspan="2" class="col-customer text-slate-800 dark:text-slate-200 font-extrabold text-xs uppercase tracking-wider text-center p-2 border border-slate-300 dark:border-slate-700 bg-amber-50/40 dark:bg-amber-950/20">
                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                    <span>Customer Quotation</span>
                                    @if(isset($comparisonResult['customer_quotations']) && $comparisonResult['customer_quotations']->count() > 1)
                                        <select onchange="switchRevisionParam('cust_quote_id', this.value)" class="text-[10px] font-bold py-0.5 px-1.5 bg-white dark:bg-slate-800 border border-amber-300 dark:border-amber-700 rounded-xs text-amber-700 dark:text-amber-300 cursor-pointer" title="Select Customer Quotation Revision">
                                            @foreach($comparisonResult['customer_quotations'] as $idx => $cQuote)
                                                <option value="{{ $cQuote->id }}" {{ ($comparisonResult['active_customer_quote']?->id == $cQuote->id) ? 'selected' : '' }}>
                                                    Rev {{ $cQuote->revision }} {{ $idx === 0 ? '(Latest)' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @elseif(isset($comparisonResult['active_customer_quote']))
                                        <span class="px-1.5 py-0.5 bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-300 text-[9px] font-bold rounded-xs">Rev {{ $comparisonResult['active_customer_quote']->revision }}</span>
                                    @else
                                        <span class="text-[10px] font-normal text-slate-400 italic">Not Imported</span>
                                    @endif
                                </div>
                            </th>

                            {{-- 5. Supplier Quotation with Supplier / Rank Selector --}}
                            <th colspan="2" class="col-supplier text-slate-800 dark:text-slate-200 font-extrabold text-xs uppercase tracking-wider text-center p-2 border border-slate-300 dark:border-slate-700 bg-purple-50/30 dark:bg-purple-950/20">
                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                    <span>Supplier Quotation</span>
                                    @if(isset($comparisonResult['supplier_quotations']) && $comparisonResult['supplier_quotations']->count() > 1)
                                        <select onchange="switchRevisionParam('supp_quote_id', this.value)" class="text-[10px] font-bold py-0.5 px-1.5 bg-white dark:bg-slate-800 border border-purple-300 dark:border-purple-700 rounded-xs text-purple-700 dark:text-purple-300 cursor-pointer" title="Select Quoting Supplier">
                                            @foreach($comparisonResult['supplier_quotations'] as $sq)
                                                <option value="{{ $sq->id }}" {{ ($comparisonResult['active_supplier_quote']?->id == $sq->id) ? 'selected' : '' }}>
                                                    #{{ $sq->worth_rank }} {{ $sq->supplier_name }} (Rev {{ $sq->revision }})
                                                </option>
                                            @endforeach
                                        </select>
                                    @elseif(isset($comparisonResult['active_supplier_quote']))
                                        <span class="px-1.5 py-0.5 bg-purple-100 dark:bg-purple-900/60 text-purple-700 dark:text-purple-300 text-[9px] font-bold rounded-xs">{{ $comparisonResult['active_supplier_quote']->supplier_name }}</span>
                                    @else
                                        <span class="text-[10px] font-normal text-slate-400 italic">Not Imported</span>
                                    @endif
                                </div>
                            </th>
                        </tr>
                        <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-300 font-bold text-[10px] uppercase border-b border-slate-300 dark:border-slate-700">
                            <th class="border border-slate-300 dark:border-slate-700 p-2 text-center w-24">Stage</th>
                            <th class="border border-slate-300 dark:border-slate-700 p-2">Criteria</th>
                            <th class="col-eng border border-slate-300 dark:border-slate-700 p-1.5 text-center w-16 bg-blue-50/20">Rate</th>
                            <th class="col-eng border border-slate-300 dark:border-slate-700 p-1.5 text-right w-36 bg-blue-50/20">Amount (IDR)</th>
                            <th class="col-sales-orig border border-slate-300 dark:border-slate-700 p-1.5 text-center w-16 bg-indigo-50/20">Rate</th>
                            <th class="col-sales-orig border border-slate-300 dark:border-slate-700 p-1.5 text-right w-36 bg-indigo-50/20">Amount (IDR)</th>
                            <th class="col-sales-rev border border-slate-300 dark:border-slate-700 p-1.5 text-center w-16 bg-violet-50/20">Rate</th>
                            <th class="col-sales-rev border border-slate-300 dark:border-slate-700 p-1.5 text-right w-36 bg-violet-50/20">Amount (IDR)</th>
                            <th class="col-customer border border-slate-300 dark:border-slate-700 p-1.5 text-center w-16 bg-amber-50/20">Rate</th>
                            <th class="col-customer border border-slate-300 dark:border-slate-700 p-1.5 text-right w-36 bg-amber-50/20">Amount (IDR)</th>
                            <th class="col-supplier border border-slate-300 dark:border-slate-700 p-1.5 text-center w-16 bg-purple-50/20">Rate</th>
                            <th class="col-supplier border border-slate-300 dark:border-slate-700 p-1.5 text-right w-36 bg-purple-50/20">Amount (IDR)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        {{-- 1. COGM: Tooling --}}
                        <tr>
                            <td class="border border-slate-300 dark:border-slate-700 p-2 text-center font-bold text-xs text-slate-600 dark:text-slate-400 bg-slate-50/50 dark:bg-slate-900/30 align-middle">
                                COGM
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2 font-medium text-slate-700 dark:text-slate-300">
                                Tooling Cost (Dies / Jigs / Checking Fixtures)
                            </td>
                            {{-- Eng --}}
                            <td class="col-eng border border-slate-300 dark:border-slate-700 p-2 text-center text-slate-400">-</td>
                            <td class="col-eng border border-slate-300 dark:border-slate-700 p-2 text-right font-medium text-slate-800 dark:text-slate-200">
                                Rp {{ number_format($comparisonResult['tooling']['cogm_eng'], 2, ',', '.') }}
                            </td>
                            {{-- Sales Orig --}}
                            <td class="col-sales-orig border border-slate-300 dark:border-slate-700 p-2 text-center text-slate-400">-</td>
                            <td class="col-sales-orig border border-slate-300 dark:border-slate-700 p-2 text-right font-medium text-slate-800 dark:text-slate-200">
                                Rp {{ number_format($comparisonResult['tooling']['cogm_sales'], 2, ',', '.') }}
                            </td>
                            {{-- Sales Adj --}}
                            <td class="col-sales-rev border border-slate-300 dark:border-slate-700 p-2 text-center text-slate-400">-</td>
                            <td class="col-sales-rev border border-slate-300 dark:border-slate-700 p-2 text-right font-medium text-slate-800 dark:text-slate-200">
                                @if(isset($comparisonResult['active_sales_rev']) && $comparisonResult['tooling']['cogm_sales_rev'] > 0)
                                    Rp {{ number_format($comparisonResult['tooling']['cogm_sales_rev'], 2, ',', '.') }}
                                @else
                                    <span class="text-slate-400 italic text-[11px]">—</span>
                                @endif
                            </td>
                            {{-- Customer --}}
                            <td class="col-customer border border-slate-300 dark:border-slate-700 p-2 text-center text-slate-400">-</td>
                            <td class="col-customer border border-slate-300 dark:border-slate-700 p-2 text-right font-medium text-slate-800 dark:text-slate-200">
                                @if(isset($comparisonResult['active_customer_quote']) && $comparisonResult['tooling']['cogm_customer'] > 0)
                                    Rp {{ number_format($comparisonResult['tooling']['cogm_customer'], 2, ',', '.') }}
                                @else
                                    <span class="text-slate-400 italic text-[11px]">—</span>
                                @endif
                            </td>
                            {{-- Supplier --}}
                            <td class="col-supplier border border-slate-300 dark:border-slate-700 p-2 text-center text-slate-400">-</td>
                            <td class="col-supplier border border-slate-300 dark:border-slate-700 p-2 text-right font-medium text-slate-800 dark:text-slate-200">
                                @if(isset($comparisonResult['active_supplier_quote']) && $comparisonResult['tooling']['cogm_supplier'] > 0)
                                    Rp {{ number_format($comparisonResult['tooling']['cogm_supplier'], 2, ',', '.') }}
                                @else
                                    <span class="text-slate-400 italic text-[11px]">—</span>
                                @endif
                            </td>
                        </tr>

                        {{-- 2. Others: O/H + Profit --}}
                        <tr>
                            <td class="border border-slate-300 dark:border-slate-700 p-2 text-center font-bold text-xs text-slate-600 dark:text-slate-400 bg-slate-50/50 dark:bg-slate-900/30 align-middle">
                                Others
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2 font-medium text-slate-700 dark:text-slate-300">
                                Overhead & Profit (O/H)
                            </td>
                            {{-- Eng --}}
                            <td class="col-eng border border-slate-300 dark:border-slate-700 p-2 text-center font-bold text-slate-700 dark:text-slate-300 bg-slate-50/30 dark:bg-slate-900/20">
                                {{ number_format($comparisonResult['tooling']['oh_profit_eng_pct'], 2) }}%
                            </td>
                            <td class="col-eng border border-slate-300 dark:border-slate-700 p-2 text-right font-medium text-slate-800 dark:text-slate-200">
                                Rp {{ number_format($comparisonResult['tooling']['oh_profit_eng_val'], 2, ',', '.') }}
                            </td>
                            {{-- Sales Orig --}}
                            <td class="col-sales-orig border border-slate-300 dark:border-slate-700 p-2 text-center font-bold text-slate-700 dark:text-slate-300 bg-slate-50/30 dark:bg-slate-900/20">
                                {{ number_format($comparisonResult['tooling']['oh_profit_sales_pct'], 2) }}%
                            </td>
                            <td class="col-sales-orig border border-slate-300 dark:border-slate-700 p-2 text-right font-semibold text-slate-800 dark:text-slate-200">
                                Rp {{ number_format($comparisonResult['tooling']['oh_profit_sales_val'], 2, ',', '.') }}
                            </td>
                            {{-- Sales Adj --}}
                            <td class="col-sales-rev border border-slate-300 dark:border-slate-700 p-2 text-center font-bold text-slate-700 dark:text-slate-300 bg-slate-50/30 dark:bg-slate-900/20">
                                @if(isset($comparisonResult['active_sales_rev']))
                                    {{ number_format($comparisonResult['tooling']['oh_profit_sales_rev_pct'], 2) }}%
                                @else
                                    -
                                @endif
                            </td>
                            <td class="col-sales-rev border border-slate-300 dark:border-slate-700 p-2 text-right font-semibold text-slate-800 dark:text-slate-200">
                                @if(isset($comparisonResult['active_sales_rev']) && $comparisonResult['tooling']['cogm_sales_rev'] > 0)
                                    Rp {{ number_format($comparisonResult['tooling']['oh_profit_sales_rev_val'], 2, ',', '.') }}
                                @else
                                    <span class="text-slate-400 italic text-[11px]">—</span>
                                @endif
                            </td>
                            {{-- Customer --}}
                            <td class="col-customer border border-slate-300 dark:border-slate-700 p-2 text-center font-bold text-slate-700 dark:text-slate-300 bg-slate-50/30 dark:bg-slate-900/20">
                                @if(isset($comparisonResult['active_customer_quote']))
                                    {{ number_format($comparisonResult['tooling']['oh_profit_customer_pct'], 2) }}%
                                @else
                                    -
                                @endif
                            </td>
                            <td class="col-customer border border-slate-300 dark:border-slate-700 p-2 text-right font-semibold text-slate-800 dark:text-slate-200">
                                @if(isset($comparisonResult['active_customer_quote']) && $comparisonResult['tooling']['cogm_customer'] > 0)
                                    Rp {{ number_format($comparisonResult['tooling']['oh_profit_customer_val'], 2, ',', '.') }}
                                @else
                                    <span class="text-slate-400 italic text-[11px]">—</span>
                                @endif
                            </td>
                            {{-- Supplier --}}
                            <td class="col-supplier border border-slate-300 dark:border-slate-700 p-2 text-center text-slate-400">0.00%</td>
                            <td class="col-supplier border border-slate-300 dark:border-slate-700 p-2 text-right font-medium text-slate-800 dark:text-slate-200">
                                @if(isset($comparisonResult['active_supplier_quote']) && $comparisonResult['tooling']['cogm_supplier'] > 0)
                                    Rp 0,00
                                @else
                                    <span class="text-slate-400 italic text-[11px]">—</span>
                                @endif
                            </td>
                        </tr>

                        {{-- 3. COGS Total --}}
                        <tr class="bg-slate-50 dark:bg-slate-900/80 font-black text-xs">
                            <td class="border border-slate-300 dark:border-slate-700 p-2 text-center text-slate-800 dark:text-white uppercase tracking-wider">
                                COGS
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2 text-slate-800 dark:text-white font-black">
                                Total Tooling COGS (COGM + O/H Profit)
                            </td>
                            {{-- Eng --}}
                            <td class="col-eng border border-slate-300 dark:border-slate-700 p-2 text-center text-slate-400">-</td>
                            <td class="col-eng border border-slate-300 dark:border-slate-700 p-2 text-right text-blue-700 dark:text-blue-300 font-black">
                                Rp {{ number_format($comparisonResult['tooling']['cogs_eng'], 2, ',', '.') }}
                            </td>
                            {{-- Sales Orig --}}
                            <td class="col-sales-orig border border-slate-300 dark:border-slate-700 p-2 text-center text-slate-400">-</td>
                            <td class="col-sales-orig border border-slate-300 dark:border-slate-700 p-2 text-right text-indigo-700 dark:text-indigo-300 font-black">
                                Rp {{ number_format($comparisonResult['tooling']['cogs_sales'], 2, ',', '.') }}
                            </td>
                            {{-- Sales Adj --}}
                            <td class="col-sales-rev border border-slate-300 dark:border-slate-700 p-2 text-center text-slate-400">-</td>
                            <td class="col-sales-rev border border-slate-300 dark:border-slate-700 p-2 text-right text-violet-700 dark:text-violet-300 font-black">
                                @if(isset($comparisonResult['active_sales_rev']) && $comparisonResult['tooling']['cogs_sales_rev'] > 0)
                                    Rp {{ number_format($comparisonResult['tooling']['cogs_sales_rev'], 2, ',', '.') }}
                                @else
                                    <span class="text-slate-400 italic text-[11px]">—</span>
                                @endif
                            </td>
                            {{-- Customer --}}
                            <td class="col-customer border border-slate-300 dark:border-slate-700 p-2 text-center text-slate-400">-</td>
                            <td class="col-customer border border-slate-300 dark:border-slate-700 p-2 text-right text-amber-700 dark:text-amber-300 font-black">
                                @if(isset($comparisonResult['active_customer_quote']) && $comparisonResult['tooling']['cogs_customer'] > 0)
                                    Rp {{ number_format($comparisonResult['tooling']['cogs_customer'], 2, ',', '.') }}
                                @else
                                    <span class="text-slate-400 italic text-[11px]">—</span>
                                @endif
                            </td>
                            {{-- Supplier --}}
                            <td class="col-supplier border border-slate-300 dark:border-slate-700 p-2 text-center text-slate-400">-</td>
                            <td class="col-supplier border border-slate-300 dark:border-slate-700 p-2 text-right text-purple-700 dark:text-purple-300 font-black">
                                @if(isset($comparisonResult['active_supplier_quote']) && $comparisonResult['tooling']['cogs_supplier'] > 0)
                                    Rp {{ number_format($comparisonResult['tooling']['cogs_supplier'], 2, ',', '.') }}
                                @else
                                    <span class="text-slate-400 italic text-[11px]">—</span>
                                @endif
                            </td>
                        </tr>

                        {{-- 4. Margin vs Engineering --}}
                        <tr class="bg-slate-50/70 dark:bg-slate-900/50 font-bold">
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-center text-slate-700 dark:text-slate-300 uppercase">
                                Margin
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 font-bold text-slate-800 dark:text-slate-200">
                                Margin vs Engineering
                            </td>
                            {{-- Eng --}}
                            <td colspan="2" class="col-eng border border-slate-300 dark:border-slate-700 p-2 text-center text-slate-400 font-medium italic">
                                Baseline
                            </td>
                            {{-- Sales Orig Margin --}}
                            <td colspan="2" class="col-sales-orig border border-slate-300 dark:border-slate-700 p-2 text-right">
                                @if($comparisonResult['tooling']['margin_idr'] >= 0)
                                    <span class="text-emerald-600 dark:text-emerald-400 font-black">+Rp {{ number_format($comparisonResult['tooling']['margin_idr'], 0, ',', '.') }}</span>
                                    <span class="ml-1 px-1.5 py-0.5 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 rounded-xs text-[10px] font-black">+{{ number_format($comparisonResult['tooling']['margin_pct'], 2) }}%</span>
                                @else
                                    <span class="text-rose-600 dark:text-rose-400 font-black">Rp {{ number_format($comparisonResult['tooling']['margin_idr'], 0, ',', '.') }}</span>
                                    <span class="ml-1 px-1.5 py-0.5 bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 rounded-xs text-[10px] font-black">{{ number_format($comparisonResult['tooling']['margin_pct'], 2) }}%</span>
                                @endif
                            </td>
                            {{-- Sales Adj Margin --}}
                            <td colspan="2" class="col-sales-rev border border-slate-300 dark:border-slate-700 p-2 text-right">
                                @if(isset($comparisonResult['active_sales_rev']) && $comparisonResult['tooling']['cogs_sales_rev'] > 0)
                                    @php
                                        $tRevMarginIdr = $comparisonResult['tooling']['cogs_sales_rev'] - $comparisonResult['tooling']['cogs_eng'];
                                        $tRevMarginPct = $comparisonResult['tooling']['cogs_sales_rev'] > 0 ? ($tRevMarginIdr / $comparisonResult['tooling']['cogs_sales_rev']) * 100 : 0;
                                    @endphp
                                    @if($tRevMarginIdr >= 0)
                                        <span class="text-emerald-600 dark:text-emerald-400 font-black">+Rp {{ number_format($tRevMarginIdr, 0, ',', '.') }}</span>
                                        <span class="ml-1 px-1.5 py-0.5 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 rounded-xs text-[10px] font-black">+{{ number_format($tRevMarginPct, 2) }}%</span>
                                    @else
                                        <span class="text-rose-600 dark:text-rose-400 font-black">Rp {{ number_format($tRevMarginIdr, 0, ',', '.') }}</span>
                                        <span class="ml-1 px-1.5 py-0.5 bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 rounded-xs text-[10px] font-black">{{ number_format($tRevMarginPct, 2) }}%</span>
                                    @endif
                                @else
                                    <span class="text-slate-400 italic text-[11px]">—</span>
                                @endif
                            </td>
                            {{-- Customer Margin --}}
                            <td colspan="2" class="col-customer border border-slate-300 dark:border-slate-700 p-2 text-right">
                                @if(isset($comparisonResult['active_customer_quote']) && $comparisonResult['tooling']['cogs_customer'] > 0)
                                    @php
                                        $tCustMarginIdr = $comparisonResult['tooling']['cogs_customer'] - $comparisonResult['tooling']['cogs_eng'];
                                        $tCustMarginPct = $comparisonResult['tooling']['cogs_customer'] > 0 ? ($tCustMarginIdr / $comparisonResult['tooling']['cogs_customer']) * 100 : 0;
                                    @endphp
                                    @if($tCustMarginIdr >= 0)
                                        <span class="text-emerald-600 dark:text-emerald-400 font-black">+Rp {{ number_format($tCustMarginIdr, 0, ',', '.') }}</span>
                                        <span class="ml-1 px-1.5 py-0.5 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 rounded-xs text-[10px] font-black">+{{ number_format($tCustMarginPct, 2) }}%</span>
                                    @else
                                        <span class="text-rose-600 dark:text-rose-400 font-black">Rp {{ number_format($tCustMarginIdr, 0, ',', '.') }}</span>
                                        <span class="ml-1 px-1.5 py-0.5 bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 rounded-xs text-[10px] font-black">{{ number_format($tCustMarginPct, 2) }}%</span>
                                    @endif
                                @else
                                    <span class="text-slate-400 italic text-[11px]">—</span>
                                @endif
                            </td>
                            {{-- Supplier Cost Gap --}}
                            <td colspan="2" class="col-supplier border border-slate-300 dark:border-slate-700 p-2 text-right">
                                @if(isset($comparisonResult['active_supplier_quote']) && $comparisonResult['tooling']['cogs_supplier'] > 0)
                                    @php
                                        $tSuppGapIdr = $comparisonResult['tooling']['cogs_supplier'] - $comparisonResult['tooling']['cogs_eng'];
                                        $tSuppGapPct = $comparisonResult['tooling']['cogs_eng'] > 0 ? ($tSuppGapIdr / $comparisonResult['tooling']['cogs_eng']) * 100 : 0;
                                    @endphp
                                    @if($tSuppGapIdr <= 0)
                                        <span class="text-emerald-600 dark:text-emerald-400 font-black">{{ number_format($tSuppGapIdr, 0, ',', '.') }}</span>
                                        <span class="ml-1 px-1.5 py-0.5 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 rounded-xs text-[10px] font-black">{{ number_format($tSuppGapPct, 2) }}%</span>
                                    @else
                                        <span class="text-rose-600 dark:text-rose-400 font-black">+Rp {{ number_format($tSuppGapIdr, 0, ',', '.') }}</span>
                                        <span class="ml-1 px-1.5 py-0.5 bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 rounded-xs text-[10px] font-black">+{{ number_format($tSuppGapPct, 2) }}%</span>
                                    @endif
                                @else
                                    <span class="text-slate-400 italic text-[11px]">—</span>
                                @endif
                            </td>
                        </tr>

                        {{-- 5. Standard Margin Status --}}
                        <tr class="bg-slate-50/70 dark:bg-slate-900/50 font-bold">
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-center text-slate-700 dark:text-slate-300 uppercase">
                                Std Margin
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-slate-800 dark:text-slate-200">
                                Target Policy: <strong>Min. {{ number_format($comparisonResult['tooling']['target_std_margin_pct'], 2) }}%</strong>
                            </td>
                            <td colspan="2" class="col-eng border border-slate-300 dark:border-slate-700 p-2 text-center text-slate-400 italic text-[10px]">
                                —
                            </td>
                            <td colspan="2" class="col-sales-orig border border-slate-300 dark:border-slate-700 p-2 text-center">
                                <span class="px-2 py-0.5 text-[10px] font-black rounded-xs {{ $comparisonResult['tooling']['status_badge'] }}">
                                    {{ $comparisonResult['tooling']['status'] }}
                                </span>
                            </td>
                            <td colspan="2" class="col-sales-rev border border-slate-300 dark:border-slate-700 p-2 text-center">
                                @if(isset($comparisonResult['active_sales_rev']) && $comparisonResult['tooling']['cogs_sales_rev'] > 0)
                                    @php
                                        $tRevMarginPct = (($comparisonResult['tooling']['cogs_sales_rev'] - $comparisonResult['tooling']['cogs_eng']) / $comparisonResult['tooling']['cogs_sales_rev']) * 100;
                                        $tRevPass = ($tRevMarginPct >= $comparisonResult['tooling']['target_std_margin_pct']);
                                    @endphp
                                    <span class="px-2 py-0.5 text-[10px] font-black rounded-xs {{ $tRevPass ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300' }}">
                                        {{ $tRevPass ? 'PASSED' : 'ALERT' }}
                                    </span>
                                @else
                                    <span class="text-slate-400 italic text-[10px]">—</span>
                                @endif
                            </td>
                            <td colspan="2" class="col-customer border border-slate-300 dark:border-slate-700 p-2 text-center">
                                @if(isset($comparisonResult['active_customer_quote']) && $comparisonResult['tooling']['cogs_customer'] > 0)
                                    @php
                                        $tCustMarginPct = (($comparisonResult['tooling']['cogs_customer'] - $comparisonResult['tooling']['cogs_eng']) / $comparisonResult['tooling']['cogs_customer']) * 100;
                                        $tCustPass = ($tCustMarginPct >= $comparisonResult['tooling']['target_std_margin_pct']);
                                    @endphp
                                    <span class="px-2 py-0.5 text-[10px] font-black rounded-xs {{ $tCustPass ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300' }}">
                                        {{ $tCustPass ? 'PASSED' : 'BELOW TARGET' }}
                                    </span>
                                @else
                                    <span class="text-slate-400 italic text-[10px]">—</span>
                                @endif
                            </td>
                            <td colspan="2" class="col-supplier border border-slate-300 dark:border-slate-700 p-2 text-center">
                                @if(isset($comparisonResult['active_supplier_quote']) && $comparisonResult['tooling']['cogs_supplier'] > 0)
                                    <span class="px-2 py-0.5 text-[10px] font-black rounded-xs bg-purple-100 text-purple-800 dark:bg-purple-950/40 dark:text-purple-300">
                                        RANK #{{ $comparisonResult['active_supplier_quote']->worth_rank ?? '1' }}
                                    </span>
                                @else
                                    <span class="text-slate-400 italic text-[10px]">—</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Detailed Tooling Breakdown Table --}}
            <div>
                <x-table id="tooling-breakdown-table" class="w-full text-xs text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900 text-[10px] font-extrabold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                            <th class="px-2 py-2.5 text-center w-8 border-r border-slate-200 dark:border-slate-700">No.</th>
                            <th class="px-2.5 py-2.5 border-r border-slate-200 dark:border-slate-700 min-w-[160px]">Part Number & Name</th>
                            <th class="px-2.5 py-2.5 border-r border-slate-200 dark:border-slate-700 min-w-[170px]">Tooling Process</th>
                            <th class="col-eng px-2 py-2.5 text-right border-r border-slate-200 dark:border-slate-700 bg-blue-50/20">Eng</th>
                            <th class="col-sales-orig px-2 py-2.5 text-right border-r border-slate-200 dark:border-slate-700 bg-indigo-50/20">Sales</th>
                            <th class="col-tool-sales-rev px-2 py-2.5 text-right border-r border-slate-200 dark:border-slate-700 bg-violet-50/20">Sales Adj.</th>
                            <th class="col-tool-customer px-2 py-2.5 text-right border-r border-slate-200 dark:border-slate-700 bg-amber-50/20">Customer</th>
                            <th class="col-tool-supplier px-2 py-2.5 text-right border-r border-slate-200 dark:border-slate-700 bg-purple-50/20">Supplier</th>
                            <th class="px-2 py-2.5 text-right border-r border-slate-200 dark:border-slate-700">Margin (IDR)</th>
                            <th class="px-1.5 py-2.5 text-right font-extrabold text-slate-800 dark:text-slate-100">Margin (%)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($comparisonResult['tooling']['items'] as $idx => $tItem)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="px-2 py-2 text-center font-bold text-slate-400 border-r border-slate-100 dark:border-slate-800">
                                    {{ $idx + 1 }}
                                </td>
                                {{-- 1. Part Number & Name (clean, with rank badge if present) --}}
                                <td class="px-2.5 py-2 border-r border-slate-100 dark:border-slate-800 font-semibold">
                                    <div class="font-extrabold text-slate-800 dark:text-white">{{ $tItem['part_no'] }}</div>
                                    <div class="text-[10px] text-slate-400 truncate max-w-[150px]">{{ $tItem['part_name'] }}</div>
                                    @if($tItem['tool_rank'] && $tItem['tool_rank'] !== '-')
                                        <div class="mt-0.5">
                                            <span class="px-1.5 py-0.2 text-[8px] font-bold rounded-xs bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                                                Rank {{ $tItem['tool_rank'] }}
                                            </span>
                                        </div>
                                    @endif
                                </td>
                                {{-- 2. Tooling Process (with Category badge and unclipped hover/click triggers) --}}
                                <td class="px-2.5 py-2 border-r border-slate-100 dark:border-slate-800">
                                    @php
                                        $tCat = strtoupper(trim($tItem['category'] ?? ''));
                                        $tProcName = trim($tItem['process_name'] ?? '');
                                        $tOp = trim((string)($tItem['op'] ?? ''));
                                        $hasOpVal = ($tOp !== '' && $tOp !== '-');

                                        if ($tCat === 'JIG' || str_contains(strtoupper($tProcName), 'JIG')) {
                                            $opPrefix = $hasOpVal ? "ST {$tOp}" : "ST";
                                            $badgeColor = "bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800";
                                        } elseif ($tCat === 'CF' || str_contains(strtoupper($tProcName), 'CF')) {
                                            $opPrefix = "-";
                                            $badgeColor = "bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800";
                                        } else {
                                            $opPrefix = $hasOpVal ? "OP {$tOp}" : "OP";
                                            $badgeColor = "bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800";
                                        }

                                        if ($opPrefix === '-') {
                                            $displayProc = $tProcName ?: '-';
                                        } else {
                                            $displayProc = $opPrefix . ($tProcName !== '' && $tProcName !== '-' ? ': ' . $tProcName : '');
                                        }
                                    @endphp
                                    <div class="tooling-hover-trigger cursor-pointer group/tool inline-block w-full"
                                         onmouseenter="window.showGlobalToolingPopover && window.showGlobalToolingPopover(this, {{ $idx }})"
                                         onmouseleave="window.hideGlobalToolingPopover && window.hideGlobalToolingPopover()"
                                         onclick="window.openToolingDetailModal && window.openToolingDetailModal({{ $idx }})">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="px-1.5 py-0.5 text-[9px] font-black rounded-xs {{ $badgeColor }}">
                                                {{ $tCat ?: 'DIE' }}
                                            </span>
                                            <span class="font-bold text-slate-800 dark:text-slate-200 group-hover/tool:text-purple-600 transition-colors">
                                                {{ $displayProc }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                {{-- Eng --}}
                                <td class="col-eng px-2 py-2 text-right border-r border-slate-100 dark:border-slate-800 font-medium text-slate-800 dark:text-slate-200 bg-blue-50/10">
                                    {{ number_format($tItem['total_cost_eng'], 0, ',', '.') }}
                                </td>
                                {{-- Sales Orig --}}
                                <td class="col-sales-orig px-2 py-2 text-right border-r border-slate-100 dark:border-slate-800 font-bold text-slate-900 dark:text-white bg-indigo-50/10">
                                    {{ number_format($tItem['total_cost_sales'], 0, ',', '.') }}
                                </td>
                                {{-- Sales Adj --}}
                                <td class="col-tool-sales-rev px-2 py-2 text-right border-r border-slate-100 dark:border-slate-800 font-bold text-violet-700 dark:text-violet-300 bg-violet-50/10">
                                    @if($tItem['total_cost_sales_rev'])
                                        {{ number_format($tItem['total_cost_sales_rev'], 0, ',', '.') }}
                                    @else
                                        <span class="text-slate-400 italic text-[10px]">—</span>
                                    @endif
                                </td>
                                {{-- Customer --}}
                                <td class="col-tool-customer px-2 py-2 text-right border-r border-slate-100 dark:border-slate-800 font-bold text-amber-700 dark:text-amber-300 bg-amber-50/10">
                                    @if($tItem['total_cost_customer'] !== null)
                                        {{ number_format($tItem['total_cost_customer'], 0, ',', '.') }}
                                    @else
                                        <span class="text-slate-400 italic text-[10px]">—</span>
                                    @endif
                                </td>
                                {{-- Supplier --}}
                                <td class="col-tool-supplier px-2 py-2 text-right border-r border-slate-100 dark:border-slate-800 font-bold text-purple-700 dark:text-purple-300 bg-purple-50/10">
                                    @if($tItem['total_cost_supplier'] !== null)
                                        {{ number_format($tItem['total_cost_supplier'], 0, ',', '.') }}
                                    @else
                                        <span class="text-slate-400 italic text-[10px]">—</span>
                                    @endif
                                </td>
                                {{-- Margin IDR --}}
                                <td class="px-2 py-2 text-right border-r border-slate-100 dark:border-slate-800 font-bold">
                                    @if($tItem['margin_idr'] > 0)
                                        <span class="text-emerald-600 dark:text-emerald-400">+{{ number_format($tItem['margin_idr'], 0, ',', '.') }}</span>
                                    @elseif($tItem['margin_idr'] < 0)
                                        <span class="text-rose-600 dark:text-rose-400">{{ number_format($tItem['margin_idr'], 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-slate-400">0</span>
                                    @endif
                                </td>
                                {{-- Margin % --}}
                                <td class="px-1.5 py-2 text-right font-black">
                                    @if($tItem['margin_pct'] > 0)
                                        <span class="px-1.5 py-0.5 text-[10px] font-black rounded-xs bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">+{{ number_format($tItem['margin_pct'], 1) }}%</span>
                                    @elseif($tItem['margin_pct'] < 0)
                                        <span class="px-1.5 py-0.5 text-[10px] font-black rounded-xs bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800">{{ number_format($tItem['margin_pct'], 1) }}%</span>
                                    @else
                                        <span class="px-1.5 py-0.5 text-[10px] font-bold rounded-xs bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">0.0%</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-slate-400 italic">
                                    No tooling processes or investment records found for this EBD project.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-table>
            </div>
        </div>
    </div>
</div>

<script>
    // Cached Comparison Values for KPI Dynamic Toggle
    const kpiData = {
        product: {
            cogsEng: 'Rp {{ number_format($comparisonResult['totals']['cogs_eng'], 0, ',', '.') }}',
            subEng: 'Direct Manufacturing Cost Baseline',
            cogsSales: 'Rp {{ number_format($comparisonResult['totals']['cogs_sales'], 0, ',', '.') }}',
            subSales: 'Commercial Quotation Baseline',
            marginPct: '{{ number_format($comparisonResult['margin_pct'], 2) }}%',
            marginIdr: '+Rp {{ number_format($comparisonResult['margin_idr'], 0, ',', '.') }}'
        },
        tooling: {
            cogsEng: 'Rp {{ number_format($comparisonResult['tooling']['cogs_eng'], 0, ',', '.') }}',
            subEng: 'Tooling Manufacturing Cost Baseline',
            cogsSales: 'Rp {{ number_format($comparisonResult['tooling']['cogs_sales'], 0, ',', '.') }}',
            subSales: 'Commercial Tooling Baseline (+O/H)',
            marginPct: '{{ number_format($comparisonResult['tooling']['margin_pct'], 2) }}%',
            marginIdr: '+Rp {{ number_format($comparisonResult['tooling']['margin_idr'], 0, ',', '.') }}'
        }
    };

    function switchTab(tab) {
        const btnProduct = document.getElementById('tab-btn-product-cost');
        const btnTooling = document.getElementById('tab-btn-tooling-cost');
        const badgeProduct = document.getElementById('tab-badge-product-cost');
        const badgeTooling = document.getElementById('tab-badge-tooling-cost');

        const contentProduct = document.getElementById('tab-content-product-cost');
        const contentTooling = document.getElementById('tab-content-tooling-cost');

        const valCogsEng = document.getElementById('kpi-val-cogs-eng');
        const subCogsEng = document.getElementById('kpi-sub-cogs-eng');
        const valCogsSales = document.getElementById('kpi-val-cogs-sales');
        const subCogsSales = document.getElementById('kpi-sub-cogs-sales');
        const valMarginPct = document.getElementById('kpi-val-margin-pct');
        const valMarginIdr = document.getElementById('kpi-val-margin-idr');

        if (tab === 'product-cost') {
            btnProduct.className = 'tab-btn inline-flex items-center gap-2 py-3 px-1 border-b-2 font-bold text-xs transition-all cursor-pointer border-blue-600 text-blue-600 dark:text-blue-400';
            badgeProduct.className = 'px-2 py-0.5 text-[10px] font-bold rounded-full bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 transition-colors';

            btnTooling.className = 'tab-btn inline-flex items-center gap-2 py-3 px-1 border-b-2 font-bold text-xs transition-all cursor-pointer border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:border-slate-300 dark:hover:border-slate-600';
            badgeTooling.className = 'px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 transition-colors';

            contentProduct.classList.remove('hidden');
            contentTooling.classList.add('hidden');
            $('#toggle-col-supplier-wrapper').addClass('hidden');

            if (valCogsEng) valCogsEng.textContent = kpiData.product.cogsEng;
            if (subCogsEng) subCogsEng.textContent = kpiData.product.subEng;
            if (valCogsSales) valCogsSales.textContent = kpiData.product.cogsSales;
            if (subCogsSales) subCogsSales.textContent = kpiData.product.subSales;
            if (valMarginPct) valMarginPct.textContent = kpiData.product.marginPct;
            if (valMarginIdr) valMarginIdr.textContent = kpiData.product.marginIdr;
        } else if (tab === 'tooling-cost') {
            btnTooling.className = 'tab-btn inline-flex items-center gap-2 py-3 px-1 border-b-2 font-bold text-xs transition-all cursor-pointer border-blue-600 text-blue-600 dark:text-blue-400';
            badgeTooling.className = 'px-2 py-0.5 text-[10px] font-bold rounded-full bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 transition-colors';

            btnProduct.className = 'tab-btn inline-flex items-center gap-2 py-3 px-1 border-b-2 font-bold text-xs transition-all cursor-pointer border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:border-slate-300 dark:hover:border-slate-600';
            badgeProduct.className = 'px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 transition-colors';

            contentProduct.classList.add('hidden');
            contentTooling.classList.remove('hidden');
            $('#toggle-col-supplier-wrapper').removeClass('hidden');

            if (valCogsEng) valCogsEng.textContent = kpiData.tooling.cogsEng;
            if (subCogsEng) subCogsEng.textContent = kpiData.tooling.subEng;
            if (valCogsSales) valCogsSales.textContent = kpiData.tooling.cogsSales;
            if (subCogsSales) subCogsSales.textContent = kpiData.tooling.subSales;
            if (valMarginPct) valMarginPct.textContent = kpiData.tooling.marginPct;
            if (valMarginIdr) valMarginIdr.textContent = kpiData.tooling.marginIdr;
        }

        setTimeout(function () {
            if ($.fn.DataTable.isDataTable('#tooling-breakdown-table')) {
                $('#tooling-breakdown-table').DataTable().columns.adjust().draw(false);
            }
            if ($.fn.DataTable.isDataTable('#part-breakdown-table')) {
                $('#part-breakdown-table').DataTable().columns.adjust().draw(false);
            }
        }, 50);
    }

    function switchRevisionParam(param, value) {
        const url = new URL(window.location.href);
        if (value) {
            url.searchParams.set(param, value);
        } else {
            url.searchParams.delete(param);
        }
        window.location.href = url.toString();
    }

    function switchEbdRevision(ebdId) {
        const url = new URL(window.location.href);
        url.pathname = url.pathname.replace(/\/product-cost-comparison\/\d+/, '/product-cost-comparison/' + ebdId);
        window.location.href = url.toString();
    }

    function syncGroupHeadersVisibility() {
        const isSalesRevVisible = $('#toggle-col-sales-rev').is(':checked');
        const isCustVisible = $('#toggle-col-customer').is(':checked');

        const thSalesRev = document.getElementById('th-group-sales-rev');
        if (thSalesRev) {
            thSalesRev.colSpan = 3;
            thSalesRev.style.setProperty('display', isSalesRevVisible ? '' : 'none', 'important');
        }

        const thCust = document.getElementById('th-group-customer');
        if (thCust) {
            thCust.colSpan = 3;
            thCust.style.setProperty('display', isCustVisible ? '' : 'none', 'important');
        }
    }

    // Dropdown controller for grouped Import button
    function toggleImportDropdown(e) {
        if (e) e.stopPropagation();
        const menu = document.getElementById('import-dropdown-menu');
        const chevron = document.getElementById('import-chevron-icon');
        if (menu) {
            const isHidden = menu.classList.toggle('hidden');
            if (chevron) {
                chevron.classList.toggle('rotate-180', !isHidden);
            }
        }
    }

    function openImportModal(type) {
        const menu = document.getElementById('import-dropdown-menu');
        const chevron = document.getElementById('import-chevron-icon');
        if (menu) menu.classList.add('hidden');
        if (chevron) chevron.classList.remove('rotate-180');

        if (type === 'sales-rev') {
            $('#import-sales-rev-modal').removeClass('hidden').addClass('flex');
        } else if (type === 'customer') {
            $('#import-customer-modal').removeClass('hidden').addClass('flex');
        } else if (type === 'supplier') {
            $('#import-supplier-modal').removeClass('hidden').addClass('flex');
        }
    }

    // Close import dropdown on outside click
    document.addEventListener('click', function (e) {
        const container = document.getElementById('import-dropdown-container');
        const menu = document.getElementById('import-dropdown-menu');
        const chevron = document.getElementById('import-chevron-icon');
        if (container && menu && !container.contains(e.target)) {
            menu.classList.add('hidden');
            if (chevron) chevron.classList.remove('rotate-180');
        }
    });

    // Dynamic Column Visibility Controller for Multi-Source Comparison
    function toggleSourceVisibility(source, isVisible) {
        try {
            localStorage.setItem('cost_comp_col_' + source, isVisible ? '1' : '0');
        } catch (e) {}

        const container = document.getElementById('cost-comparison-container');

        if (source === 'sales-rev') {
            if (container) container.classList.toggle('hide-sales-rev', !isVisible);

            if ($.fn.DataTable.isDataTable('#part-breakdown-table')) {
                const dt = $('#part-breakdown-table').DataTable();
                dt.columns([8, 9, 10]).visible(isVisible, false);
                dt.columns.adjust().draw(false);
            }
            if ($.fn.DataTable.isDataTable('#tooling-breakdown-table')) {
                const dtTool = $('#tooling-breakdown-table').DataTable();
                dtTool.column(5).visible(isVisible, false);
                dtTool.columns.adjust().draw(false);
            }
            syncGroupHeadersVisibility();
        } else if (source === 'customer') {
            if (container) container.classList.toggle('hide-customer', !isVisible);

            if ($.fn.DataTable.isDataTable('#part-breakdown-table')) {
                const dt = $('#part-breakdown-table').DataTable();
                dt.columns([11, 12, 13]).visible(isVisible, false);
                dt.columns.adjust().draw(false);
            }
            if ($.fn.DataTable.isDataTable('#tooling-breakdown-table')) {
                const dtTool = $('#tooling-breakdown-table').DataTable();
                dtTool.column(6).visible(isVisible, false);
                dtTool.columns.adjust().draw(false);
            }
            syncGroupHeadersVisibility();
        } else if (source === 'supplier') {
            if (container) container.classList.toggle('hide-supplier', !isVisible);

            if ($.fn.DataTable.isDataTable('#tooling-breakdown-table')) {
                const dtTool = $('#tooling-breakdown-table').DataTable();
                dtTool.column(7).visible(isVisible, false);
                dtTool.columns.adjust().draw(false);
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Initialize DataTables
        if (typeof window.defaultDataTable === 'function') {
            // Product Breakdown DataTables (Server-side)
            window.defaultDataTable('#part-breakdown-table', {
                serverSide: true,
                processing: true,
                pageLength: 25,
                ordering: true,
                order: [[0, 'asc']],
                drawCallback: function () {
                    syncGroupHeadersVisibility();
                },
                ajax: {
                    url: '{{ route("management.product-cost-comparison.items-data", $comparisonResult["ebd_header"]->id) }}',
                    data: function (d) {
                        const urlParams = new URLSearchParams(window.location.search);
                        d.sales_rev_id = urlParams.get('sales_rev_id') || '';
                        d.cust_quote_id = urlParams.get('cust_quote_id') || '';
                    }
                },
                columns: [
                    {
                        data: 'index',
                        orderable: false,
                        className: 'px-2 py-2.5 text-center font-bold text-slate-400 border-r border-slate-100 dark:border-slate-800'
                    },
                    {
                        data: 'part_no',
                        orderable: true,
                        className: 'px-2.5 py-2.5 border-r border-slate-100 dark:border-slate-800 font-semibold',
                        render: function (data, type, row) {
                            if (window.currentTableItemsMap) {
                                window.currentTableItemsMap[row.id] = row;
                            }
                            return `
                                <div class="part-hover-trigger cursor-pointer group/part"
                                     onmouseenter="window.showGlobalPartPopover && window.showGlobalPartPopover(this, ${row.id})"
                                     onmouseleave="window.hideGlobalPartPopover && window.hideGlobalPartPopover()"
                                     onclick="window.openPartDetailModal && window.openPartDetailModal(${row.id})">
                                    <div class="font-extrabold text-slate-800 dark:text-white group-hover/part:text-blue-600 transition-colors flex items-center gap-1.5">
                                        <span>${row.part_no}</span>
                                        ${row.is_assy ? '<span class="px-1 py-0.2 text-[8px] font-black rounded-xs bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-slate-600">ASSY</span>' : ''}
                                    </div>
                                    <div class="text-[10px] text-slate-400 truncate max-w-[170px]">${row.part_name}</div>
                                </div>
                            `;
                        }
                    },
                    // Engineering
                    {
                        data: 'eng_mat_cost',
                        orderable: true,
                        className: 'px-1.5 py-2.5 text-right border-r border-slate-100 dark:border-slate-800 font-medium text-slate-800 dark:text-slate-200 bg-blue-50/10',
                        render: function (data) {
                            return Number(data || 0).toLocaleString('id-ID');
                        }
                    },
                    {
                        data: 'eng_mfg_cost',
                        orderable: true,
                        className: 'px-1.5 py-2.5 text-right border-r border-slate-100 dark:border-slate-800 font-medium text-slate-800 dark:text-slate-200 bg-blue-50/10',
                        render: function (data) {
                            return Number(data || 0).toLocaleString('id-ID');
                        }
                    },
                    {
                        data: 'eng_cogs',
                        orderable: true,
                        className: 'px-1.5 py-2.5 text-right border-r border-slate-100 dark:border-slate-800 font-black text-slate-900 dark:text-white bg-blue-50/10',
                        render: function (data) {
                            return Number(data || 0).toLocaleString('id-ID');
                        }
                    },
                    // Sales (Original)
                    {
                        data: 'sales_mat_cost',
                        orderable: true,
                        className: 'px-1.5 py-2.5 text-right border-r border-slate-100 dark:border-slate-800 font-medium text-slate-800 dark:text-slate-200 bg-indigo-50/10',
                        render: function (data) {
                            return Number(data || 0).toLocaleString('id-ID');
                        }
                    },
                    {
                        data: 'sales_mfg_cost',
                        orderable: true,
                        className: 'px-1.5 py-2.5 text-right border-r border-slate-100 dark:border-slate-800 font-medium text-slate-800 dark:text-slate-200 bg-indigo-50/10',
                        render: function (data) {
                            return Number(data || 0).toLocaleString('id-ID');
                        }
                    },
                    {
                        data: 'sales_cogs',
                        orderable: true,
                        className: 'px-1.5 py-2.5 text-right border-r border-slate-100 dark:border-slate-800 font-black text-slate-900 dark:text-white bg-indigo-50/10',
                        render: function (data) {
                            return Number(data || 0).toLocaleString('id-ID');
                        }
                    },
                    // Sales Adjustment
                    {
                        data: 'sales_rev_mat_cost',
                        orderable: false,
                        className: 'px-1.5 py-2.5 text-right border-r border-slate-100 dark:border-slate-800 font-medium text-violet-700 dark:text-violet-300 bg-violet-50/10',
                        render: function (data) {
                            return (data !== null && data !== undefined) ? Number(data).toLocaleString('id-ID') : '—';
                        }
                    },
                    {
                        data: 'sales_rev_mfg_cost',
                        orderable: false,
                        className: 'px-1.5 py-2.5 text-right border-r border-slate-100 dark:border-slate-800 font-medium text-violet-700 dark:text-violet-300 bg-violet-50/10',
                        render: function (data) {
                            return (data !== null && data !== undefined) ? Number(data).toLocaleString('id-ID') : '—';
                        }
                    },
                    {
                        data: 'sales_rev_cogs',
                        orderable: false,
                        className: 'px-1.5 py-2.5 text-right border-r border-slate-100 dark:border-slate-800 font-black text-violet-700 dark:text-violet-300 bg-violet-50/10',
                        render: function (data) {
                            return (data !== null && data !== undefined) ? Number(data).toLocaleString('id-ID') : '—';
                        }
                    },
                    // Customer Quotation
                    {
                        data: 'cust_mat_cost',
                        orderable: false,
                        className: 'px-1.5 py-2.5 text-right border-r border-slate-100 dark:border-slate-800 font-medium text-amber-700 dark:text-amber-300 bg-amber-50/10',
                        render: function (data) {
                            return (data !== null && data !== undefined) ? Number(data).toLocaleString('id-ID') : '—';
                        }
                    },
                    {
                        data: 'cust_mfg_cost',
                        orderable: false,
                        className: 'px-1.5 py-2.5 text-right border-r border-slate-100 dark:border-slate-800 font-medium text-amber-700 dark:text-amber-300 bg-amber-50/10',
                        render: function (data) {
                            return (data !== null && data !== undefined) ? Number(data).toLocaleString('id-ID') : '—';
                        }
                    },
                    {
                        data: 'cust_cogs',
                        orderable: false,
                        className: 'px-1.5 py-2.5 text-right border-r border-slate-100 dark:border-slate-800 font-black text-amber-700 dark:text-amber-300 bg-amber-50/10',
                        render: function (data) {
                            return (data !== null && data !== undefined) ? Number(data).toLocaleString('id-ID') : '—';
                        }
                    },
                    // Profitability
                    {
                        data: 'margin_idr',
                        orderable: true,
                        className: 'px-1.5 py-2.5 text-right border-r border-slate-100 dark:border-slate-800 font-bold',
                        render: function (data) {
                            const val = Number(data || 0);
                            if (val > 0) {
                                return `<span class="text-emerald-600 dark:text-emerald-400">+${val.toLocaleString('id-ID')}</span>`;
                            } else if (val < 0) {
                                return `<span class="text-rose-600 dark:text-rose-400">${val.toLocaleString('id-ID')}</span>`;
                            }
                            return `<span class="text-slate-400">0</span>`;
                        }
                    },
                    {
                        data: 'margin_pct',
                        orderable: true,
                        className: 'px-1.5 py-2.5 text-right font-black',
                        render: function (data) {
                            const val = Number(data || 0);
                            if (val > 0) {
                                return `<span class="px-1.5 py-0.5 text-[9px] font-black rounded-xs bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">+${val.toFixed(1)}%</span>`;
                            } else if (val < 0) {
                                return `<span class="px-1.5 py-0.5 text-[9px] font-black rounded-xs bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800">${val.toFixed(1)}%</span>`;
                            }
                            return `<span class="px-1.5 py-0.5 text-[9px] font-bold rounded-xs bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">0.0%</span>`;
                        }
                    }
                ]
            });

            // Tooling Breakdown DataTables (Client-side)
            window.defaultDataTable('#tooling-breakdown-table', {
                pageLength: 25,
                ordering: true,
                order: [[0, 'asc']]
            });
        }

        // Restore column visibility preferences
        try {
            const showSalesRev = localStorage.getItem('cost_comp_col_sales-rev') !== '0';
            const showCustomer = localStorage.getItem('cost_comp_col_customer') !== '0';
            const showSupplier = localStorage.getItem('cost_comp_col_supplier') !== '0';

            $('#toggle-col-sales-rev').prop('checked', showSalesRev);
            $('#toggle-col-customer').prop('checked', showCustomer);
            $('#toggle-col-supplier').prop('checked', showSupplier);

            if (!showSalesRev) toggleSourceVisibility('sales-rev', false);
            if (!showCustomer) toggleSourceVisibility('customer', false);
            if (!showSupplier) toggleSourceVisibility('supplier', false);
        } catch (e) {}

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
            $btn.prop('disabled', true).text('Processing Import...');

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
                           .html(`<div class="font-bold">${res.message}</div>`);
                    
                    setTimeout(function() {
                        window.location.href = res.redirect_url || window.location.href;
                    }, 1000);
                },
                error: function(xhr) {
                    $btn.prop('disabled', false).html(originalHtml);
                    let errMsg = 'Import failed. Please check your Excel spreadsheet format.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errMsg = xhr.responseJSON.message;
                    }
                    $result.removeClass('hidden')
                           .addClass('bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800')
                           .html(`<div class="font-bold">${errMsg}</div>`);
                }
            });
        });
    });

    function openCostComparisonExportModal() {
        $('#exportCostComparisonModal').removeClass('hidden').addClass('flex');
    }
</script>

{{-- ===== 1. EXPORT CUSTOMER QUOTATION MODAL ===== --}}
<div id="exportCostComparisonModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xs shadow-2xl w-full max-w-md mx-4 overflow-hidden animate-fade-in">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-700">
            <div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-white">
                    Export Customer Quotation
                </h3>
                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Generate customized quotation spreadsheet</p>
            </div>
            <button type="button" onclick="$('#exportCostComparisonModal').addClass('hidden').removeClass('flex')" class="w-7 h-7 flex items-center justify-center rounded-xs text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer font-bold">
                ✕
            </button>
        </div>

        <form id="exportCostComparisonForm" action="{{ route('management.product-cost-comparison.quotation', $comparisonResult['ebd_header']->id) }}" method="GET" target="_blank">
            <div class="px-5 py-4 space-y-4">
                {{-- Target Info Box --}}
                <div class="p-3 bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 rounded-xs space-y-1">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-500">Customer:</span>
                        <span class="font-bold text-slate-800 dark:text-slate-100">{{ $comparisonResult['customer']->name ?? '—' }} ({{ $comparisonResult['customer']->code ?? '—' }})</span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-500">Project Model:</span>
                        <span class="font-bold text-slate-800 dark:text-slate-100">{{ $comparisonResult['project_model']->name ?? '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-500">EBD Revision:</span>
                        <span class="font-bold font-mono text-slate-800 dark:text-slate-100">Rev {{ $comparisonResult['ebd_header']->revision ?? '0' }}</span>
                    </div>
                </div>

                {{-- Template Selection --}}
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">
                            Output Template <span class="text-rose-500">*</span>
                        </label>
                        @if($defaultTemplateId)
                            <span class="text-[9px] font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 px-1.5 py-0.5 rounded-xs border border-emerald-200 dark:border-emerald-800">
                                Auto-Matched Customer
                            </span>
                        @endif
                    </div>
                    <select name="template_id" id="export_template_id" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-xs font-medium text-slate-800 dark:text-slate-100 rounded-xs focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        <option value="">-- Standard System Template (Default Format) --</option>
                        @foreach($exportTemplates ?? [] as $tpl)
                            @php
                                $isCustomerMatch = ($comparisonResult['customer']->id && $tpl->customer_id == $comparisonResult['customer']->id);
                                $isSelected = ($tpl->id == ($defaultTemplateId ?? null));
                            @endphp
                            <option value="{{ $tpl->id }}" {{ $isSelected ? 'selected' : '' }}>
                                {{ $tpl->template_name }} (Rev {{ $tpl->revision ?? '0' }})
                                @if($tpl->customer)
                                    [{{ $tpl->customer->code ?? $tpl->customer->name }}]
                                @else
                                    [Universal]
                                @endif
                                @if($isCustomerMatch)
                                    ★ (Matched)
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-5 py-3.5 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-200 dark:border-slate-700">
                <button type="button" onclick="$('#exportCostComparisonModal').addClass('hidden').removeClass('flex')" class="px-3.5 h-8 text-xs font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xs transition-colors cursor-pointer">
                    Cancel
                </button>
                <button type="submit" onclick="setTimeout(function(){ $('#exportCostComparisonModal').addClass('hidden').removeClass('flex'); }, 500)" class="inline-flex items-center justify-center px-4 h-8 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xs text-xs font-semibold shadow-xs active:scale-98 transition-all cursor-pointer">
                    Generate & Download Excel
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ===== 2. IMPORT SALES ADJUSTMENT MODAL ===== --}}
<div id="import-sales-rev-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xs shadow-2xl w-full max-w-md mx-4 overflow-hidden animate-fade-in">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-700">
            <div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-white">
                    Import Sales Adjustment Quotation
                </h3>
                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Upload modified quotation spreadsheet from Sales</p>
            </div>
            <button type="button" onclick="$('#import-sales-rev-modal').addClass('hidden').removeClass('flex')" class="w-7 h-7 flex items-center justify-center rounded-xs text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer font-bold">
                ✕
            </button>
        </div>

        <form class="form-import-quotation" action="{{ route('management.product-cost-comparison.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="ebd_header_id" value="{{ $comparisonResult['ebd_header']->id }}">
            <input type="hidden" name="source_type" value="sales">
            <input type="hidden" name="customer_id" value="{{ $comparisonResult['customer']->id ?? '' }}">

            <div class="px-5 py-4 space-y-4">
                {{-- Project Info Box --}}
                <div class="p-3 bg-violet-50/70 dark:bg-violet-950/40 border border-violet-200 dark:border-violet-800/60 rounded-xs flex items-center justify-between">
                    <div>
                        <span class="text-[10px] text-violet-700 dark:text-violet-300 font-semibold uppercase tracking-wider block">Source: Sales Adjustment</span>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-100">{{ $comparisonResult['project_model']->name ?? 'Model' }} — {{ $comparisonResult['customer']->code ?? 'Customer' }}</span>
                    </div>
                    <span class="text-[9px] font-bold bg-violet-200 dark:bg-violet-900/60 text-violet-800 dark:text-violet-200 px-2 py-0.5 rounded-xs">SALES ADJ</span>
                </div>

                {{-- Mapping Template --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">
                        Mapping Template (Optional)
                    </label>
                    <select name="template_id" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-xs font-medium text-slate-800 dark:text-slate-100 rounded-xs focus:ring-2 focus:ring-violet-500">
                        <option value="">-- Standard Quotation Template (Default Format) --</option>
                        @foreach($importTemplates ?? [] as $tpl)
                            <option value="{{ $tpl->id }}">
                                {{ $tpl->template_name }} (Rev {{ $tpl->revision ?? '0' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Import Mode --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">
                        Import Mode <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <label class="flex items-center gap-2 p-2.5 border border-violet-200 dark:border-violet-800 rounded-xs bg-violet-50/40 dark:bg-violet-950/40 cursor-pointer hover:border-violet-400">
                            <input type="radio" name="import_mode" value="new_revision" checked class="text-violet-600 focus:ring-violet-500">
                            <div>
                                <span class="block font-bold text-slate-800 dark:text-slate-100 text-[11px]">New Revision</span>
                                <span class="block text-[9px] text-slate-500">Append as next revision</span>
                            </div>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 border border-slate-200 dark:border-slate-700 rounded-xs bg-white dark:bg-slate-900 cursor-pointer hover:border-violet-400">
                            <input type="radio" name="import_mode" value="overwrite" class="text-violet-600 focus:ring-violet-500">
                            <div>
                                <span class="block font-bold text-slate-800 dark:text-slate-100 text-[11px]">Overwrite</span>
                                <span class="block text-[9px] text-slate-500">Update current revision</span>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- File Upload --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">
                        Quotation Spreadsheet File (.xlsx) <span class="text-rose-500">*</span>
                    </label>
                    <div class="dropzone-area relative flex flex-col items-center justify-center p-6 border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-xs bg-slate-50/50 dark:bg-slate-900/50 hover:bg-slate-100/70 dark:hover:bg-slate-800/60 transition-all cursor-pointer group text-center">
                        <input type="file" name="quotation_file" accept=".xlsx,.xls,.csv" class="input-quotation-file absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required>
                        
                        <div class="dropzone-prompt flex flex-col items-center justify-center space-y-1 pointer-events-none">
                            <div class="text-xs text-slate-600 dark:text-slate-300 font-semibold">
                                Click to select file or drag & drop here
                            </div>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500">Supported formats: .xlsx, .xls, .csv</p>
                        </div>

                        <div class="dropzone-file-info hidden flex items-center gap-3 p-2 pl-3 pr-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xs shadow-xs relative z-20 max-w-full">
                            <div class="min-w-0 text-left pr-2">
                                <p class="dropzone-file-name text-xs font-semibold text-slate-700 dark:text-slate-200 truncate max-w-[200px]"></p>
                                <p class="dropzone-file-size text-[10px] text-slate-400 dark:text-slate-500"></p>
                            </div>
                            <button type="button" class="btn-remove-file px-2 py-0.5 text-xs text-slate-400 hover:text-rose-600 cursor-pointer ml-auto flex-shrink-0 z-30" title="Remove File">
                                ✕
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Result Alert --}}
                <div class="importResult hidden text-xs font-medium p-3.5 rounded-xs border"></div>
            </div>

            <div class="flex items-center justify-end gap-2 px-5 py-3.5 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-200 dark:border-slate-700">
                <button type="button" onclick="$('#import-sales-rev-modal').addClass('hidden').removeClass('flex')" class="px-3.5 h-8 text-xs font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xs transition-colors cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="btn-submit-import inline-flex items-center justify-center px-4 h-8 bg-violet-600 hover:bg-violet-700 text-white rounded-xs text-xs font-semibold shadow-xs active:scale-98 transition-all cursor-pointer">
                    Import Sales Adjustment
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ===== 3. IMPORT CUSTOMER QUOTATION MODAL ===== --}}
<div id="import-customer-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xs shadow-2xl w-full max-w-md mx-4 overflow-hidden animate-fade-in">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-700">
            <div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-white">
                    Import Customer Quotation
                </h3>
                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Upload completed Excel quotation returned by customer</p>
            </div>
            <button type="button" onclick="$('#import-customer-modal').addClass('hidden').removeClass('flex')" class="w-7 h-7 flex items-center justify-center rounded-xs text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer font-bold">
                ✕
            </button>
        </div>

        <form class="form-import-quotation" action="{{ route('management.product-cost-comparison.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="ebd_header_id" value="{{ $comparisonResult['ebd_header']->id }}">
            <input type="hidden" name="source_type" value="customer">
            <input type="hidden" name="customer_id" value="{{ $comparisonResult['customer']->id ?? '' }}">

            <div class="px-5 py-4 space-y-4">
                {{-- Customer Info Box --}}
                <div class="p-3 bg-amber-50/70 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 rounded-xs flex items-center justify-between">
                    <div>
                        <span class="text-[10px] text-amber-700 dark:text-amber-300 font-semibold uppercase tracking-wider block">Target Customer</span>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-100">{{ $comparisonResult['customer']?->code ? "[{$comparisonResult['customer']->code}] " : "" }}{{ $comparisonResult['customer']?->name ?? 'Customer' }}</span>
                    </div>
                    <span class="text-[9px] font-bold bg-amber-200 dark:bg-amber-900/60 text-amber-800 dark:text-amber-200 px-2 py-0.5 rounded-xs">CUSTOMER</span>
                </div>

                {{-- Template Configuration --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">
                        Mapping Template <span class="text-rose-500">*</span>
                    </label>
                    <select name="template_id" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-xs font-medium text-slate-800 dark:text-slate-100 rounded-xs focus:ring-2 focus:ring-amber-500">
                        <option value="">-- Standard System Template (Default Format) --</option>
                        @foreach($importTemplates ?? [] as $tpl)
                            @php
                                $isCustomerMatch = ($comparisonResult['customer']->id && $tpl->customer_id == $comparisonResult['customer']->id);
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
                                    ★ (Matched)
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Import Mode --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">
                        Import Mode <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <label class="flex items-center gap-2 p-2.5 border border-amber-200 dark:border-amber-800 rounded-xs bg-amber-50/40 dark:bg-amber-950/40 cursor-pointer hover:border-amber-400">
                            <input type="radio" name="import_mode" value="new_revision" checked class="text-amber-600 focus:ring-amber-500">
                            <div>
                                <span class="block font-bold text-slate-800 dark:text-slate-100 text-[11px]">New Revision</span>
                                <span class="block text-[9px] text-slate-500">Append as next revision</span>
                            </div>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 border border-slate-200 dark:border-slate-700 rounded-xs bg-white dark:bg-slate-900 cursor-pointer hover:border-amber-400">
                            <input type="radio" name="import_mode" value="overwrite" class="text-amber-600 focus:ring-amber-500">
                            <div>
                                <span class="block font-bold text-slate-800 dark:text-slate-100 text-[11px]">Overwrite</span>
                                <span class="block text-[9px] text-slate-500">Update current revision</span>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- File Upload --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">
                        Customer Quotation File (.xlsx) <span class="text-rose-500">*</span>
                    </label>
                    <div class="dropzone-area relative flex flex-col items-center justify-center p-6 border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-xs bg-slate-50/50 dark:bg-slate-900/50 hover:bg-slate-100/70 dark:hover:bg-slate-800/60 transition-all cursor-pointer group text-center">
                        <input type="file" name="quotation_file" accept=".xlsx,.xls,.csv" class="input-quotation-file absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required>
                        
                        <div class="dropzone-prompt flex flex-col items-center justify-center space-y-1 pointer-events-none">
                            <div class="text-xs text-slate-600 dark:text-slate-300 font-semibold">
                                Click to select file or drag & drop here
                            </div>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500">Supported formats: .xlsx, .xls, .csv</p>
                        </div>

                        <div class="dropzone-file-info hidden flex items-center gap-3 p-2 pl-3 pr-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xs shadow-xs relative z-20 max-w-full">
                            <div class="min-w-0 text-left pr-2">
                                <p class="dropzone-file-name text-xs font-semibold text-slate-700 dark:text-slate-200 truncate max-w-[200px]"></p>
                                <p class="dropzone-file-size text-[10px] text-slate-400 dark:text-slate-500"></p>
                            </div>
                            <button type="button" class="btn-remove-file px-2 py-0.5 text-xs text-slate-400 hover:text-rose-600 cursor-pointer ml-auto flex-shrink-0 z-30" title="Remove File">
                                ✕
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Result Alert --}}
                <div class="importResult hidden text-xs font-medium p-3.5 rounded-xs border"></div>
            </div>

            <div class="flex items-center justify-end gap-2 px-5 py-3.5 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-200 dark:border-slate-700">
                <button type="button" onclick="$('#import-customer-modal').addClass('hidden').removeClass('flex')" class="px-3.5 h-8 text-xs font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xs transition-colors cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="btn-submit-import inline-flex items-center justify-center px-4 h-8 bg-amber-600 hover:bg-amber-700 text-white rounded-xs text-xs font-semibold shadow-xs active:scale-98 transition-all cursor-pointer">
                    Import Customer Quotation
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ===== 4. IMPORT SUPPLIER QUOTATION MODAL ===== --}}
<div id="import-supplier-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xs shadow-2xl w-full max-w-md mx-4 overflow-hidden animate-fade-in">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-700">
            <div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-white">
                    Import Supplier Quotation
                </h3>
                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Upload tooling quotation spreadsheet from supplier</p>
            </div>
            <button type="button" onclick="$('#import-supplier-modal').addClass('hidden').removeClass('flex')" class="w-7 h-7 flex items-center justify-center rounded-xs text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer font-bold">
                ✕
            </button>
        </div>

        <form class="form-import-quotation" action="{{ route('management.product-cost-comparison.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="ebd_header_id" value="{{ $comparisonResult['ebd_header']->id }}">
            <input type="hidden" name="source_type" value="supplier">

            <div class="px-5 py-4 space-y-4">
                {{-- Supplier Select --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">
                        Select Supplier <span class="text-rose-500">*</span>
                    </label>
                    <select name="supplier_id" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-xs font-semibold text-slate-800 dark:text-slate-100 rounded-xs focus:ring-2 focus:ring-purple-500">
                        <option value="">-- Choose Quoting Supplier --</option>
                        @foreach($suppliers ?? [] as $supp)
                            <option value="{{ $supp->id }}">
                                {{ $supp->name }} ({{ $supp->code ?? 'SUPP' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Template Configuration --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">
                        Mapping Template (Optional)
                    </label>
                    <select name="template_id" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-xs font-medium text-slate-800 dark:text-slate-100 rounded-xs focus:ring-2 focus:ring-purple-500">
                        <option value="">-- Standard Tooling Quotation (Default Format) --</option>
                        @foreach($importTemplates ?? [] as $tpl)
                            <option value="{{ $tpl->id }}">
                                {{ $tpl->template_name }} (Rev {{ $tpl->revision ?? '0' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Import Mode --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">
                        Import Mode <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <label class="flex items-center gap-2 p-2.5 border border-purple-200 dark:border-purple-800 rounded-xs bg-purple-50/40 dark:bg-purple-950/40 cursor-pointer hover:border-purple-400">
                            <input type="radio" name="import_mode" value="new_revision" checked class="text-purple-600 focus:ring-purple-500">
                            <div>
                                <span class="block font-bold text-slate-800 dark:text-slate-100 text-[11px]">New Revision</span>
                                <span class="block text-[9px] text-slate-500">Append as next revision</span>
                            </div>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 border border-slate-200 dark:border-slate-700 rounded-xs bg-white dark:bg-slate-900 cursor-pointer hover:border-purple-400">
                            <input type="radio" name="import_mode" value="overwrite" class="text-purple-600 focus:ring-purple-500">
                            <div>
                                <span class="block font-bold text-slate-800 dark:text-slate-100 text-[11px]">Overwrite</span>
                                <span class="block text-[9px] text-slate-500">Update current revision</span>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- File Upload --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">
                        Supplier Quotation File (.xlsx) <span class="text-rose-500">*</span>
                    </label>
                    <div class="dropzone-area relative flex flex-col items-center justify-center p-6 border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-xs bg-slate-50/50 dark:bg-slate-900/50 hover:bg-slate-100/70 dark:hover:bg-slate-800/60 transition-all cursor-pointer group text-center">
                        <input type="file" name="quotation_file" accept=".xlsx,.xls,.csv" class="input-quotation-file absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required>
                        
                        <div class="dropzone-prompt flex flex-col items-center justify-center space-y-1 pointer-events-none">
                            <div class="text-xs text-slate-600 dark:text-slate-300 font-semibold">
                                Click to select file or drag & drop here
                            </div>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500">Supported formats: .xlsx, .xls, .csv</p>
                        </div>

                        <div class="dropzone-file-info hidden flex items-center gap-3 p-2 pl-3 pr-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xs shadow-xs relative z-20 max-w-full">
                            <div class="min-w-0 text-left pr-2">
                                <p class="dropzone-file-name text-xs font-semibold text-slate-700 dark:text-slate-200 truncate max-w-[200px]"></p>
                                <p class="dropzone-file-size text-[10px] text-slate-400 dark:text-slate-500"></p>
                            </div>
                            <button type="button" class="btn-remove-file px-2 py-0.5 text-xs text-slate-400 hover:text-rose-600 cursor-pointer ml-auto flex-shrink-0 z-30" title="Remove File">
                                ✕
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Result Alert --}}
                <div class="importResult hidden text-xs font-medium p-3.5 rounded-xs border"></div>
            </div>

            <div class="flex items-center justify-end gap-2 px-5 py-3.5 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-200 dark:border-slate-700">
                <button type="button" onclick="$('#import-supplier-modal').addClass('hidden').removeClass('flex')" class="px-3.5 h-8 text-xs font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xs transition-colors cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="btn-submit-import inline-flex items-center justify-center px-4 h-8 bg-purple-600 hover:bg-purple-700 text-white rounded-xs text-xs font-semibold shadow-xs active:scale-98 transition-all cursor-pointer">
                    Import Supplier Quotation
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Dedicated Part Detail Modal & Global Unclipped Floating Popover Component --}}
@include('management.cost-comparison.partials.part-detail-modal')

{{-- Dedicated Tooling Detail Modal & Global Unclipped Floating Popover Component --}}
<script>
    window.currentToolingItems = @json($comparisonResult['tooling']['items'] ?? []);
</script>
@include('management.cost-comparison.partials.tooling-detail-modal')

@endsection
