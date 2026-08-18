@extends('layouts.app')

@section('title', 'Product Cost Comparison Detail · Promise Management')

@section('content')
<div class="flex-1 overflow-y-auto p-4 pt-17.5 space-y-5 transition-colors duration-200">

    {{-- Page Header & Back Navigation --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('management.product-cost-comparison.index') }}"
               class="w-8 h-8 rounded-xs bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors shadow-xs">
                <i class="fa-solid fa-arrow-left text-xs"></i>
            </a>
            <div>
                <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none mb-1">
                    Cost Estimation Matrix Detail
                </div>
                <h1 class="text-xl font-extrabold tracking-tight text-slate-800 dark:text-white leading-none flex items-center gap-2">
                    <i class="fa-solid fa-scale-balanced text-blue-600 dark:text-blue-400 text-lg"></i>
                    {{ $comparisonResult['project_model']->name ?? 'Model' }} — {{ $comparisonResult['customer']->name ?? 'Customer' }}
                </h1>
                <p class="text-xs text-slate-400 mt-1">
                    EBD Rev: <strong>{{ $comparisonResult['ebd_header']->revision ?? '0' }}</strong> |
                    Date: <strong>{{ $comparisonResult['ebd_header']->date ? $comparisonResult['ebd_header']->date->format('d/m/Y') : '-' }}</strong> |
                    Total Parts: <strong>{{ count($comparisonResult['items']) }} Parts</strong>
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('management.product-cost-comparison.export', ['ebd_header_id' => $comparisonResult['ebd_header']->id]) }}"
               class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xs shadow-xs transition-colors">
                <i class="fa-solid fa-file-excel text-[11px]"></i> Export CSV
            </a>
        </div>
    </div>

    {{-- KPI Summary Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-4 bg-white dark:bg-slate-800 rounded-xs border border-slate-200 dark:border-slate-700 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">BOM Structure</span>
                <div class="text-xl font-black text-slate-800 dark:text-white mt-0.5">{{ count($comparisonResult['items']) }} Parts</div>
                <span class="text-[10px] text-slate-400">{{ $comparisonResult['project_model']->name ?? 'Model' }}</span>
            </div>
            <div class="w-10 h-10 rounded-xs bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 flex items-center justify-center font-bold">
                <i class="fa-solid fa-boxes-stacked text-base"></i>
            </div>
        </div>

        <div class="p-4 bg-white dark:bg-slate-800 rounded-xs border border-slate-200 dark:border-slate-700 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">Total COGS (Engineering)</span>
                <div class="text-xl font-black text-blue-700 dark:text-blue-300 mt-0.5">
                    Rp {{ number_format($comparisonResult['totals']['cogs_eng'], 0, ',', '.') }}
                </div>
                <span class="text-[10px] text-slate-400">Direct Manufacturing HPP</span>
            </div>
            <div class="w-10 h-10 rounded-xs bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold">
                <i class="fa-solid fa-wrench text-base"></i>
            </div>
        </div>

        <div class="p-4 bg-white dark:bg-slate-800 rounded-xs border border-slate-200 dark:border-slate-700 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Total COGS (Sales)</span>
                <div class="text-xl font-black text-indigo-700 dark:text-indigo-300 mt-0.5">
                    Rp {{ number_format($comparisonResult['totals']['cogs_sales'], 0, ',', '.') }}
                </div>
                <span class="text-[10px] text-slate-400">Commercial Quotation Price</span>
            </div>
            <div class="w-10 h-10 rounded-xs bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                <i class="fa-solid fa-file-invoice-dollar text-base"></i>
            </div>
        </div>

        <div class="p-4 bg-white dark:bg-slate-800 rounded-xs border border-slate-200 dark:border-slate-700 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Profit Margin</span>
                <div class="text-xl font-black text-emerald-700 dark:text-emerald-300 mt-0.5">
                    {{ number_format($comparisonResult['margin_pct'], 2) }}%
                </div>
                <span class="text-[10px] font-bold text-slate-500">
                    +Rp {{ number_format($comparisonResult['margin_idr'], 0, ',', '.') }}
                </span>
            </div>
            <div class="w-10 h-10 rounded-xs bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                <i class="fa-solid fa-chart-line text-base"></i>
            </div>
        </div>
    </div>

    {{-- Status Evaluation Banner --}}
    <div class="p-3.5 rounded-xs border flex items-center justify-between {{ $comparisonResult['status_badge'] }}">
        <div class="flex items-center gap-2.5">
            <i class="fa-solid fa-shield-halved text-base"></i>
            <div>
                <span class="font-extrabold text-xs uppercase tracking-wider block">Profitability Evaluation Status: {{ $comparisonResult['status_text'] }}</span>
                <span class="text-[11px] opacity-90">
                    Target Std Margin Sales: <strong>Min. {{ number_format($comparisonResult['target_margin_sales'], 2) }}%</strong> |
                    Std Margin Eng: <strong>Min. {{ number_format($comparisonResult['target_margin_eng'], 2) }}%</strong> |
                    Current Margin: <strong>{{ number_format($comparisonResult['margin_pct'], 2) }}%</strong>
                </span>
            </div>
        </div>
        <div class="text-xs font-black px-2.5 py-1 rounded-xs bg-white/60 dark:bg-black/30">
            {{ $comparisonResult['status'] }}
        </div>
    </div>

    {{-- Main Comparison Matrix (Exact Match to User's Reference Layout) --}}
    <div class="bg-white dark:bg-slate-800 rounded-xs border border-slate-200 dark:border-slate-700 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-900/50">
            <h2 class="text-sm font-extrabold text-slate-800 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-table-cells text-amber-500"></i> Product Cost Structure Matrix
            </h2>
            <div class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                Customer: <span class="font-bold text-slate-700 dark:text-slate-200">{{ $comparisonResult['customer']->name ?? 'Global' }}</span> |
                Model: <span class="font-bold text-slate-700 dark:text-slate-200">{{ $comparisonResult['project_model']->name ?? '-' }}</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left border-collapse">
                {{-- Header with Yellow Product Cost Accent --}}
                <thead>
                    <tr>
                        <th class="w-28 bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 p-2.5 text-center font-bold text-slate-600 dark:text-slate-400"></th>
                        <th class="bg-amber-300 dark:bg-amber-500 text-slate-900 font-black text-sm uppercase text-center p-2.5 border border-slate-300 dark:border-slate-700 tracking-wider">
                            Product Cost
                        </th>
                        <th colspan="2" class="bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-700"></th>
                    </tr>
                    <tr class="bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-extrabold text-[11px] uppercase">
                        <th class="border border-slate-300 dark:border-slate-700 p-2.5 text-center w-28">Stage</th>
                        <th class="border border-slate-300 dark:border-slate-700 p-2.5">Criteria</th>
                        <th class="border border-slate-300 dark:border-slate-700 p-2.5 text-right w-56 bg-sky-100 dark:bg-sky-950/60 text-sky-950 dark:text-sky-200 font-black">
                            <i class="fa-solid fa-wrench mr-1 text-sky-600 dark:text-sky-400"></i> Engineering (Eng)
                        </th>
                        <th class="border border-slate-300 dark:border-slate-700 p-2.5 text-right w-56 bg-amber-100 dark:bg-amber-950/60 text-amber-950 dark:text-amber-200 font-black">
                            <i class="fa-solid fa-file-invoice-dollar mr-1 text-amber-600 dark:text-amber-400"></i> Sales
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    {{-- 1. COGM: Material --}}
                    <tr>
                        <td rowspan="3" class="border border-slate-300 dark:border-slate-700 p-3 text-center font-black text-sm text-slate-800 dark:text-white bg-slate-50/70 dark:bg-slate-900/40 align-middle">
                            COGM
                        </td>
                        <td class="border border-slate-300 dark:border-slate-700 p-2.5 font-bold text-slate-700 dark:text-slate-300">
                            Material Cost
                        </td>
                        <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-right font-bold text-sky-950 dark:text-sky-100 bg-sky-50/80 dark:bg-sky-950/40">
                            Rp {{ number_format($comparisonResult['totals']['material_eng'], 2, ',', '.') }}
                        </td>
                        <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-right font-bold text-amber-950 dark:text-amber-100 bg-amber-50/80 dark:bg-amber-950/40">
                            Rp {{ number_format($comparisonResult['totals']['material_sales'], 2, ',', '.') }}
                        </td>
                    </tr>

                    {{-- 1. COGM: Mfg --}}
                    <tr>
                        <td class="border border-slate-300 dark:border-slate-700 p-2.5 font-bold text-slate-700 dark:text-slate-300">
                            Mfg Cost (Stamping & Processes)
                        </td>
                        <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-right font-bold text-sky-950 dark:text-sky-100 bg-sky-50/80 dark:bg-sky-950/40">
                            Rp {{ number_format($comparisonResult['totals']['mfg_eng'], 2, ',', '.') }}
                        </td>
                        <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-right font-bold text-amber-950 dark:text-amber-100 bg-amber-50/80 dark:bg-amber-950/40">
                            Rp {{ number_format($comparisonResult['totals']['mfg_sales'], 2, ',', '.') }}
                        </td>
                    </tr>

                    {{-- 1. COGM Subtotal --}}
                    <tr class="bg-slate-100/60 dark:bg-slate-800/60 font-black">
                        <td class="border border-slate-300 dark:border-slate-700 p-2 text-slate-600 dark:text-slate-400 uppercase text-[10px]">
                            Subtotal COGM
                        </td>
                        <td class="border border-slate-300 dark:border-slate-700 p-2 text-right text-sky-950 dark:text-sky-100 bg-sky-100/90 dark:bg-sky-900/60">
                            Rp {{ number_format($comparisonResult['totals']['cogm_eng'], 2, ',', '.') }}
                        </td>
                        <td class="border border-slate-300 dark:border-slate-700 p-2 text-right text-amber-950 dark:text-amber-100 bg-amber-100/90 dark:bg-amber-900/60">
                            Rp {{ number_format($comparisonResult['totals']['cogm_sales'], 2, ',', '.') }}
                        </td>
                    </tr>

                    {{-- 2. Others: Admin Material --}}
                    <tr>
                        <td rowspan="3" class="border border-slate-300 dark:border-slate-700 p-3 text-center font-black text-sm text-slate-800 dark:text-white bg-slate-50/70 dark:bg-slate-900/40 align-middle">
                            Others
                        </td>
                        <td class="border border-slate-300 dark:border-slate-700 p-2.5 font-medium text-slate-700 dark:text-slate-300 flex justify-between items-center">
                            <span>Admin matrl.</span>
                            <span class="text-[10px] text-slate-400">
                                Eng: {{ $comparisonResult['policy_eng']->admin_matrl_pct }}% | Sales: Follow Cust Rate ({{ $comparisonResult['policy_sales']->admin_matrl_pct }}%)
                            </span>
                        </td>
                        <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-right font-semibold text-sky-950 dark:text-sky-100 bg-sky-50/80 dark:bg-sky-950/40">
                            Rp {{ number_format($comparisonResult['totals']['admin_matrl_eng'], 2, ',', '.') }}
                        </td>
                        <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-right font-semibold text-amber-950 dark:text-amber-100 bg-amber-50/80 dark:bg-amber-950/40">
                            Rp {{ number_format($comparisonResult['totals']['admin_matrl_sales'], 2, ',', '.') }}
                        </td>
                    </tr>

                    {{-- 2. Others: Admin Mfg --}}
                    <tr>
                        <td class="border border-slate-300 dark:border-slate-700 p-2.5 font-medium text-slate-700 dark:text-slate-300 flex justify-between items-center">
                            <span>Admin mfg.</span>
                            <span class="text-[10px] text-slate-400">
                                Eng: {{ $comparisonResult['policy_eng']->admin_mfg_pct }}% | Sales: Follow Cust Rate ({{ $comparisonResult['policy_sales']->admin_mfg_pct }}%)
                            </span>
                        </td>
                        <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-right font-semibold text-sky-950 dark:text-sky-100 bg-sky-50/80 dark:bg-sky-950/40">
                            Rp {{ number_format($comparisonResult['totals']['admin_mfg_eng'], 2, ',', '.') }}
                        </td>
                        <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-right font-semibold text-amber-950 dark:text-amber-100 bg-amber-50/80 dark:bg-amber-950/40">
                            Rp {{ number_format($comparisonResult['totals']['admin_mfg_sales'], 2, ',', '.') }}
                        </td>
                    </tr>

                    {{-- 2. Others: O/H + Profit --}}
                    <tr>
                        <td class="border border-slate-300 dark:border-slate-700 p-2.5 font-medium text-slate-700 dark:text-slate-300 flex justify-between items-center">
                            <span>O/H + Profit</span>
                            <span class="text-[10px] text-slate-400">
                                Eng: 0% | Sales: Follow Sales Strategy ({{ $comparisonResult['policy_sales']->oh_profit_pct }}%)
                            </span>
                        </td>
                        <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-right font-semibold text-sky-950 dark:text-sky-100 bg-sky-50/80 dark:bg-sky-950/40">
                            Rp {{ number_format($comparisonResult['totals']['oh_profit_eng'], 2, ',', '.') }}
                        </td>
                        <td class="border border-slate-300 dark:border-slate-700 p-2.5 text-right font-semibold text-amber-950 dark:text-amber-100 bg-amber-50/80 dark:bg-amber-950/40">
                            Rp {{ number_format($comparisonResult['totals']['oh_profit_sales'], 2, ',', '.') }}
                        </td>
                    </tr>

                    {{-- 3. COGS Total --}}
                    <tr class="bg-slate-100 dark:bg-slate-900/80 font-black text-sm">
                        <td class="border border-slate-300 dark:border-slate-700 p-3 text-center text-slate-900 dark:text-white uppercase tracking-wider">
                            COGS
                        </td>
                        <td class="border border-slate-300 dark:border-slate-700 p-3 text-slate-900 dark:text-white">
                            Total ( COGM + Admin + O/H )
                        </td>
                        <td class="border border-slate-300 dark:border-slate-700 p-3 text-right text-sky-950 dark:text-white bg-sky-200/90 dark:bg-sky-800/60 font-black">
                            Rp {{ number_format($comparisonResult['totals']['cogs_eng'], 2, ',', '.') }}
                        </td>
                        <td class="border border-slate-300 dark:border-slate-700 p-3 text-right text-amber-950 dark:text-white bg-amber-200/90 dark:bg-amber-800/60 font-black">
                            Rp {{ number_format($comparisonResult['totals']['cogs_sales'], 2, ',', '.') }}
                        </td>
                    </tr>

                    {{-- 4. Margin (Yellow Highlight row as per reference) --}}
                    <tr class="bg-amber-100/80 dark:bg-amber-950/40 font-extrabold">
                        <td class="border border-slate-300 dark:border-slate-700 p-3 text-center bg-amber-200 dark:bg-amber-900 text-amber-950 dark:text-amber-100 uppercase">
                            Margin
                        </td>
                        <td colspan="3" class="border border-slate-300 dark:border-slate-700 p-3 text-slate-900 dark:text-amber-100">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span><strong>= COGS SALES - COGS ENG</strong></span>
                                <div class="text-right">
                                    <span class="text-sm text-emerald-800 dark:text-emerald-300 font-black">
                                        +Rp {{ number_format($comparisonResult['margin_idr'], 2, ',', '.') }}
                                    </span>
                                    <span class="ml-2 px-2 py-0.5 bg-emerald-600 text-white rounded-xs text-xs font-black">
                                        {{ number_format($comparisonResult['margin_pct'], 2) }}%
                                    </span>
                                </div>
                            </div>
                        </td>
                    </tr>

                    {{-- 5. Std Margin (Yellow Highlight row as per reference) --}}
                    <tr class="bg-amber-100/80 dark:bg-amber-950/40 font-extrabold">
                        <td class="border border-slate-300 dark:border-slate-700 p-3 text-center bg-amber-200 dark:bg-amber-900 text-amber-950 dark:text-amber-100 uppercase">
                            Std Margin
                        </td>
                        <td colspan="3" class="border border-slate-300 dark:border-slate-700 p-3 text-slate-900 dark:text-amber-100">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span><strong>= Min. {{ number_format($comparisonResult['target_margin_sales'], 2) }}%</strong></span>
                                <span class="px-2.5 py-1 text-xs font-black rounded-xs {{ $comparisonResult['status_badge'] }}">
                                    {{ $comparisonResult['status_text'] }}
                                </span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Detailed Per-Part Breakdown Table with x-table Component and DataTable --}}
    <div class="space-y-2">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-extrabold text-slate-800 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-list-check text-blue-600"></i> Detailed Per-Part Cost Breakdown ({{ count($comparisonResult['items']) }} Parts)
            </h2>
        </div>

        <x-table id="part-breakdown-table" class="w-full text-xs text-left border-collapse">
            <thead>
                <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900 text-[10px] font-extrabold text-slate-600 dark:text-slate-300 uppercase">
                    <th class="px-3 py-3 w-10 text-center border-r border-slate-200 dark:border-slate-700">No.</th>
                    <th class="px-3 py-3 border-r border-slate-200 dark:border-slate-700">Part No & Name</th>
                    <th class="px-2 py-3 text-center border-r border-slate-200 dark:border-slate-700">Rank</th>
                    <th class="px-3 py-3 border-r border-slate-200 dark:border-slate-700">Material Spec</th>
                    <th class="px-2 py-3 text-center border-r-2 border-sky-400 dark:border-sky-700">Total Process</th>
                    <th class="px-2 py-3 text-right border-r border-slate-200 dark:border-slate-700 bg-sky-100 dark:bg-sky-950/60 text-sky-950 dark:text-sky-200 font-bold">
                        Matrl (Eng)
                    </th>
                    <th class="px-2 py-3 text-right border-r border-slate-200 dark:border-slate-700 bg-sky-100 dark:bg-sky-950/60 text-sky-950 dark:text-sky-200 font-bold">
                        Mfg (Eng)
                    </th>
                    <th class="px-2 py-3 text-right border-r-2 border-amber-400 dark:border-amber-700 bg-sky-200 dark:bg-sky-900/70 text-sky-950 dark:text-sky-100 font-black">
                        COGS Eng
                    </th>
                    <th class="px-2 py-3 text-right border-r border-slate-200 dark:border-slate-700 bg-amber-100 dark:bg-amber-950/60 text-amber-950 dark:text-amber-200 font-bold">
                        Matrl (Sales)
                    </th>
                    <th class="px-2 py-3 text-right border-r border-slate-200 dark:border-slate-700 bg-amber-100 dark:bg-amber-950/60 text-amber-950 dark:text-amber-200 font-bold">
                        Mfg (Sales)
                    </th>
                    <th class="px-2 py-3 text-right border-r-2 border-emerald-400 dark:border-emerald-700 bg-amber-200 dark:bg-amber-900/70 text-amber-950 dark:text-amber-100 font-black">
                        COGS Sales
                    </th>
                    <th class="px-2 py-3 text-right border-r border-slate-200 dark:border-slate-700 bg-emerald-100 dark:bg-emerald-950/60 text-emerald-950 dark:text-emerald-200 font-bold">
                        Margin (IDR)
                    </th>
                    <th class="px-2 py-3 text-right bg-emerald-200 dark:bg-emerald-900/70 text-emerald-950 dark:text-emerald-100 font-black">
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.defaultDataTable === 'function') {
            window.defaultDataTable('#part-breakdown-table', {
                serverSide: true,
                processing: true,
                pageLength: 25,
                ordering: true,
                order: [[1, 'asc']],
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
                                return `<span class="px-1.5 py-0.5 text-[9px] font-black rounded-xs bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">${data}</span>`;
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
                        className: 'px-2 py-2.5 text-center border-r-2 border-sky-400 dark:border-sky-700 font-bold',
                        render: function (data, type, row) {
                            return `<span class="px-2 py-0.5 text-[10px] font-black rounded-xs bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">${data} Procs</span>`;
                        }
                    },
                    {
                        data: 'eng_mat_cost',
                        orderable: true,
                        className: 'px-2 py-2.5 text-right border-r border-slate-100 dark:border-slate-800 text-sky-950 dark:text-sky-100 bg-sky-50/80 dark:bg-sky-950/30',
                        render: function (data) {
                            return Number(data || 0).toLocaleString('id-ID');
                        }
                    },
                    {
                        data: 'eng_mfg_cost',
                        orderable: true,
                        className: 'px-2 py-2.5 text-right border-r border-slate-100 dark:border-slate-800 text-sky-950 dark:text-sky-100 bg-sky-50/80 dark:bg-sky-950/30',
                        render: function (data) {
                            return Number(data || 0).toLocaleString('id-ID');
                        }
                    },
                    {
                        data: 'eng_cogs',
                        orderable: true,
                        className: 'px-2 py-2.5 text-right border-r-2 border-amber-400 dark:border-amber-700 font-bold text-sky-950 dark:text-sky-100 bg-sky-100 dark:bg-sky-900/50',
                        render: function (data) {
                            return Number(data || 0).toLocaleString('id-ID');
                        }
                    },
                    {
                        data: 'sales_mat_cost',
                        orderable: true,
                        className: 'px-2 py-2.5 text-right border-r border-slate-100 dark:border-slate-800 text-amber-950 dark:text-amber-100 bg-amber-50/80 dark:bg-amber-950/30',
                        render: function (data) {
                            return Number(data || 0).toLocaleString('id-ID');
                        }
                    },
                    {
                        data: 'sales_mfg_cost',
                        orderable: true,
                        className: 'px-2 py-2.5 text-right border-r border-slate-100 dark:border-slate-800 text-amber-950 dark:text-amber-100 bg-amber-50/80 dark:bg-amber-950/30',
                        render: function (data) {
                            return Number(data || 0).toLocaleString('id-ID');
                        }
                    },
                    {
                        data: 'sales_cogs',
                        orderable: true,
                        className: 'px-2 py-2.5 text-right border-r-2 border-emerald-400 dark:border-emerald-700 font-bold text-amber-950 dark:text-amber-100 bg-amber-100 dark:bg-amber-900/50',
                        render: function (data) {
                            return Number(data || 0).toLocaleString('id-ID');
                        }
                    },
                    {
                        data: 'margin_idr',
                        orderable: true,
                        className: 'px-2 py-2.5 text-right border-r border-slate-100 dark:border-slate-800 font-bold text-emerald-800 dark:text-emerald-300 bg-emerald-50/80 dark:bg-emerald-950/30',
                        render: function (data) {
                            return '+' + Number(data || 0).toLocaleString('id-ID');
                        }
                    },
                    {
                        data: 'margin_pct',
                        orderable: true,
                        className: 'px-2 py-2.5 text-right font-black text-emerald-950 dark:text-emerald-200 bg-emerald-100 dark:bg-emerald-900/50',
                        render: function (data) {
                            return Number(data || 0).toFixed(1) + '%';
                        }
                    }
                ]
            });
        }
    });
</script>
@endsection
