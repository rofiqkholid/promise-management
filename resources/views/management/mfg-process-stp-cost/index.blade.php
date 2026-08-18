@extends('layouts.app')

@section('title', 'Master Data Stamping Process Cost · Promise Management')

@section('content')
<x-sweetalert />
<div class="flex-1 overflow-y-auto p-4 pt-17.5 space-y-5 transition-colors duration-200">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none mb-1">
                Master Data Configuration
            </div>
            <h1 class="text-xl font-extrabold tracking-tight text-slate-800 dark:text-white leading-none flex items-center gap-2">
                <i class="fa-solid fa-stamp text-blue-600 dark:text-blue-400 text-lg"></i>
                Stamping Process Cost Rate
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Manage master stamping process cost rates based on machine type, tonnage, output, and process complexity.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('management.mfg-process-stp-cost.export') }}"
               class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xs shadow-xs transition-colors">
                <i class="fa-solid fa-file-excel text-[11px]"></i> Export CSV
            </a>

            <button onclick="openAddModal()"
                    class="inline-flex items-center gap-2 px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xs shadow-xs transition-colors cursor-pointer">
                <i class="fa-solid fa-plus text-[10px]"></i> Add Stamping Rate
            </button>
        </div>
    </div>

    {{-- Error Banner --}}
    @if($errors->any())
        <div class="p-3.5 bg-rose-50 dark:bg-rose-950/30 border-l-4 border-rose-500 text-rose-700 dark:text-rose-400 text-xs rounded-r-xs">
            <div class="font-bold mb-1"><i class="fa-solid fa-triangle-exclamation mr-1.5"></i> Please correct the following errors:</div>
            <ul class="list-disc pl-4 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="p-4 bg-white dark:bg-slate-800 rounded-xs border border-slate-200 dark:border-slate-700 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Total Stamping Rates</span>
                <div class="text-xl font-black text-slate-800 dark:text-white mt-0.5" id="stat-total">{{ $totalItems }}</div>
            </div>
            <div class="w-10 h-10 rounded-xs bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold">
                <i class="fa-solid fa-stamp text-base"></i>
            </div>
        </div>

        <div class="p-4 bg-white dark:bg-slate-800 rounded-xs border border-slate-200 dark:border-slate-700 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Small Machine</span>
                <div class="text-xl font-black text-slate-800 dark:text-white mt-0.5" id="stat-small">{{ $smallCount }}</div>
            </div>
            <div class="w-10 h-10 rounded-xs bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                <i class="fa-solid fa-gears text-base"></i>
            </div>
        </div>

        <div class="p-4 bg-white dark:bg-slate-800 rounded-xs border border-slate-200 dark:border-slate-700 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Medium Machine</span>
                <div class="text-xl font-black text-slate-800 dark:text-white mt-0.5" id="stat-medium">{{ $mediumCount }}</div>
            </div>
            <div class="w-10 h-10 rounded-xs bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold">
                <i class="fa-solid fa-industry text-base"></i>
            </div>
        </div>

        <div class="p-4 bg-white dark:bg-slate-800 rounded-xs border border-slate-200 dark:border-slate-700 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Large Machine</span>
                <div class="text-xl font-black text-slate-800 dark:text-white mt-0.5" id="stat-large">{{ $largeCount }}</div>
            </div>
            <div class="w-10 h-10 rounded-xs bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 flex items-center justify-center font-bold">
                <i class="fa-solid fa-warehouse text-base"></i>
            </div>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <div class="p-4 bg-white dark:bg-slate-800 rounded-xs border border-slate-200 dark:border-slate-700 shadow-xs space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                {{-- Machine Type Filter --}}
                <div class="w-full sm:w-40">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Machine Type</label>
                    <select id="filter-type" class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xs text-xs font-semibold text-slate-700 dark:text-slate-200">
                        <option value="">All Types</option>
                        @foreach($machineTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Machine Category Filter --}}
                <div class="w-full sm:w-40">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Category</label>
                    <select id="filter-category" class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xs text-xs font-semibold text-slate-700 dark:text-slate-200">
                        <option value="">All Categories</option>
                        @foreach($machineCategories as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Output Type Filter --}}
                <div class="w-full sm:w-40">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Output Type</label>
                    <select id="filter-output" class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xs text-xs font-semibold text-slate-700 dark:text-slate-200">
                        <option value="">All Outputs</option>
                        @foreach($outputTypes as $out)
                            <option value="{{ $out }}">{{ $out }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Rate Source Filter --}}
                <div class="w-full sm:w-40">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Rate Source</label>
                    <select id="filter-source" class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xs text-xs font-semibold text-slate-700 dark:text-slate-200">
                        <option value="">All Sources</option>
                        <option value="Sales">Sales</option>
                        <option value="Engineering">Engineering</option>
                    </select>
                </div>
            </div>

            <div class="flex items-end gap-2">
                <button type="button" id="btn-reset-filters" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xs transition-colors cursor-pointer">
                    <i class="fa-solid fa-rotate-left mr-1"></i> Reset
                </button>
            </div>
        </div>
    </div>

    {{-- Main DataTable --}}
    <x-table id="stp-cost-table" class="w-full text-xs text-left border-collapse">
        <thead>
            <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900/60 text-[11px] font-extrabold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                <th rowspan="2" class="px-3 py-3 w-12 text-center border-r border-slate-200 dark:border-slate-700">No.</th>
                <th colspan="3" class="px-4 py-2 text-center border-r border-slate-200 dark:border-slate-700 bg-slate-200/50 dark:bg-slate-800/50">
                    Machine Identity
                </th>
                <th colspan="3" class="px-4 py-2 text-center border-r border-slate-200 dark:border-slate-700 bg-indigo-50/50 dark:bg-indigo-950/30 text-indigo-900 dark:text-indigo-300">
                    Output & Setup
                </th>
                <th rowspan="2" class="px-4 py-3 border-r border-slate-200 dark:border-slate-700">Complexity (Rank)</th>
                <th colspan="2" class="px-4 py-2 text-center border-r border-slate-200 dark:border-slate-700 bg-blue-50/70 dark:bg-blue-950/40 text-blue-800 dark:text-blue-300">
                    Stamping Cost Rate
                </th>
                <th rowspan="2" class="px-4 py-3 border-r border-slate-200 dark:border-slate-700">Rate Source</th>
                <th rowspan="2" class="px-3 py-3 text-center w-24">Actions</th>
            </tr>
            <tr class="border-b-2 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase">
                <th class="px-3 py-2 border-r border-slate-200 dark:border-slate-700">Machine Type</th>
                <th class="px-3 py-2 text-right border-r border-slate-200 dark:border-slate-700">Tonnage</th>
                <th class="px-3 py-2 border-r border-slate-200 dark:border-slate-700">Category</th>
                <th class="px-3 py-2 border-r border-slate-200 dark:border-slate-700 bg-indigo-50/30 dark:bg-indigo-950/10">Output Type</th>
                <th class="px-3 py-2 text-center border-r border-slate-200 dark:border-slate-700 bg-indigo-50/30 dark:bg-indigo-950/10">Output Qty</th>
                <th class="px-3 py-2 text-right border-r border-slate-200 dark:border-slate-700 bg-indigo-50/30 dark:bg-indigo-950/10">Stroke</th>
                <th class="px-3 py-2 text-right border-r border-slate-200 dark:border-slate-700 bg-blue-50/40 dark:bg-blue-950/20">Min</th>
                <th class="px-3 py-2 text-right border-r border-slate-200 dark:border-slate-700 bg-blue-50/40 dark:bg-blue-950/20">Std <span class="text-rose-500">*</span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
        </tbody>
    </x-table>

</div>

{{-- ── ADD MODAL ────────────────────────────────────────────────── --}}
<div id="modal-add" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs">
    <div class="bg-white dark:bg-slate-800 rounded-xs shadow-2xl w-full max-w-lg border border-slate-200 dark:border-slate-700 overflow-hidden my-6 max-h-[90vh] flex flex-col">
        <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-900/50">
            <h3 class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-plus-circle text-blue-600"></i> Add Stamping Process Cost Rate
            </h3>
            <button onclick="closeAddModal()" class="text-slate-400 hover:text-slate-600 text-lg leading-none cursor-pointer">&times;</button>
        </div>
        <form action="{{ route('management.mfg-process-stp-cost.store') }}" method="POST" class="p-5 overflow-y-auto space-y-4">
            @include('management.mfg-process-stp-cost._form', ['item' => null])
            <div class="flex justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-700">
                <button type="button" onclick="closeAddModal()"
                        class="px-4 py-2 text-xs font-bold border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-xs transition-colors">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xs shadow-xs transition-colors cursor-pointer flex items-center gap-1.5">
                    <i class="fa-solid fa-floppy-disk"></i> Save Master Data
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── EDIT MODAL ───────────────────────────────────────────────── --}}
<div id="modal-edit" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs">
    <div class="bg-white dark:bg-slate-800 rounded-xs shadow-2xl w-full max-w-lg border border-slate-200 dark:border-slate-700 overflow-hidden my-6 max-h-[90vh] flex flex-col">
        <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-900/50">
            <h3 class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-amber-500"></i> Edit Stamping Process Cost Rate
            </h3>
            <button onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600 text-lg leading-none cursor-pointer">&times;</button>
        </div>
        <form id="edit-form" action="" method="POST" class="p-5 overflow-y-auto space-y-4">
            @csrf
            @method('PUT')
            <div id="edit-form-content">
                {{-- Loaded via JavaScript --}}
            </div>
            <div class="flex justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-700">
                <button type="button" onclick="closeEditModal()"
                        class="px-4 py-2 text-xs font-bold border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-xs transition-colors">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white rounded-xs shadow-xs transition-colors cursor-pointer flex items-center gap-1.5">
                    <i class="fa-solid fa-check"></i> Update Master Data
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dataTable = window.defaultDataTable('#stp-cost-table', {
            serverSide: true,
            processing: true,
            ordering: true,
            order: [[2, 'asc']],
            ajax: {
                url: '{{ route("management.mfg-process-stp-cost.data") }}',
                data: function(d) {
                    d.machine_type = $('#filter-type').val();
                    d.machine_category = $('#filter-category').val();
                    d.output_type = $('#filter-output').val();
                    d.rate_source = $('#filter-source').val();
                }
            },
            columns: [
                {
                    data: null,
                    orderable: false,
                    className: 'text-center font-bold text-slate-500',
                    render: function (data, type, row, meta) {
                        return meta.row + 1 + meta.settings._iDisplayStart;
                    }
                },
                { data: 'machine_type', orderable: true, className: 'font-bold text-slate-900 dark:text-white' },
                {
                    data: 'tonnage',
                    orderable: true,
                    className: 'text-right font-extrabold text-slate-800 dark:text-slate-100',
                    render: function(data) {
                        return `${data} T`;
                    }
                },
                {
                    data: 'machine_category',
                    orderable: true,
                    className: 'font-semibold',
                    render: function (data) {
                        if (data === 'Small') {
                            return `<span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">Small</span>`;
                        } else if (data === 'Medium') {
                            return `<span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">Medium</span>`;
                        } else if (data === 'Large') {
                            return `<span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">Large</span>`;
                        }
                        return data || '-';
                    }
                },
                { data: 'output_type', orderable: true, className: 'font-medium text-slate-700 dark:text-slate-300' },
                { data: 'output_qty', orderable: true, className: 'text-center font-semibold text-slate-800 dark:text-slate-200' },
                { data: 'stroke', orderable: true, className: 'text-right font-medium text-slate-700 dark:text-slate-300' },
                {
                    data: 'process_complexity',
                    orderable: true,
                    className: 'font-semibold text-slate-800 dark:text-slate-200',
                    render: function (data, type, row) {
                        if (!data) return '-';
                        const alias = row.complexity_alias ? ` (${row.complexity_alias})` : '';
                        return `<span class="font-bold text-slate-800 dark:text-slate-100">${data}${alias}</span>`;
                    }
                },
                {
                    data: 'min_cost_rate',
                    orderable: true,
                    className: 'text-right font-semibold text-slate-700 dark:text-slate-300 bg-blue-50/30 dark:bg-blue-950/10',
                    render: function(data) {
                        if (data === null || data === undefined || data === '') return '-';
                        return parseFloat(data).toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 2 });
                    }
                },
                {
                    data: 'std_cost_rate',
                    orderable: true,
                    className: 'text-right font-bold text-slate-900 dark:text-white bg-blue-50/30 dark:bg-blue-950/10',
                    render: function(data) {
                        if (data === null || data === undefined || data === '') return '0.0';
                        return parseFloat(data).toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 2 });
                    }
                },
                {
                    data: 'rate_source',
                    orderable: true,
                    className: 'font-semibold text-slate-700 dark:text-slate-300',
                    render: function(data) {
                        if (data === 'Engineering') {
                            return `<span class="px-2 py-0.5 text-[10px] font-bold rounded-xs bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Engineering</span>`;
                        } else if (data === 'Sales') {
                            return `<span class="px-2 py-0.5 text-[10px] font-bold rounded-xs bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300">Sales</span>`;
                        }
                        return `<span class="px-2 py-0.5 text-[10px] font-bold rounded-xs bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">${data}</span>`;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    className: 'text-center',
                    render: function (data, type, row) {
                        const rowJson = JSON.stringify(row).replace(/'/g, "&apos;").replace(/"/g, "&quot;");
                        return `
                            <div class="flex items-center justify-center gap-1.5">
                                <button type="button" onclick='openEditModal(${rowJson})'
                                        class="p-1.5 text-amber-600 hover:text-amber-800 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/40 rounded-xs transition-colors cursor-pointer" title="Edit Data">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button type="button" onclick="deleteItem(${row.id})"
                                        class="p-1.5 text-rose-600 hover:text-rose-800 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-xs transition-colors cursor-pointer" title="Delete Data">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        `;
                    }
                }
            ]
        });

        $('#filter-type, #filter-category, #filter-output, #filter-source').on('change', function() {
            dataTable.ajax.reload();
        });

        $('#btn-reset-filters').on('click', function() {
            $('#filter-type').val('');
            $('#filter-category').val('');
            $('#filter-output').val('');
            $('#filter-source').val('');
            dataTable.ajax.reload();
        });
    });

    function openAddModal() {
        document.getElementById('modal-add').classList.remove('hidden');
    }

    function closeAddModal() {
        document.getElementById('modal-add').classList.add('hidden');
    }

    function openEditModal(item) {
        const editForm = document.getElementById('edit-form');
        editForm.action = `{{ url('management/mfg-process-stp-cost') }}/${item.id}`;

        const editFormContent = document.getElementById('edit-form-content');
        editFormContent.innerHTML = `
            @csrf
            @method('PUT')
            <div class="space-y-4 text-xs">
                <div class="p-3 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xs space-y-3">
                    <span class="block font-extrabold text-[11px] uppercase tracking-wider text-slate-600 dark:text-slate-400 flex items-center gap-1.5">
                        <i class="fa-solid fa-gears text-blue-500"></i> Machine Identity
                    </span>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Machine Type <span class="text-rose-500">*</span></label>
                            <input type="text" name="machine_type" value="${item.machine_type || ''}" required class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xs font-medium">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Tonnage (Tons) <span class="text-rose-500">*</span></label>
                            <input type="number" name="tonnage" value="${item.tonnage || ''}" required class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xs font-semibold">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Machine Category</label>
                            <select name="machine_category" class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xs font-medium">
                                <option value="">- Select Category -</option>
                                <option value="Small" ${item.machine_category === 'Small' ? 'selected' : ''}>Small</option>
                                <option value="Medium" ${item.machine_category === 'Medium' ? 'selected' : ''}>Medium</option>
                                <option value="Large" ${item.machine_category === 'Large' ? 'selected' : ''}>Large</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="p-3 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xs space-y-3">
                    <span class="block font-extrabold text-[11px] uppercase tracking-wider text-slate-600 dark:text-slate-400 flex items-center gap-1.5">
                        <i class="fa-solid fa-sliders text-indigo-500"></i> Output & Setup Configuration
                    </span>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Output Type <span class="text-rose-500">*</span></label>
                            <select name="output_type" required class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xs font-medium">
                                <option value="Part" ${item.output_type === 'Part' ? 'selected' : ''}>Part</option>
                                <option value="Cavity" ${item.output_type === 'Cavity' ? 'selected' : ''}>Cavity</option>
                                <option value="Process" ${item.output_type === 'Process' ? 'selected' : ''}>Process</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Output Qty <span class="text-rose-500">*</span></label>
                            <input type="number" name="output_qty" value="${item.output_qty || 1}" required min="1" class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xs font-semibold">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Stroke <span class="text-rose-500">*</span></label>
                            <input type="number" step="0.01" name="stroke" value="${item.stroke || 1.00}" required min="0.01" class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xs font-semibold">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Process Complexity <span class="text-rose-500">*</span></label>
                        <input type="text" name="process_complexity" value="${item.process_complexity || ''}" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xs font-medium">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Complexity Alias (Part Rank)</label>
                        <input type="text" name="complexity_alias" value="${item.complexity_alias || ''}" placeholder="e.g. A, B, C, D" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xs font-medium">
                    </div>
                </div>

                <div class="p-3 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xs space-y-3">
                    <span class="block font-extrabold text-[11px] uppercase tracking-wider text-slate-600 dark:text-slate-400 flex items-center gap-1.5">
                        <i class="fa-solid fa-coins text-emerald-500"></i> Stamping Cost Rates
                    </span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Min Cost Rate <span class="text-slate-400 font-normal">(Optional)</span></label>
                            <input type="number" step="0.01" name="min_cost_rate" value="${item.min_cost_rate !== null ? item.min_cost_rate : ''}" class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xs font-semibold">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Std Cost Rate <span class="text-rose-500">*</span></label>
                            <input type="number" step="0.01" name="std_cost_rate" value="${item.std_cost_rate !== null ? item.std_cost_rate : ''}" required class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xs font-semibold">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Rate Source <span class="text-rose-500">*</span></label>
                    <select name="rate_source" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xs font-medium">
                        <option value="Sales" ${item.rate_source === 'Sales' ? 'selected' : ''}>Sales</option>
                        <option value="Engineering" ${item.rate_source === 'Engineering' ? 'selected' : ''}>Engineering</option>
                    </select>
                </div>
            </div>
        `;

        document.getElementById('modal-edit').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('modal-edit').classList.add('hidden');
    }

    function deleteItem(id) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Are you sure?',
                text: "This Stamping Process Cost Rate will be deleted from the system.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Delete Record!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitDelete(id);
                }
            });
        } else {
            if (confirm('Are you sure you want to delete this record?')) {
                submitDelete(id);
            }
        }
    }

    function submitDelete(id) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `{{ url('management/mfg-process-stp-cost') }}/${id}`;

        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = '{{ csrf_token() }}';
        form.appendChild(csrf);

        const method = document.createElement('input');
        method.type = 'hidden';
        method.name = '_method';
        method.value = 'DELETE';
        form.appendChild(method);

        document.body.appendChild(form);
        form.submit();
    }
</script>
@endsection
