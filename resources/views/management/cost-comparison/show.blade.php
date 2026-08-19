@extends('layouts.app')

@section('title', 'Cost Comparison Matrix Detail · Promise Management')

@section('content')
<div class="flex-1 overflow-y-auto p-4 pt-17.5 space-y-5 transition-colors duration-200">

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- TOP RESPONSIVE HEADER & KPI SUMMARY GRID (40 : 60 RATIO)      --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-2.5 items-stretch">
        {{-- Card Header Halaman (40% -> lg:col-span-5) --}}
        <div class="lg:col-span-5 bg-white dark:bg-slate-800 rounded-sm border border-slate-300 dark:border-slate-700 overflow-hidden flex flex-col justify-between">
            {{-- Header Strip Judul --}}
            <div class="px-3 py-1.5 bg-slate-50 dark:bg-slate-900/80 border-b border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                Cost Estimation Comparison Matrix
            </div>

            {{-- Table Grid Structure --}}
            <div class="grid grid-cols-12 divide-x divide-slate-200 dark:divide-slate-700 flex-1">
                {{-- Cell 1: Button Back + Model & Customer --}}
                <div class="col-span-6 p-2.5 flex items-center gap-2.5 min-w-0 bg-white dark:bg-slate-800">
                    <a href="{{ route('management.product-cost-comparison.index') }}"
                       class="shrink-0 w-8 h-8 rounded-sm bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 border border-slate-200 dark:border-slate-600 flex items-center justify-center text-slate-700 dark:text-slate-200 transition-colors"
                       title="Kembali ke Daftar">
                        <i class="fa-solid fa-arrow-left text-xs"></i>
                    </a>
                    <div class="min-w-0 flex-1">
                        <h1 class="text-sm font-black tracking-tight text-slate-800 dark:text-white leading-tight truncate"
                            title="{{ $comparisonResult['project_model']->name ?? 'Model' }} — {{ $comparisonResult['customer']->code ?? $comparisonResult['customer']->name ?? 'Customer' }}">
                            {{ $comparisonResult['project_model']->name ?? 'Model' }} — {{ $comparisonResult['customer']->code ?? $comparisonResult['customer']->name ?? 'Customer' }}
                        </h1>
                    </div>
                </div>

                {{-- Cell 2: Rev & Date (Table Rows Style) --}}
                <div class="col-span-3 p-2 flex flex-col justify-center divide-y divide-slate-100 dark:divide-slate-700/60 bg-slate-50/50 dark:bg-slate-900/30 text-xs text-slate-600 dark:text-slate-300">
                    <div class="py-0.5 leading-tight">
                        Rev: <strong class="text-slate-800 dark:text-white font-bold">{{ $comparisonResult['ebd_header']->revision ?? '0' }}</strong>
                    </div>
                    <div class="py-0.5 leading-tight">
                        Date: <strong class="text-slate-800 dark:text-white font-bold">{{ $comparisonResult['ebd_header']->date ? $comparisonResult['ebd_header']->date->format('d/m/Y') : '-' }}</strong>
                    </div>
                </div>

                {{-- Cell 3: Parts & Toolings (Table Rows Style) --}}
                <div class="col-span-3 p-2 flex flex-col justify-center divide-y divide-slate-100 dark:divide-slate-700/60 bg-slate-50/50 dark:bg-slate-900/30 text-xs font-semibold">
                    <div class="py-0.5 flex items-center gap-1.5 text-slate-700 dark:text-slate-200 leading-tight">
                        <i class="fa-solid fa-cube text-slate-500 dark:text-slate-400 text-xs"></i>
                        <span class="font-bold">{{ count($comparisonResult['items']) }} Parts</span>
                    </div>
                    <div class="py-0.5 flex items-center gap-1.5 text-slate-700 dark:text-slate-200 leading-tight">
                        <i class="fa-solid fa-wrench text-slate-500 dark:text-slate-400 text-xs"></i>
                        <span class="font-bold">{{ $comparisonResult['tooling']['total_items_count'] ?? 0 }} Toolings</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- KPI Cards (60% -> lg:col-span-7) --}}
        <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-3 gap-2.5">
            {{-- KPI 1: Total COGS (Engineering) --}}
            <div class="p-3 bg-white dark:bg-slate-800 rounded-sm border border-slate-300 dark:border-slate-700 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">
                        Total COGS (Eng)
                    </span>
                    <div class="w-6.5 h-6.5 rounded-none bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold">
                        <i id="kpi-icon-cogs-eng" class="fa-solid fa-wrench text-xs"></i>
                    </div>
                </div>
                <div class="mt-1">
                    <div id="kpi-val-cogs-eng" class="text-base sm:text-lg font-black text-blue-700 dark:text-blue-300">
                        Rp {{ number_format($comparisonResult['totals']['cogs_eng'], 0, ',', '.') }}
                    </div>
                    <span id="kpi-sub-cogs-eng" class="text-[11px] text-slate-400 block truncate">
                        Direct Manufacturing HPP
                    </span>
                </div>
            </div>

            {{-- KPI 2: Total COGS (Sales) --}}
            <div class="p-3 bg-white dark:bg-slate-800 rounded-sm border border-slate-300 dark:border-slate-700 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                        Total COGS (Sales)
                    </span>
                    <div class="w-6.5 h-6.5 rounded-none bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                        <i class="fa-solid fa-file-invoice-dollar text-xs"></i>
                    </div>
                </div>
                <div class="mt-1">
                    <div id="kpi-val-cogs-sales" class="text-base sm:text-lg font-black text-indigo-700 dark:text-indigo-300">
                        Rp {{ number_format($comparisonResult['totals']['cogs_sales'], 0, ',', '.') }}
                    </div>
                    <span id="kpi-sub-cogs-sales" class="text-[11px] text-slate-400 block truncate">
                        Commercial Quotation Price
                    </span>
                </div>
            </div>

            {{-- KPI 3: Profit Margin --}}
            <div class="p-3 bg-white dark:bg-slate-800 rounded-sm border border-slate-300 dark:border-slate-700 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                        Profit Margin
                    </span>
                    <div class="w-6.5 h-6.5 rounded-none bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                        <i class="fa-solid fa-chart-line text-xs"></i>
                    </div>
                </div>
                <div class="mt-1">
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
    {{-- MAIN CONTENT CARD WITH INTEGRATED TABS HEADER                --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    <div class="bg-white dark:bg-slate-800 rounded-sm border border-slate-300 dark:border-slate-700 overflow-hidden">
        {{-- Card Header: Underline Navigation Tabs --}}
        <div class="border-b border-slate-200 dark:border-slate-700 px-4 bg-slate-50/80 dark:bg-slate-900/50">
            <nav class="flex space-x-6 -mb-px" aria-label="Tabs">
                {{-- Tab 1: Product Cost --}}
                <button type="button" onclick="switchTab('product-cost')" id="tab-btn-product-cost"
                        class="tab-btn inline-flex items-center gap-2 py-3 px-1 border-b-2 font-bold text-xs transition-all cursor-pointer border-blue-600 text-blue-600 dark:text-blue-400">
                    <i id="tab-icon-product-cost" class="fa-solid fa-cube text-sm text-blue-600 dark:text-blue-400"></i>
                    <span>Product Cost Comparison</span>
                    <span id="tab-badge-product-cost"
                          class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 transition-colors">
                        {{ count($comparisonResult['items']) }}
                    </span>
                </button>

                {{-- Tab 2: Tooling Cost --}}
                <button type="button" onclick="switchTab('tooling-cost')" id="tab-btn-tooling-cost"
                        class="tab-btn inline-flex items-center gap-2 py-3 px-1 border-b-2 font-bold text-xs transition-all cursor-pointer border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:border-slate-300 dark:hover:border-slate-600">
                    <i id="tab-icon-tooling-cost" class="fa-solid fa-wrench text-sm text-slate-400 dark:text-slate-500"></i>
                    <span>Tooling Cost Comparison</span>
                    <span id="tab-badge-tooling-cost"
                          class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 transition-colors">
                        {{ $comparisonResult['tooling']['total_items_count'] ?? 0 }}
                    </span>
                </button>
            </nav>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- TAB 1: PRODUCT COST COMPARISON CONTENT                        --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div id="tab-content-product-cost" class="p-4 space-y-5">
            {{-- Main Product Cost Matrix (Neutral Styling) --}}
            <div class="overflow-x-auto rounded-sm border border-slate-300 dark:border-slate-700">
                <table class="w-full text-xs text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100 dark:bg-slate-900 border-b border-slate-300 dark:border-slate-700">
                            <th class="w-28 border border-slate-300 dark:border-slate-700 p-2.5 text-center font-bold text-slate-500 dark:text-slate-400"></th>
                            <th class="text-slate-800 dark:text-slate-200 font-extrabold text-xs uppercase text-center p-2.5 border border-slate-300 dark:border-slate-700 tracking-wider">
                                Product Cost
                            </th>
                            <th colspan="2" class="text-slate-800 dark:text-slate-200 font-extrabold text-xs uppercase tracking-wider text-center p-2 border border-slate-300 dark:border-slate-700">
                                <i class="fa-solid fa-wrench mr-1 text-slate-400"></i> Engineering (Eng)
                            </th>
                            <th colspan="2" class="text-slate-800 dark:text-slate-200 font-extrabold text-xs uppercase tracking-wider text-center p-2 border border-slate-300 dark:border-slate-700">
                                <i class="fa-solid fa-file-invoice-dollar mr-1 text-slate-400"></i> Sales
                            </th>
                        </tr>
                        <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-300 font-bold text-[11px] uppercase border-b border-slate-300 dark:border-slate-700">
                            <th class="border border-slate-300 dark:border-slate-700 p-2.5 text-center w-28">Stage</th>
                            <th class="border border-slate-300 dark:border-slate-700 p-2.5">Criteria</th>
                            <th class="border border-slate-300 dark:border-slate-700 p-2 text-center w-20">
                                Rate (%)
                            </th>
                            <th class="border border-slate-300 dark:border-slate-700 p-2 text-right w-48">
                                Amount (IDR)
                            </th>
                            <th class="border border-slate-300 dark:border-slate-700 p-2 text-center w-20">
                                Rate (%)
                            </th>
                            <th class="border border-slate-300 dark:border-slate-700 p-2 text-right w-48">
                                Amount (IDR)
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        {{-- 1. COGM: Material --}}
                        <tr>
                            <td rowspan="2" class="border border-slate-300 dark:border-slate-700 p-3 text-center font-bold text-xs text-slate-600 dark:text-slate-400 bg-slate-50/50 dark:bg-slate-900/30 align-middle">
                                COGM
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 font-medium text-slate-700 dark:text-slate-300">
                                Material Cost
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-center text-slate-400">
                                -
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-right font-medium text-slate-800 dark:text-slate-200">
                                Rp {{ number_format($comparisonResult['totals']['material_eng'], 2, ',', '.') }}
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-center text-slate-400">
                                -
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-right font-medium text-slate-800 dark:text-slate-200">
                                Rp {{ number_format($comparisonResult['totals']['material_sales'], 2, ',', '.') }}
                            </td>
                        </tr>

                        {{-- 1. COGM: Manufacturing Process Cost --}}
                        <tr>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 font-medium text-slate-700 dark:text-slate-300">
                                Mfg Process Cost
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-center text-slate-400">
                                -
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-right font-medium text-slate-800 dark:text-slate-200">
                                Rp {{ number_format($comparisonResult['totals']['mfg_eng'], 2, ',', '.') }}
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-center text-slate-400">
                                -
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-right font-medium text-slate-800 dark:text-slate-200">
                                Rp {{ number_format($comparisonResult['totals']['mfg_sales'], 2, ',', '.') }}
                            </td>
                        </tr>

                        {{-- 2. Others: Admin Material --}}
                        <tr>
                            <td rowspan="3" class="border border-slate-300 dark:border-slate-700 p-3 text-center font-bold text-xs text-slate-600 dark:text-slate-400 bg-slate-50/50 dark:bg-slate-900/30 align-middle">
                                Others
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 font-medium text-slate-700 dark:text-slate-300">
                                Admin matrl.
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-center font-bold text-slate-700 dark:text-slate-300 bg-slate-50/30 dark:bg-slate-900/20">
                                {{ number_format($comparisonResult['policy_eng']->admin_matrl_pct ?? 0, 2) }}%
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-right font-medium text-slate-800 dark:text-slate-200">
                                Rp {{ number_format($comparisonResult['totals']['admin_matrl_eng'], 2, ',', '.') }}
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-center font-bold text-slate-700 dark:text-slate-300 bg-slate-50/30 dark:bg-slate-900/20">
                                {{ number_format($comparisonResult['policy_sales']->admin_matrl_pct ?? 0, 2) }}%
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-right font-medium text-slate-800 dark:text-slate-200">
                                Rp {{ number_format($comparisonResult['totals']['admin_matrl_sales'], 2, ',', '.') }}
                            </td>
                        </tr>

                        {{-- 2. Others: Admin Mfg --}}
                        <tr>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 font-medium text-slate-700 dark:text-slate-300">
                                Admin mfg.
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-center font-bold text-slate-700 dark:text-slate-300 bg-slate-50/30 dark:bg-slate-900/20">
                                {{ number_format($comparisonResult['policy_eng']->admin_mfg_pct ?? 0, 2) }}%
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-right font-medium text-slate-800 dark:text-slate-200">
                                Rp {{ number_format($comparisonResult['totals']['admin_mfg_eng'], 2, ',', '.') }}
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-center font-bold text-slate-700 dark:text-slate-300 bg-slate-50/30 dark:bg-slate-900/20">
                                {{ number_format($comparisonResult['policy_sales']->admin_mfg_pct ?? 0, 2) }}%
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-right font-medium text-slate-800 dark:text-slate-200">
                                Rp {{ number_format($comparisonResult['totals']['admin_mfg_sales'], 2, ',', '.') }}
                            </td>
                        </tr>

                        {{-- 2. Others: O/H + Profit --}}
                        <tr>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 font-medium text-slate-700 dark:text-slate-300">
                                O/H + Profit
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-center font-bold text-slate-700 dark:text-slate-300 bg-slate-50/30 dark:bg-slate-900/20">
                                {{ number_format($comparisonResult['policy_eng']->oh_profit_pct ?? 0, 2) }}%
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-right font-semibold text-slate-800 dark:text-slate-200">
                                Rp {{ number_format($comparisonResult['totals']['oh_profit_eng'], 2, ',', '.') }}
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-center font-bold text-slate-700 dark:text-slate-300 bg-slate-50/30 dark:bg-slate-900/20">
                                {{ number_format($comparisonResult['policy_sales']->oh_profit_pct ?? 0, 2) }}%
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-right font-semibold text-slate-800 dark:text-slate-200">
                                Rp {{ number_format($comparisonResult['totals']['oh_profit_sales'], 2, ',', '.') }}
                            </td>
                        </tr>

                        {{-- 3. COGS Total --}}
                        <tr class="bg-slate-50 dark:bg-slate-900/80 font-black text-sm">
                            <td class="border border-slate-300 dark:border-slate-700 p-3 text-center text-slate-800 dark:text-white uppercase tracking-wider">
                                COGS
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-3 text-slate-800 dark:text-white font-black">
                                Total ( COGM + Admin + O/H )
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-3 text-center text-slate-400">
                                -
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-3 text-right text-slate-900 dark:text-white font-black">
                                Rp {{ number_format($comparisonResult['totals']['cogs_eng'], 2, ',', '.') }}
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-3 text-center text-slate-400">
                                -
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-3 text-right text-slate-900 dark:text-white font-black">
                                Rp {{ number_format($comparisonResult['totals']['cogs_sales'], 2, ',', '.') }}
                            </td>
                        </tr>

                        {{-- 4. Margin --}}
                        <tr class="bg-slate-50/70 dark:bg-slate-900/50 font-bold">
                            <td class="border border-slate-300 dark:border-slate-700 p-3 text-center text-slate-700 dark:text-slate-300 uppercase">
                                Margin
                            </td>
                            <td colspan="5" class="border border-slate-300 dark:border-slate-700 p-3 text-slate-900 dark:text-white">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span><strong>= COGS SALES - COGS ENG</strong></span>
                                    <div class="text-right">
                                        @if($comparisonResult['margin_idr'] >= 0)
                                            <span class="text-sm text-emerald-600 dark:text-emerald-400 font-black">
                                                +Rp {{ number_format($comparisonResult['margin_idr'], 2, ',', '.') }}
                                            </span>
                                            <span class="ml-2 px-2 py-0.5 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 rounded-sm text-xs font-black">
                                                +{{ number_format($comparisonResult['margin_pct'], 2) }}%
                                            </span>
                                        @else
                                            <span class="text-sm text-rose-600 dark:text-rose-400 font-black">
                                                Rp {{ number_format($comparisonResult['margin_idr'], 2, ',', '.') }}
                                            </span>
                                            <span class="ml-2 px-2 py-0.5 bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 rounded-sm text-xs font-black">
                                                {{ number_format($comparisonResult['margin_pct'], 2) }}%
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>

                        {{-- 5. Std Margin --}}
                        <tr class="bg-slate-50/70 dark:bg-slate-900/50 font-bold">
                            <td class="border border-slate-300 dark:border-slate-700 p-3 text-center text-slate-700 dark:text-slate-300 uppercase">
                                Std Margin
                            </td>
                            <td colspan="5" class="border border-slate-300 dark:border-slate-700 p-3 text-slate-900 dark:text-white">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span><strong>= Min. {{ number_format($comparisonResult['target_margin_sales'], 2) }}%</strong> <span class="text-xs font-normal text-slate-500 dark:text-slate-400">(Sales Target Margin)</span></span>
                                    <span class="px-2.5 py-1 text-xs font-black rounded-sm {{ $comparisonResult['status_badge'] }}">
                                        {{ $comparisonResult['status_text'] }}
                                    </span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Detailed Per-Part Breakdown Table with x-table Component and DataTable --}}
            <div>
                <x-table id="part-breakdown-table" class="w-full text-xs text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900 text-[10px] font-extrabold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                            <th rowspan="2" class="px-3 py-3 w-10 text-center border-r border-slate-200 dark:border-slate-700 align-middle">No.</th>
                            <th rowspan="2" class="px-3 py-3 border-r border-slate-200 dark:border-slate-700 align-middle">Part No & Name</th>
                            <th rowspan="2" class="px-2 py-3 text-center border-r border-slate-200 dark:border-slate-700 align-middle">Rank</th>
                            <th rowspan="2" class="px-3 py-3 border-r border-slate-200 dark:border-slate-700 align-middle">Material Spec</th>
                            <th rowspan="2" class="px-2 py-3 text-center border-r border-slate-200 dark:border-slate-700 align-middle">Total Process</th>

                            {{-- Group Header: Engineering --}}
                            <th colspan="3" class="px-3 py-2 text-center border-r border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900 text-slate-800 dark:text-slate-200 font-extrabold text-[11px]">
                                <i class="fa-solid fa-wrench mr-1 text-slate-400"></i> ENGINEERING (HPP)
                            </th>

                            {{-- Group Header: Sales --}}
                            <th colspan="3" class="px-3 py-2 text-center border-r border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900 text-slate-800 dark:text-slate-200 font-extrabold text-[11px]">
                                <i class="fa-solid fa-file-invoice-dollar mr-1 text-slate-400"></i> SALES (QUOTATION)
                            </th>

                            {{-- Group Header: Profitability --}}
                            <th colspan="2" class="px-3 py-2 text-center bg-slate-100 dark:bg-slate-900 text-slate-800 dark:text-slate-200 font-extrabold text-[11px]">
                                <i class="fa-solid fa-chart-line mr-1 text-slate-400"></i> PROFITABILITY
                            </th>
                        </tr>
                        <tr class="border-b-2 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-300 text-[10px] font-bold uppercase">
                            {{-- Engineering Sub-headers --}}
                            <th class="px-2 py-2 text-right border-r border-slate-200 dark:border-slate-700">
                                Material Cost
                            </th>
                            <th class="px-2 py-2 text-right border-r border-slate-200 dark:border-slate-700">
                                Mfg Process Cost
                            </th>
                            <th class="px-2 py-2 text-right border-r border-slate-200 dark:border-slate-700 font-extrabold text-slate-800 dark:text-slate-100">
                                COGS
                            </th>

                            {{-- Sales Sub-headers --}}
                            <th class="px-2 py-2 text-right border-r border-slate-200 dark:border-slate-700">
                                Material Cost
                            </th>
                            <th class="px-2 py-2 text-right border-r border-slate-200 dark:border-slate-700">
                                Mfg Process Cost
                            </th>
                            <th class="px-2 py-2 text-right border-r border-slate-200 dark:border-slate-700 font-extrabold text-slate-800 dark:text-slate-100">
                                COGS
                            </th>

                            {{-- Profitability Sub-headers --}}
                            <th class="px-2 py-2 text-right border-r border-slate-200 dark:border-slate-700">
                                Margin (IDR)
                            </th>
                            <th class="px-2 py-2 text-right font-extrabold text-slate-800 dark:text-slate-100">
                                Margin (%)
                            </th>
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
            {{-- Main Tooling Cost Matrix (Neutral Styling) --}}
            <div class="overflow-x-auto rounded-sm border border-slate-300 dark:border-slate-700">
                <table class="w-full text-xs text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100 dark:bg-slate-900 border-b border-slate-300 dark:border-slate-700">
                            <th class="w-28 border border-slate-300 dark:border-slate-700 p-2.5 text-center font-bold text-slate-500 dark:text-slate-400"></th>
                            <th class="text-slate-800 dark:text-slate-200 font-extrabold text-xs uppercase text-center p-2.5 border border-slate-300 dark:border-slate-700 tracking-wider">
                                Tooling Cost
                            </th>
                            <th colspan="2" class="text-slate-800 dark:text-slate-200 font-extrabold text-xs uppercase tracking-wider text-center p-2 border border-slate-300 dark:border-slate-700">
                                <i class="fa-solid fa-wrench mr-1 text-slate-400"></i> Engineering (Eng)
                            </th>
                            <th colspan="2" class="text-slate-800 dark:text-slate-200 font-extrabold text-xs uppercase tracking-wider text-center p-2 border border-slate-300 dark:border-slate-700">
                                <i class="fa-solid fa-file-invoice-dollar mr-1 text-slate-400"></i> Sales
                            </th>
                        </tr>
                        <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-300 font-bold text-[11px] uppercase border-b border-slate-300 dark:border-slate-700">
                            <th class="border border-slate-300 dark:border-slate-700 p-2.5 text-center w-28">Stage</th>
                            <th class="border border-slate-300 dark:border-slate-700 p-2.5">Criteria</th>
                            <th class="border border-slate-300 dark:border-slate-700 p-2 text-center w-20">
                                Rate (%)
                            </th>
                            <th class="border border-slate-300 dark:border-slate-700 p-2 text-right w-48">
                                Amount (IDR)
                            </th>
                            <th class="border border-slate-300 dark:border-slate-700 p-2 text-center w-20">
                                Rate (%)
                            </th>
                            <th class="border border-slate-300 dark:border-slate-700 p-2 text-right w-48">
                                Amount (IDR)
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        {{-- 1. COGM: Tooling --}}
                        <tr>
                            <td class="border border-slate-300 dark:border-slate-700 p-3 text-center font-bold text-xs text-slate-600 dark:text-slate-400 bg-slate-50/50 dark:bg-slate-900/30 align-middle">
                                COGM
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 font-medium text-slate-700 dark:text-slate-300">
                                Tooling Cost (Dies / Jigs / Fixtures)
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-center text-slate-400">
                                -
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-right font-medium text-slate-800 dark:text-slate-200">
                                Rp {{ number_format($comparisonResult['tooling']['cogm_eng'], 2, ',', '.') }}
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-center text-slate-400">
                                -
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-right font-medium text-slate-800 dark:text-slate-200">
                                Rp {{ number_format($comparisonResult['tooling']['cogm_sales'], 2, ',', '.') }}
                            </td>
                        </tr>

                        {{-- 2. Others: O/H + Profit --}}
                        <tr>
                            <td class="border border-slate-300 dark:border-slate-700 p-3 text-center font-bold text-xs text-slate-600 dark:text-slate-400 bg-slate-50/50 dark:bg-slate-900/30 align-middle">
                                Others
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 font-medium text-slate-700 dark:text-slate-300">
                                O/H + Profit
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-center font-bold text-slate-700 dark:text-slate-300 bg-slate-50/30 dark:bg-slate-900/20">
                                0.00%
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-right font-medium text-slate-800 dark:text-slate-200">
                                Rp 0,00
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-center font-bold text-slate-700 dark:text-slate-300 bg-slate-50/30 dark:bg-slate-900/20">
                                {{ number_format($comparisonResult['tooling']['oh_profit_sales_pct'], 2) }}%
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-right font-semibold text-slate-800 dark:text-slate-200">
                                Rp {{ number_format($comparisonResult['tooling']['oh_profit_sales_val'], 2, ',', '.') }}
                            </td>
                        </tr>

                        {{-- 3. COGS Total --}}
                        <tr class="bg-slate-50 dark:bg-slate-900/80 font-black text-sm">
                            <td class="border border-slate-300 dark:border-slate-700 p-3 text-center text-slate-800 dark:text-white uppercase tracking-wider">
                                COGS
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-3 text-slate-800 dark:text-white font-black">
                                Total ( COGM + O/H Profit )
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-3 text-center text-slate-400">
                                -
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-3 text-right text-slate-900 dark:text-white font-black">
                                Rp {{ number_format($comparisonResult['tooling']['cogs_eng'], 2, ',', '.') }}
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-3 text-center text-slate-400">
                                -
                            </td>
                            <td class="border border-slate-300 dark:border-slate-700 p-3 text-right text-slate-900 dark:text-white font-black">
                                Rp {{ number_format($comparisonResult['tooling']['cogs_sales'], 2, ',', '.') }}
                            </td>
                        </tr>

                        {{-- 4. Margin --}}
                        <tr class="bg-slate-50/70 dark:bg-slate-900/50 font-bold">
                            <td class="border border-slate-300 dark:border-slate-700 p-3 text-center text-slate-700 dark:text-slate-300 uppercase">
                                Margin
                            </td>
                            <td colspan="5" class="border border-slate-300 dark:border-slate-700 p-3 text-slate-900 dark:text-white">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span><strong>= COGS SALES - COGS ENG</strong></span>
                                    <div class="text-right">
                                        @if($comparisonResult['tooling']['margin_idr'] >= 0)
                                            <span class="text-sm text-emerald-600 dark:text-emerald-400 font-black">
                                                +Rp {{ number_format($comparisonResult['tooling']['margin_idr'], 2, ',', '.') }}
                                            </span>
                                            <span class="ml-2 px-2 py-0.5 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 rounded-sm text-xs font-black">
                                                +{{ number_format($comparisonResult['tooling']['margin_pct'], 2) }}%
                                            </span>
                                        @else
                                            <span class="text-sm text-rose-600 dark:text-rose-400 font-black">
                                                Rp {{ number_format($comparisonResult['tooling']['margin_idr'], 2, ',', '.') }}
                                            </span>
                                            <span class="ml-2 px-2 py-0.5 bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 rounded-sm text-xs font-black">
                                                {{ number_format($comparisonResult['tooling']['margin_pct'], 2) }}%
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>

                        {{-- 5. Std Margin --}}
                        <tr class="bg-slate-50/70 dark:bg-slate-900/50 font-bold">
                            <td class="border border-slate-300 dark:border-slate-700 p-3 text-center text-slate-700 dark:text-slate-300 uppercase">
                                Std Margin
                            </td>
                            <td colspan="5" class="border border-slate-300 dark:border-slate-700 p-3 text-slate-900 dark:text-white">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span><strong>= Min. {{ number_format($comparisonResult['tooling']['target_std_margin_pct'], 2) }}%</strong> <span class="text-xs font-normal text-slate-500 dark:text-slate-400">(Standard Minimum Tooling Margin)</span></span>
                                    <span class="px-2.5 py-1 text-xs font-black rounded-sm {{ $comparisonResult['tooling']['status_badge'] }}">
                                        {{ $comparisonResult['tooling']['status_text'] }}
                                    </span>
                                </div>
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
                            <th class="px-3 py-2.5 text-center w-10 border-r border-slate-200 dark:border-slate-700">No.</th>
                            <th class="px-3 py-2.5 border-r border-slate-200 dark:border-slate-700">Part No & Name</th>
                            <th class="px-2 py-2.5 text-center border-r border-slate-200 dark:border-slate-700">Rank</th>
                            <th class="px-2 py-2.5 text-center border-r border-slate-200 dark:border-slate-700">Category</th>
                            <th class="px-3 py-2.5 border-r border-slate-200 dark:border-slate-700">OP & Process Name</th>
                            <th class="px-2 py-2.5 text-center border-r border-slate-200 dark:border-slate-700">Machine / Tons</th>
                            <th class="px-2 py-2.5 text-center border-r border-slate-200 dark:border-slate-700">Qty</th>
                            <th class="px-3 py-2.5 text-right border-r border-slate-200 dark:border-slate-700">
                                Tooling Cost (Eng)
                            </th>
                            <th class="px-2 py-2.5 text-center border-r border-slate-200 dark:border-slate-700">
                                O/H (%)
                            </th>
                            <th class="px-3 py-2.5 text-right border-r border-slate-200 dark:border-slate-700 font-extrabold text-slate-800 dark:text-slate-100">
                                Tooling Cost (Sales)
                            </th>
                            <th class="px-3 py-2.5 text-right border-r border-slate-200 dark:border-slate-700">
                                Margin (IDR)
                            </th>
                            <th class="px-2 py-2.5 text-right font-extrabold text-slate-800 dark:text-slate-100">
                                Margin (%)
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($comparisonResult['tooling']['items'] as $idx => $tItem)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="px-3 py-2 text-center font-bold text-slate-400 border-r border-slate-100 dark:border-slate-800">
                                    {{ $idx + 1 }}
                                </td>
                                <td class="px-3 py-2 border-r border-slate-100 dark:border-slate-800 font-semibold">
                                    <div class="font-extrabold text-slate-800 dark:text-white">{{ $tItem['part_no'] }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $tItem['part_name'] }}</div>
                                </td>
                                <td class="px-2 py-2 text-center border-r border-slate-100 dark:border-slate-800">
                                    @if($tItem['tool_rank'] && $tItem['tool_rank'] !== '-')
                                        <span class="px-1.5 py-0.5 text-[9px] font-black rounded-sm bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">{{ $tItem['tool_rank'] }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-2 py-2 text-center border-r border-slate-100 dark:border-slate-800">
                                    <span class="px-1.5 py-0.5 text-[9px] font-black rounded-sm bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                        {{ $tItem['category'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 border-r border-slate-100 dark:border-slate-800">
                                    <span class="font-bold text-slate-800 dark:text-slate-200">OP {{ $tItem['op'] }}: {{ $tItem['process_name'] }}</span>
                                </td>
                                <td class="px-2 py-2 text-center border-r border-slate-100 dark:border-slate-800 text-[11px] text-slate-600 dark:text-slate-300">
                                    {{ $tItem['machine_type'] }} @if($tItem['tonnage']) ({{ $tItem['tonnage'] }}T) @endif
                                </td>
                                <td class="px-2 py-2 text-center border-r border-slate-100 dark:border-slate-800 font-bold text-slate-800 dark:text-slate-200">
                                    {{ $tItem['qty'] }}
                                </td>
                                <td class="px-3 py-2 text-right border-r border-slate-100 dark:border-slate-800 font-medium text-slate-800 dark:text-slate-200">
                                    Rp {{ number_format($tItem['total_cost_eng'], 0, ',', '.') }}
                                </td>
                                <td class="px-2 py-2 text-center border-r border-slate-100 dark:border-slate-800 font-bold text-slate-700 dark:text-slate-300">
                                    {{ number_format($tItem['oh_profit_pct'], 1) }}%
                                </td>
                                <td class="px-3 py-2 text-right border-r border-slate-100 dark:border-slate-800 font-bold text-slate-900 dark:text-white">
                                    Rp {{ number_format($tItem['total_cost_sales'], 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-2 text-right border-r border-slate-100 dark:border-slate-800 font-bold">
                                    @if($tItem['margin_idr'] > 0)
                                        <span class="text-emerald-600 dark:text-emerald-400">+Rp {{ number_format($tItem['margin_idr'], 0, ',', '.') }}</span>
                                    @elseif($tItem['margin_idr'] < 0)
                                        <span class="text-rose-600 dark:text-rose-400">Rp {{ number_format($tItem['margin_idr'], 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-slate-400">Rp 0</span>
                                    @endif
                                </td>
                                <td class="px-2 py-2 text-right font-black">
                                    @if($tItem['margin_pct'] > 0)
                                        <span class="px-1.5 py-0.5 text-[10px] font-black rounded-sm bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">+{{ number_format($tItem['margin_pct'], 1) }}%</span>
                                    @elseif($tItem['margin_pct'] < 0)
                                        <span class="px-1.5 py-0.5 text-[10px] font-black rounded-sm bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800">{{ number_format($tItem['margin_pct'], 1) }}%</span>
                                    @else
                                        <span class="px-1.5 py-0.5 text-[10px] font-bold rounded-sm bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">0.0%</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="px-4 py-8 text-center text-slate-400 italic">
                                    <i class="fa-solid fa-circle-info mr-1"></i> No tooling processes or investment records found in this EBD project.
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
            subEng: 'Direct Manufacturing HPP',
            iconEng: 'fa-solid fa-wrench',
            cogsSales: 'Rp {{ number_format($comparisonResult['totals']['cogs_sales'], 0, ',', '.') }}',
            subSales: 'Commercial Quotation Price',
            marginPct: '{{ number_format($comparisonResult['margin_pct'], 2) }}%',
            marginIdr: '+Rp {{ number_format($comparisonResult['margin_idr'], 0, ',', '.') }}'
        },
        tooling: {
            cogsEng: 'Rp {{ number_format($comparisonResult['tooling']['cogs_eng'], 0, ',', '.') }}',
            subEng: 'Tooling Manufacturing HPP',
            iconEng: 'fa-solid fa-gears',
            cogsSales: 'Rp {{ number_format($comparisonResult['tooling']['cogs_sales'], 0, ',', '.') }}',
            subSales: 'Commercial Tooling Price (+O/H)',
            marginPct: '{{ number_format($comparisonResult['tooling']['margin_pct'], 2) }}%',
            marginIdr: '+Rp {{ number_format($comparisonResult['tooling']['margin_idr'], 0, ',', '.') }}'
        }
    };

    function switchTab(tab) {
        const btnProduct = document.getElementById('tab-btn-product-cost');
        const btnTooling = document.getElementById('tab-btn-tooling-cost');
        const iconProduct = document.getElementById('tab-icon-product-cost');
        const iconTooling = document.getElementById('tab-icon-tooling-cost');
        const badgeProduct = document.getElementById('tab-badge-product-cost');
        const badgeTooling = document.getElementById('tab-badge-tooling-cost');

        const contentProduct = document.getElementById('tab-content-product-cost');
        const contentTooling = document.getElementById('tab-content-tooling-cost');

        const valCogsEng = document.getElementById('kpi-val-cogs-eng');
        const subCogsEng = document.getElementById('kpi-sub-cogs-eng');
        const iconCogsEng = document.getElementById('kpi-icon-cogs-eng');
        const valCogsSales = document.getElementById('kpi-val-cogs-sales');
        const subCogsSales = document.getElementById('kpi-sub-cogs-sales');
        const valMarginPct = document.getElementById('kpi-val-margin-pct');
        const valMarginIdr = document.getElementById('kpi-val-margin-idr');

        if (tab === 'product-cost') {
            // Underline active tab styling for Product
            btnProduct.className = 'tab-btn inline-flex items-center gap-2 py-3 px-1 border-b-2 font-bold text-xs transition-all cursor-pointer border-blue-600 text-blue-600 dark:text-blue-400';
            iconProduct.className = 'fa-solid fa-cube text-sm text-blue-600 dark:text-blue-400';
            badgeProduct.className = 'px-2 py-0.5 text-[10px] font-bold rounded-full bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 transition-colors';

            // Inactive tab styling for Tooling
            btnTooling.className = 'tab-btn inline-flex items-center gap-2 py-3 px-1 border-b-2 font-bold text-xs transition-all cursor-pointer border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:border-slate-300 dark:hover:border-slate-600';
            iconTooling.className = 'fa-solid fa-wrench text-sm text-slate-400 dark:text-slate-500';
            badgeTooling.className = 'px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 transition-colors';

            // Show Product Content
            contentProduct.classList.remove('hidden');
            contentTooling.classList.add('hidden');

            // Update KPI cards to Product values
            if (valCogsEng) valCogsEng.textContent = kpiData.product.cogsEng;
            if (subCogsEng) subCogsEng.textContent = kpiData.product.subEng;
            if (iconCogsEng) iconCogsEng.className = kpiData.product.iconEng + ' text-xs';
            if (valCogsSales) valCogsSales.textContent = kpiData.product.cogsSales;
            if (subCogsSales) subCogsSales.textContent = kpiData.product.subSales;
            if (valMarginPct) valMarginPct.textContent = kpiData.product.marginPct;
            if (valMarginIdr) valMarginIdr.textContent = kpiData.product.marginIdr;
        } else if (tab === 'tooling-cost') {
            // Underline active tab styling for Tooling
            btnTooling.className = 'tab-btn inline-flex items-center gap-2 py-3 px-1 border-b-2 font-bold text-xs transition-all cursor-pointer border-blue-600 text-blue-600 dark:text-blue-400';
            iconTooling.className = 'fa-solid fa-wrench text-sm text-blue-600 dark:text-blue-400';
            badgeTooling.className = 'px-2 py-0.5 text-[10px] font-bold rounded-full bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 transition-colors';

            // Inactive tab styling for Product
            btnProduct.className = 'tab-btn inline-flex items-center gap-2 py-3 px-1 border-b-2 font-bold text-xs transition-all cursor-pointer border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:border-slate-300 dark:hover:border-slate-600';
            iconProduct.className = 'fa-solid fa-cube text-sm text-slate-400 dark:text-slate-500';
            badgeProduct.className = 'px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 transition-colors';

            // Show Tooling Content
            contentTooling.classList.remove('hidden');
            contentProduct.classList.add('hidden');

            // Update KPI cards to Tooling values
            if (valCogsEng) valCogsEng.textContent = kpiData.tooling.cogsEng;
            if (subCogsEng) subCogsEng.textContent = kpiData.tooling.subEng;
            if (iconCogsEng) iconCogsEng.className = kpiData.tooling.iconEng + ' text-xs';
            if (valCogsSales) valCogsSales.textContent = kpiData.tooling.cogsSales;
            if (subCogsSales) subCogsSales.textContent = kpiData.tooling.subSales;
            if (valMarginPct) valMarginPct.textContent = kpiData.tooling.marginPct;
            if (valMarginIdr) valMarginIdr.textContent = kpiData.tooling.marginIdr;
        }

        // Auto-adjust DataTable columns on tab switch
        setTimeout(() => {
            if (typeof $.fn.dataTable !== 'undefined') {
                $($.fn.dataTable.tables(true)).DataTable().columns.adjust();
            }
        }, 50);
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.defaultDataTable === 'function') {
            // Product Breakdown DataTables (Server-side)
            window.defaultDataTable('#part-breakdown-table', {
                serverSide: true,
                processing: true,
                pageLength: 25,
                ordering: true,
                order: [[0, 'asc']],
                ajax: '{{ route("management.product-cost-comparison.items-data", $comparisonResult["ebd_header"]->id) }}',
                columns: [
                    {
                        data: 'index',
                        orderable: false,
                        className: 'px-3 py-2.5 text-center font-bold text-slate-400 border-r border-slate-100 dark:border-slate-800'
                    },
                    {
                        data: 'part_no',
                        orderable: true,
                        className: 'px-3 py-2.5 border-r border-slate-100 dark:border-slate-800 font-semibold',
                        render: function (data, type, row) {
                            return `
                                <div class="font-extrabold text-slate-800 dark:text-white">${row.part_no}</div>
                                <div class="text-[10px] text-slate-400">${row.part_name}</div>
                            `;
                        }
                    },
                    {
                        data: 'part_rank',
                        orderable: true,
                        className: 'px-2 py-2.5 text-center border-r border-slate-100 dark:border-slate-800',
                        render: function (data) {
                            if (data && data !== '-') {
                                return `<span class="px-1.5 py-0.5 text-[9px] font-black rounded-sm bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">${data}</span>`;
                            }
                            return '-';
                        }
                    },
                    {
                        data: 'mat_spec',
                        orderable: true,
                        className: 'px-3 py-2.5 border-r border-slate-100 dark:border-slate-800 font-medium text-slate-700 dark:text-slate-300'
                    },
                    {
                        data: 'total_process',
                        orderable: true,
                        className: 'px-2 py-2.5 text-center border-r border-slate-100 dark:border-slate-800 font-bold',
                        render: function (data, type, row) {
                            return `<span class="px-2 py-0.5 text-[10px] font-black rounded-sm bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">${data} Procs</span>`;
                        }
                    },
                    {
                        data: 'eng_mat_cost',
                        orderable: true,
                        className: 'px-2 py-2.5 text-right border-r border-slate-100 dark:border-slate-800 font-medium text-slate-800 dark:text-slate-200',
                        render: function (data) {
                            return Number(data || 0).toLocaleString('id-ID');
                        }
                    },
                    {
                        data: 'eng_mfg_cost',
                        orderable: true,
                        className: 'px-2 py-2.5 text-right border-r border-slate-100 dark:border-slate-800 font-medium text-slate-800 dark:text-slate-200',
                        render: function (data) {
                            return Number(data || 0).toLocaleString('id-ID');
                        }
                    },
                    {
                        data: 'eng_cogs',
                        orderable: true,
                        className: 'px-2 py-2.5 text-right border-r border-slate-100 dark:border-slate-800 font-extrabold text-slate-900 dark:text-white',
                        render: function (data) {
                            return Number(data || 0).toLocaleString('id-ID');
                        }
                    },
                    {
                        data: 'sales_mat_cost',
                        orderable: true,
                        className: 'px-2 py-2.5 text-right border-r border-slate-100 dark:border-slate-800 font-medium text-slate-800 dark:text-slate-200',
                        render: function (data) {
                            return Number(data || 0).toLocaleString('id-ID');
                        }
                    },
                    {
                        data: 'sales_mfg_cost',
                        orderable: true,
                        className: 'px-2 py-2.5 text-right border-r border-slate-100 dark:border-slate-800 font-medium text-slate-800 dark:text-slate-200',
                        render: function (data) {
                            return Number(data || 0).toLocaleString('id-ID');
                        }
                    },
                    {
                        data: 'sales_cogs',
                        orderable: true,
                        className: 'px-2 py-2.5 text-right border-r border-slate-100 dark:border-slate-800 font-extrabold text-slate-900 dark:text-white',
                        render: function (data) {
                            return Number(data || 0).toLocaleString('id-ID');
                        }
                    },
                    {
                        data: 'margin_idr',
                        orderable: true,
                        className: 'px-2 py-2.5 text-right border-r border-slate-100 dark:border-slate-800 font-bold',
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
                        className: 'px-2 py-2.5 text-right font-black',
                        render: function (data) {
                            const val = Number(data || 0);
                            if (val > 0) {
                                return `<span class="px-1.5 py-0.5 text-[10px] font-black rounded-sm bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">+${val.toFixed(1)}%</span>`;
                            } else if (val < 0) {
                                return `<span class="px-1.5 py-0.5 text-[10px] font-black rounded-sm bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800">${val.toFixed(1)}%</span>`;
                            }
                            return `<span class="px-1.5 py-0.5 text-[10px] font-bold rounded-sm bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">0.0%</span>`;
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
    });
</script>
@endsection
