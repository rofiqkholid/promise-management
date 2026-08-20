@extends('layouts.app')

@section('title', 'Product Cost Comparison · Promise Management')

@section('content')
<div class="flex-1 overflow-y-auto p-4 pt-17.5 space-y-5 transition-colors duration-200">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-lg font-bold tracking-tight text-slate-800 dark:text-white">Product Cost Comparison</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">Perbandingan biaya HPP Engineering vs Harga Penawaran Sales per Customer dan Project Model EBD</p>
        </div>
    </div>

    {{-- KPI Summary Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5">
        <div class="p-3 bg-white dark:bg-slate-800 rounded-sm border border-slate-300 dark:border-slate-700 flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Total Projects (EBD)</span>
                <div class="text-lg font-black text-slate-800 dark:text-white mt-0.5">{{ $overallKpi['total_projects'] }} Models</div>
                <span class="text-[10px] text-slate-400">Evaluated BOM Documents</span>
            </div>
            <div class="w-8 h-8 rounded-none bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 flex items-center justify-center font-bold">
                <i class="fa-solid fa-folder-tree text-sm"></i>
            </div>
        </div>

        <div class="p-3 bg-white dark:bg-slate-800 rounded-sm border border-slate-300 dark:border-slate-700 flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">Total COGS (Eng)</span>
                <div class="text-lg font-black text-blue-700 dark:text-blue-300 mt-0.5">
                    Rp {{ number_format($overallKpi['total_cogs_eng'], 0, ',', '.') }}
                </div>
                <span class="text-[10px] text-slate-400">Total Direct Engineering HPP</span>
            </div>
            <div class="w-8 h-8 rounded-none bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold">
                <i class="fa-solid fa-wrench text-sm"></i>
            </div>
        </div>

        <div class="p-3 bg-white dark:bg-slate-800 rounded-sm border border-slate-300 dark:border-slate-700 flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Total COGS (Sales)</span>
                <div class="text-lg font-black text-indigo-700 dark:text-indigo-300 mt-0.5">
                    Rp {{ number_format($overallKpi['total_cogs_sales'], 0, ',', '.') }}
                </div>
                <span class="text-[10px] text-slate-400">Total Quotation Commercial</span>
            </div>
            <div class="w-8 h-8 rounded-none bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                <i class="fa-solid fa-file-invoice-dollar text-sm"></i>
            </div>
        </div>

        <div class="p-3 bg-white dark:bg-slate-800 rounded-sm border border-slate-300 dark:border-slate-700 flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Overall Profit Margin</span>
                <div class="text-lg font-black text-emerald-700 dark:text-emerald-300 mt-0.5">
                    {{ number_format($overallKpi['avg_margin_pct'], 2) }}%
                </div>
                <span class="text-[10px] font-bold text-slate-500">
                    +Rp {{ number_format($overallKpi['total_margin_idr'], 0, ',', '.') }}
                </span>
            </div>
            <div class="w-8 h-8 rounded-none bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                <i class="fa-solid fa-chart-line text-sm"></i>
            </div>
        </div>
    </div>

    {{-- Main Comparison Header DataTable Component --}}
    <x-table id="comparison-header-table" class="w-full text-xs text-left border-collapse">
        <x-slot:filters>
            <form id="comparison-filter-form" method="GET" action="{{ route('management.product-cost-comparison.index') }}" class="space-y-3">
                {{-- Customer Selector --}}
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Customer</label>
                    <select name="customer_id" id="sel-customer" onchange="loadModelsForCustomer(this.value)"
                        class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm text-xs font-semibold text-slate-700 dark:text-slate-200 focus:outline-none focus:border-blue-500">
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
                    <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Project Model</label>
                    <select name="model_id" id="sel-model"
                        class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm text-xs font-semibold text-slate-700 dark:text-slate-200 focus:outline-none focus:border-blue-500">
                        <option value="">- All Models -</option>
                        @foreach($models as $m)
                            <option value="{{ $m->id }}" {{ ($selectedModelId == $m->id) ? 'selected' : '' }}>
                                {{ $m->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </x-slot:filters>

        <thead>
            <tr class="border-b-2 border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900 text-[10px] font-extrabold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                <th class="px-3 py-2.5 w-12 text-center border-r border-slate-200 dark:border-slate-700">No.</th>
                <th class="px-4 py-2.5 border-r border-slate-200 dark:border-slate-700">Customer</th>
                <th class="px-4 py-2.5 border-r border-slate-200 dark:border-slate-700">Project Model</th>
                <th class="px-3 py-2.5 text-center border-r border-slate-200 dark:border-slate-700">EBD Rev</th>
                <th class="px-3 py-2.5 text-center border-r border-slate-200 dark:border-slate-700">BOM Parts</th>
                <th class="px-3 py-2.5 text-right border-r border-slate-200 dark:border-slate-700">
                    COGS Eng (HPP)
                </th>
                <th class="px-3 py-2.5 text-right border-r border-slate-200 dark:border-slate-700 font-extrabold text-slate-800 dark:text-slate-100">
                    COGS Sales (Quotation)
                </th>
                <th class="px-3 py-2.5 text-right border-r border-slate-200 dark:border-slate-700">
                    Profit Margin
                </th>
                <th class="px-3 py-2.5 text-center border-r border-slate-200 dark:border-slate-700">Status</th>
                <th class="px-3 py-2.5 text-center w-14">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($comparisonSummaries as $idx => $row)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/60 transition-colors">
                    <td class="px-3 py-2.5 text-center font-bold text-slate-400 border-r border-slate-100 dark:border-slate-800">
                        {{ $idx + 1 }}
                    </td>
                    <td class="px-4 py-2.5 border-r border-slate-100 dark:border-slate-800 font-bold">
                        <span class="inline-block px-2 py-0.5 text-xs font-bold rounded-sm bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-200">
                            {{ $row['customer']->code ?? $row['customer']->name ?? '-' }}
                        </span>
                    </td>
                    <td class="px-4 py-2.5 border-r border-slate-100 dark:border-slate-800 font-semibold text-slate-800 dark:text-slate-200">
                        {{ $row['model']->name ?? 'Model' }}
                    </td>
                    <td class="px-3 py-2.5 text-center border-r border-slate-100 dark:border-slate-800">
                        <span class="px-1.5 py-0.5 text-[10px] font-bold rounded-sm bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                            Rev {{ $row['ebd']->revision ?? '0' }}
                        </span>
                        <div class="text-[9px] text-slate-400 mt-0.5">{{ $row['ebd']->date ? $row['ebd']->date->format('d/m/Y') : '-' }}</div>
                    </td>
                    <td class="px-3 py-2.5 text-center font-bold border-r border-slate-100 dark:border-slate-800 text-slate-700 dark:text-slate-300">
                        {{ $row['parts_count'] }} Parts
                    </td>
                    <td class="px-3 py-2.5 text-right border-r border-slate-100 dark:border-slate-800 font-medium text-slate-800 dark:text-slate-200">
                        Rp {{ number_format($row['cogs_eng'], 0, ',', '.') }}
                    </td>
                    <td class="px-3 py-2.5 text-right border-r border-slate-100 dark:border-slate-800 font-bold text-slate-900 dark:text-white">
                        Rp {{ number_format($row['cogs_sales'], 0, ',', '.') }}
                    </td>
                    <td class="px-3 py-2.5 text-right border-r border-slate-100 dark:border-slate-800 font-bold">
                        @if($row['margin_idr'] >= 0)
                            <div class="text-emerald-600 dark:text-emerald-400 font-black">+{{ number_format($row['margin_pct'], 2) }}%</div>
                            <div class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold">+Rp {{ number_format($row['margin_idr'], 0, ',', '.') }}</div>
                        @else
                            <div class="text-rose-600 dark:text-rose-400 font-black">{{ number_format($row['margin_pct'], 2) }}%</div>
                            <div class="text-[10px] text-rose-600 dark:text-rose-400 font-bold">Rp {{ number_format($row['margin_idr'], 0, ',', '.') }}</div>
                        @endif
                    </td>
                    <td class="px-3 py-2.5 text-center border-r border-slate-100 dark:border-slate-800">
                        <span class="px-2 py-0.5 text-[10px] font-black rounded-sm {{ $row['status_badge'] }}">
                            {{ $row['status'] }}
                        </span>
                    </td>
                    <td class="px-3 py-2.5 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <button type="button"
                                    onclick="openIndexQuotationModal('{{ route('management.product-cost-comparison.quotation', $row['ebd']->id) }}', '{{ addslashes($row['customer']->name ?? 'Customer') }} ({{ $row['customer']->code ?? '—' }})', '{{ addslashes($row['model']->name ?? 'Model') }}', 'Rev {{ $row['ebd']->revision ?? '0' }}', '{{ $row['customer']->id ?? '' }}')"
                                    class="inline-flex items-center justify-center w-7 h-7 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:hover:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 rounded-sm border border-emerald-300 dark:border-emerald-800 transition-colors cursor-pointer"
                                    title="Export Quotation Excel">
                                <i class="fa-solid fa-file-excel text-xs"></i>
                            </button>
                            <a href="{{ route('management.product-cost-comparison.show', $row['ebd']->id) }}"
                               class="inline-flex items-center justify-center w-7 h-7 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-sm border border-slate-300 dark:border-slate-600 transition-colors"
                               title="Lihat Detail Matrix">
                                <i class="fa-solid fa-arrow-right text-xs"></i>
                            </a>
                        </div>
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

{{-- ===== DYNAMIC EXPORT MODAL ===== --}}
<div id="exportIndexQuotationModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-sm shadow-2xl w-full max-w-md mx-4 overflow-hidden animate-fade-in">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-700">
            <div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-file-excel text-emerald-600 dark:text-emerald-400"></i> Export Customer Quotation
                </h3>
                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Generate customized quotation Excel spreadsheet</p>
            </div>
            <button type="button" onclick="$('#exportIndexQuotationModal').addClass('hidden').removeClass('flex')" class="w-7 h-7 flex items-center justify-center rounded-sm text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <form id="exportIndexQuotationForm" action="" method="GET" target="_blank">
            <div class="px-5 py-4 space-y-4">
                {{-- Target Info Box --}}
                <div class="p-3 bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 rounded-sm space-y-1">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-500">Customer:</span>
                        <span id="modal-lbl-customer" class="font-bold text-slate-800 dark:text-slate-100">—</span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-500">Project Model:</span>
                        <span id="modal-lbl-model" class="font-bold text-slate-800 dark:text-slate-100">—</span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-500">EBD Revision:</span>
                        <span id="modal-lbl-revision" class="font-bold font-mono text-slate-800 dark:text-slate-100">—</span>
                    </div>
                </div>

                {{-- Template Selection --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">
                        Select Output Template <span class="text-rose-500">*</span>
                    </label>
                    <select name="template_id" id="modal_template_select" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-xs font-medium text-slate-800 dark:text-slate-100 rounded-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        <option value="">-- Standard System Template (Default Format) --</option>
                        @foreach($exportTemplates ?? [] as $tpl)
                            <option value="{{ $tpl->id }}" data-customer-id="{{ $tpl->customer_id ?? '' }}">
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
            </div>

            <div class="flex items-center justify-end gap-2 px-5 py-3.5 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-200 dark:border-slate-700">
                <button type="button" onclick="$('#exportIndexQuotationModal').addClass('hidden').removeClass('flex')" class="px-3.5 h-8 text-xs font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-sm transition-colors cursor-pointer">
                    Cancel
                </button>
                <button type="submit" onclick="setTimeout(function(){ $('#exportIndexQuotationModal').addClass('hidden').removeClass('flex'); }, 500)" class="inline-flex items-center justify-center gap-1.5 px-4 h-8 bg-emerald-600 hover:bg-emerald-700 text-white rounded-sm text-xs font-semibold shadow-xs active:scale-98 transition-all cursor-pointer">
                    <i class="fa-solid fa-file-excel text-xs"></i>
                    <span>Generate & Download Excel</span>
                </button>
            </div>
        </form>
    </div>
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

    function openIndexQuotationModal(actionUrl, customerName, modelName, revision, customerId) {
        $('#exportIndexQuotationForm').attr('action', actionUrl);
        $('#modal-lbl-customer').text(customerName);
        $('#modal-lbl-model').text(modelName);
        $('#modal-lbl-revision').text(revision);

        // Auto select template matching customer if available
        let matched = false;
        if (customerId) {
            $('#modal_template_select option').each(function() {
                if ($(this).data('customer-id') == customerId) {
                    $(this).prop('selected', true);
                    matched = true;
                    return false;
                }
            });
        }
        if (!matched) {
            $('#modal_template_select').val('');
        }

        $('#exportIndexQuotationModal').removeClass('hidden').addClass('flex');
    }

    function loadModelsForCustomer(customerId) {
        const modelSelect = document.getElementById('sel-model');
        if (!modelSelect) return;
        modelSelect.innerHTML = '<option value="">- All Models -</option>';

        const url = customerId 
            ? `{{ route('management.product-cost-comparison.models') }}?customer_id=${customerId}`
            : `{{ route('management.product-cost-comparison.models') }}`;

        fetch(url)
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
