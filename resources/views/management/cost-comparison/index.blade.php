@extends('layouts.app')

@section('title', 'Product Cost Comparison · Promise Management')

@section('content')
<div class="flex-1 overflow-y-auto p-4 pt-17.5 space-y-5 transition-colors duration-200">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none mb-1">
                Cost Estimation & Profitability Analysis
            </div>
            <h1 class="text-xl font-extrabold tracking-tight text-slate-800 dark:text-white leading-none flex items-center gap-2">
                <i class="fa-solid fa-scale-balanced text-blue-600 dark:text-blue-400 text-lg"></i>
                Product Cost Comparison
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Daftar perbandingan biaya HPP Engineering vs Harga Penawaran Sales per Customer dan Project Model EBD.
            </p>
        </div>
    </div>

    {{-- KPI Summary Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-4 bg-white dark:bg-slate-800 rounded-xs border border-slate-200 dark:border-slate-700 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Total Projects (EBD)</span>
                <div class="text-xl font-black text-slate-800 dark:text-white mt-0.5">{{ $overallKpi['total_projects'] }} Models</div>
                <span class="text-[10px] text-slate-400">Evaluated BOM Documents</span>
            </div>
            <div class="w-10 h-10 rounded-xs bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 flex items-center justify-center font-bold">
                <i class="fa-solid fa-folder-tree text-base"></i>
            </div>
        </div>

        <div class="p-4 bg-white dark:bg-slate-800 rounded-xs border border-slate-200 dark:border-slate-700 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">Total COGS (Eng)</span>
                <div class="text-xl font-black text-blue-700 dark:text-blue-300 mt-0.5">
                    Rp {{ number_format($overallKpi['total_cogs_eng'], 0, ',', '.') }}
                </div>
                <span class="text-[10px] text-slate-400">Total Direct Engineering HPP</span>
            </div>
            <div class="w-10 h-10 rounded-xs bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold">
                <i class="fa-solid fa-wrench text-base"></i>
            </div>
        </div>

        <div class="p-4 bg-white dark:bg-slate-800 rounded-xs border border-slate-200 dark:border-slate-700 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Total COGS (Sales)</span>
                <div class="text-xl font-black text-indigo-700 dark:text-indigo-300 mt-0.5">
                    Rp {{ number_format($overallKpi['total_cogs_sales'], 0, ',', '.') }}
                </div>
                <span class="text-[10px] text-slate-400">Total Quotation Commercial</span>
            </div>
            <div class="w-10 h-10 rounded-xs bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                <i class="fa-solid fa-file-invoice-dollar text-base"></i>
            </div>
        </div>

        <div class="p-4 bg-white dark:bg-slate-800 rounded-xs border border-slate-200 dark:border-slate-700 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Overall Profit Margin</span>
                <div class="text-xl font-black text-emerald-700 dark:text-emerald-300 mt-0.5">
                    {{ number_format($overallKpi['avg_margin_pct'], 2) }}%
                </div>
                <span class="text-[10px] font-bold text-slate-500">
                    +Rp {{ number_format($overallKpi['total_margin_idr'], 0, ',', '.') }}
                </span>
            </div>
            <div class="w-10 h-10 rounded-xs bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                <i class="fa-solid fa-chart-line text-base"></i>
            </div>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <div class="p-4 bg-white dark:bg-slate-800 rounded-xs border border-slate-200 dark:border-slate-700 shadow-xs">
        <form method="GET" action="{{ route('management.product-cost-comparison.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
            {{-- Customer Selector --}}
            <div>
                <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Filter Customer</label>
                <select name="customer_id" id="sel-customer" onchange="loadModelsForCustomer(this.value)"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xs text-xs font-semibold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500">
                    <option value="">- All Customers -</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" {{ ($selectedCustomerId == $c->id) ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->code ?? '-' }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Project Model Selector --}}
            <div>
                <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Filter Project Model</label>
                <select name="model_id" id="sel-model"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xs text-xs font-semibold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500">
                    <option value="">- All Models -</option>
                    @foreach($models as $m)
                        <option value="{{ $m->id }}" {{ ($selectedModelId == $m->id) ? 'selected' : '' }}>
                            {{ $m->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit"
                        class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xs shadow-xs transition-colors flex items-center justify-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-filter"></i> Apply Filter
                </button>
                <a href="{{ route('management.product-cost-comparison.index') }}"
                   class="px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xs transition-colors flex items-center justify-center">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>

    {{-- Main Comparison Header DataTable Component --}}
    <x-table id="comparison-header-table" class="w-full text-xs text-left border-collapse">
        <thead>
            <tr class="border-b-2 border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900 text-[11px] font-extrabold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                <th class="px-3 py-3 w-12 text-center border-r border-slate-200 dark:border-slate-700">No.</th>
                <th class="px-4 py-3 border-r border-slate-200 dark:border-slate-700">Customer</th>
                <th class="px-4 py-3 border-r border-slate-200 dark:border-slate-700">Project Model</th>
                <th class="px-3 py-3 text-center border-r border-slate-200 dark:border-slate-700">EBD Rev</th>
                <th class="px-3 py-3 text-center border-r border-slate-200 dark:border-slate-700">BOM Parts</th>
                <th class="px-3 py-3 text-right border-r border-slate-200 dark:border-slate-700 bg-sky-100 dark:bg-sky-950/60 text-sky-950 dark:text-sky-200 font-black">
                    <i class="fa-solid fa-wrench mr-1 text-sky-600 dark:text-sky-400"></i> COGS Eng (HPP)
                </th>
                <th class="px-3 py-3 text-right border-r border-slate-200 dark:border-slate-700 bg-amber-100 dark:bg-amber-950/60 text-amber-950 dark:text-amber-200 font-black">
                    <i class="fa-solid fa-file-invoice-dollar mr-1 text-amber-600 dark:text-amber-400"></i> COGS Sales (Quotation)
                </th>
                <th class="px-3 py-3 text-right border-r border-slate-200 dark:border-slate-700 bg-emerald-100 dark:bg-emerald-950/60 text-emerald-950 dark:text-emerald-200 font-black">
                    Profit Margin
                </th>
                <th class="px-3 py-3 text-center border-r border-slate-200 dark:border-slate-700">Status</th>
                <th class="px-3 py-3 text-center w-28">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($comparisonSummaries as $idx => $row)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/60 transition-colors">
                    <td class="px-3 py-3 text-center font-bold text-slate-400 border-r border-slate-100 dark:border-slate-800">
                        {{ $idx + 1 }}
                    </td>
                    <td class="px-4 py-3 border-r border-slate-100 dark:border-slate-800 font-bold">
                        <span class="inline-block px-2.5 py-1 text-xs font-black rounded-xs bg-indigo-100 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                            {{ $row['customer']->code ?? $row['customer']->name ?? '-' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 border-r border-slate-100 dark:border-slate-800 font-semibold text-slate-700 dark:text-slate-200">
                        {{ $row['model']->name ?? 'Model' }}
                    </td>
                    <td class="px-3 py-3 text-center border-r border-slate-100 dark:border-slate-800">
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-xs bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                            Rev {{ $row['ebd']->revision ?? '0' }}
                        </span>
                        <div class="text-[9px] text-slate-400 mt-0.5">{{ $row['ebd']->date ? $row['ebd']->date->format('d/m/Y') : '-' }}</div>
                    </td>
                    <td class="px-3 py-3 text-center font-bold border-r border-slate-100 dark:border-slate-800 text-slate-700 dark:text-slate-300">
                        {{ $row['parts_count'] }} Parts
                    </td>
                    <td class="px-3 py-3 text-right border-r border-slate-100 dark:border-slate-800 font-bold text-sky-950 dark:text-sky-100 bg-sky-50/80 dark:bg-sky-950/30">
                        Rp {{ number_format($row['cogs_eng'], 0, ',', '.') }}
                    </td>
                    <td class="px-3 py-3 text-right border-r border-slate-100 dark:border-slate-800 font-bold text-amber-950 dark:text-amber-100 bg-amber-50/80 dark:bg-amber-950/30">
                        Rp {{ number_format($row['cogs_sales'], 0, ',', '.') }}
                    </td>
                    <td class="px-3 py-3 text-right border-r border-slate-100 dark:border-slate-800 bg-emerald-50/80 dark:bg-emerald-950/30 font-bold">
                        <div class="text-emerald-700 dark:text-emerald-300 font-black">{{ number_format($row['margin_pct'], 2) }}%</div>
                        <div class="text-[10px] text-emerald-600 dark:text-emerald-400">+Rp {{ number_format($row['margin_idr'], 0, ',', '.') }}</div>
                    </td>
                    <td class="px-3 py-3 text-center border-r border-slate-100 dark:border-slate-800">
                        <span class="px-2 py-1 text-[10px] font-black rounded-xs {{ $row['status_badge'] }}">
                            {{ $row['status'] }}
                        </span>
                    </td>
                    <td class="px-3 py-3 text-center">
                        <a href="{{ route('management.product-cost-comparison.show', $row['ebd']->id) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-bold rounded-xs shadow-xs transition-colors cursor-pointer">
                            <i class="fa-solid fa-chart-column text-[10px]"></i> View Details
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="p-8 text-center text-slate-400">
                        Belum ada dokumen EBD yang tersedia untuk perbandingan biaya.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.defaultDataTable === 'function') {
            window.defaultDataTable('#comparison-header-table', {
                pageLength: 10,
                ordering: true,
                order: [[0, 'asc']]
            });
        }
    });

    function loadModelsForCustomer(customerId) {
        const modelSelect = document.getElementById('sel-model');
        modelSelect.innerHTML = '<option value="">- All Models -</option>';

        if (!customerId) return;

        fetch(`{{ route('management.product-cost-comparison.models') }}?customer_id=${customerId}`)
            .then(res => res.json())
            .then(models => {
                models.forEach(m => {
                    const opt = document.createElement('option');
                    opt.value = m.id;
                    opt.textContent = m.name;
                    modelSelect.appendChild(opt);
                });
            });
    }
</script>
@endsection
