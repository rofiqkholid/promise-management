@extends('layouts.app')

@section('title', 'Master Data Manufacturing Process Cost · Promise Management')

@section('content')
<x-sweetalert />
<div class="flex-1 overflow-y-auto p-4 pt-17.5 space-y-5 transition-colors duration-200">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-lg font-bold tracking-tight text-slate-800 dark:text-white">Manufacturing Process Cost</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Manage master manufacturing process cost rates (Category, Group, Control Point, UOM, Min/Std Rate & Rate Source).
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('management.mfg-process-cost.export') }}"
               class="inline-flex items-center gap-2 px-3.5 h-9 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-sm transition-colors">
                <i class="fa-solid fa-file-excel text-xs"></i> Export CSV
            </a>

            <button onclick="openAddModal()"
                    class="inline-flex items-center gap-2 px-3.5 h-9 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-sm transition-colors cursor-pointer">
                <i class="fa-solid fa-plus text-xs"></i> Add Process Cost
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

    {{-- KPI Summary Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5">
        <div class="p-3 bg-white dark:bg-slate-800 rounded-sm border border-slate-300 dark:border-slate-700 flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Total Process Cost</span>
                <div class="text-lg font-black text-slate-800 dark:text-white mt-0.5" id="stat-total">{{ $totalItems }}</div>
            </div>
            <div class="w-8 h-8 rounded-none bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold">
                <i class="fa-solid fa-list-check text-sm"></i>
            </div>
        </div>

        <div class="p-3 bg-white dark:bg-slate-800 rounded-sm border border-slate-300 dark:border-slate-700 flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Product Category</span>
                <div class="text-lg font-black text-slate-800 dark:text-white mt-0.5" id="stat-product">{{ $productCount }}</div>
            </div>
            <div class="w-8 h-8 rounded-none bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                <i class="fa-solid fa-box text-sm"></i>
            </div>
        </div>

        <div class="p-3 bg-white dark:bg-slate-800 rounded-sm border border-slate-300 dark:border-slate-700 flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Tooling Category</span>
                <div class="text-lg font-black text-slate-800 dark:text-white mt-0.5" id="stat-tooling">{{ $toolingCount }}</div>
            </div>
            <div class="w-8 h-8 rounded-none bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold">
                <i class="fa-solid fa-screws-tilting text-sm"></i>
            </div>
        </div>

        <div class="p-3 bg-white dark:bg-slate-800 rounded-sm border border-slate-300 dark:border-slate-700 flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Process Groups</span>
                <div class="text-lg font-black text-slate-800 dark:text-white mt-0.5" id="stat-groups">{{ $groupCount }}</div>
            </div>
            <div class="w-8 h-8 rounded-none bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                <i class="fa-solid fa-layer-group text-sm"></i>
            </div>
        </div>
    </div>

    {{-- Main DataTable with Filter Popover --}}
    <x-table id="mfg-cost-table" class="w-full text-xs text-left border-collapse">
        <x-slot:filters>
            <div class="space-y-3">
                {{-- Category Filter --}}
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Category</label>
                    <select id="filter-category" class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm text-xs font-semibold text-slate-700 dark:text-slate-200 focus:outline-none focus:border-blue-500">
                        <option value="">All Categories</option>
                        <option value="Product">Product</option>
                        <option value="Tooling">Tooling</option>
                    </select>
                </div>

                {{-- Group Mfg Process Filter --}}
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Process Group</label>
                    <select id="filter-group" class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm text-xs font-semibold text-slate-700 dark:text-slate-200 focus:outline-none focus:border-blue-500">
                        <option value="">All Groups</option>
                        @foreach($processGroups as $group)
                            <option value="{{ $group }}">{{ $group }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Rate Source Filter --}}
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Rate Source</label>
                    <select id="filter-source" class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm text-xs font-semibold text-slate-700 dark:text-slate-200 focus:outline-none focus:border-blue-500">
                        <option value="">All Sources</option>
                        <option value="Sales">Sales</option>
                        <option value="Engineering">Engineering</option>
                    </select>
                </div>
            </div>
        </x-slot:filters>
        <thead>
            {{-- Header Row 1 (Group Headers) --}}
            <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900 text-[10px] font-extrabold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                <th rowspan="2" class="px-3 py-2.5 w-12 text-center border-r border-slate-200 dark:border-slate-700">No.</th>
                <th rowspan="2" class="px-4 py-2.5 border-r border-slate-200 dark:border-slate-700">Category</th>
                <th rowspan="2" class="px-4 py-2.5 border-r border-slate-200 dark:border-slate-700">Group Mfg Process</th>
                <th rowspan="2" class="px-4 py-2.5 border-r border-slate-200 dark:border-slate-700">Mfg Process Name</th>
                <th rowspan="2" class="px-4 py-2.5 border-r border-slate-200 dark:border-slate-700">Control Point</th>
                <th rowspan="2" class="px-3 py-2.5 text-center border-r border-slate-200 dark:border-slate-700">UOM</th>
                <th colspan="3" class="px-4 py-2 text-center border-r border-slate-200 dark:border-slate-700">
                    SAI Cost Rate ( Eng COGM )
                </th>
                <th rowspan="2" class="px-4 py-2.5 border-r border-slate-200 dark:border-slate-700">Rate Source</th>
                <th rowspan="2" class="px-3 py-2.5 text-center w-20">Actions</th>
            </tr>
            {{-- Header Row 2 (Sub Headers for SAI Cost Rate) --}}
            <tr class="border-b-2 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/60 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase">
                <th class="px-3 py-1.5 text-center border-r border-slate-200 dark:border-slate-700">Idr / Units</th>
                <th class="px-3 py-1.5 text-right border-r border-slate-200 dark:border-slate-700">Min</th>
                <th class="px-3 py-1.5 text-right border-r border-slate-200 dark:border-slate-700 font-extrabold text-slate-800 dark:text-slate-100">Std <span class="text-rose-500">*</span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
        </tbody>
    </x-table>

</div>

{{-- ── ADD MODAL ────────────────────────────────────────────────── --}}
<div id="modal-add" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs">
    <div class="bg-white dark:bg-slate-800 rounded-sm shadow-2xl w-full max-w-lg border border-slate-200 dark:border-slate-700 overflow-hidden my-6 max-h-[90vh] flex flex-col">
        <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-900/50">
            <h3 class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-plus-circle text-blue-600"></i> Add Manufacturing Process Cost
            </h3>
            <button onclick="closeAddModal()" class="text-slate-400 hover:text-slate-600 text-lg leading-none cursor-pointer">&times;</button>
        </div>
        <form action="{{ route('management.mfg-process-cost.store') }}" method="POST" class="p-5 overflow-y-auto space-y-4">
            @include('management.mfg-process-cost._form', ['item' => null])
            <div class="flex justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-700">
                <button type="button" onclick="closeAddModal()"
                        class="px-4 py-2 text-xs font-bold border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-sm transition-colors">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-sm shadow-xs transition-colors cursor-pointer flex items-center gap-1.5">
                    <i class="fa-solid fa-floppy-disk"></i> Save Master Data
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── EDIT MODAL ───────────────────────────────────────────────── --}}
<div id="modal-edit" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs">
    <div class="bg-white dark:bg-slate-800 rounded-sm shadow-2xl w-full max-w-lg border border-slate-200 dark:border-slate-700 overflow-hidden my-6 max-h-[90vh] flex flex-col">
        <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-900/50">
            <h3 class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-amber-500"></i> Edit Manufacturing Process Cost
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
                        class="px-4 py-2 text-xs font-bold border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-sm transition-colors">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white rounded-sm shadow-xs transition-colors cursor-pointer flex items-center gap-1.5">
                    <i class="fa-solid fa-check"></i> Update Master Data
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dataTable = window.defaultDataTable('#mfg-cost-table', {
            serverSide: true,
            processing: true,
            ordering: true,
            order: [[1, 'asc']],
            ajax: {
                url: '{{ route("management.mfg-process-cost.data") }}',
                data: function(d) {
                    d.category = $('#filter-category').val();
                    d.process_group = $('#filter-group').val();
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
                {
                    data: 'category',
                    orderable: true,
                    className: 'font-semibold',
                    render: function (data) {
                        if (data === 'Product') {
                            return `<span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">Product</span>`;
                        } else if (data === 'Tooling') {
                            return `<span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">Tooling</span>`;
                        }
                        return data;
                    }
                },
                { data: 'process_group', orderable: true, className: 'font-semibold text-slate-800 dark:text-slate-200' },
                { data: 'process_name', orderable: true, className: 'font-bold text-slate-900 dark:text-white' },
                { data: 'control_point', orderable: true, className: 'text-slate-600 dark:text-slate-400', defaultContent: '-' },
                { data: 'uom', orderable: true, className: 'text-center text-slate-600 dark:text-slate-400', defaultContent: '-' },
                { data: 'rate_unit', orderable: true, className: 'text-center font-medium text-slate-700 dark:text-slate-300 bg-blue-50/30 dark:bg-blue-950/10', defaultContent: '-' },
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
                    className: 'text-right font-bold text-slate-800 dark:text-slate-100 bg-blue-50/30 dark:bg-blue-950/10',
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
                            return `<span class="px-2 py-0.5 text-[10px] font-bold rounded-sm bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Engineering</span>`;
                        } else if (data === 'Sales') {
                            return `<span class="px-2 py-0.5 text-[10px] font-bold rounded-sm bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300">Sales</span>`;
                        }
                        return `<span class="px-2 py-0.5 text-[10px] font-bold rounded-sm bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">${data}</span>`;
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
                                        class="p-1.5 text-amber-600 hover:text-amber-800 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/40 rounded-sm transition-colors cursor-pointer" title="Edit Data">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button type="button" onclick="deleteItem(${row.id})"
                                        class="p-1.5 text-rose-600 hover:text-rose-800 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-sm transition-colors cursor-pointer" title="Delete Data">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        `;
                    }
                }
            ]
        });

        $('#filter-category, #filter-group, #filter-source').on('change', function() {
            dataTable.ajax.reload();
        });

        $('#btn-reset-filters').on('click', function() {
            $('#filter-category').val('');
            $('#filter-group').val('');
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
        editForm.action = `{{ url('management/mfg-process-cost') }}/${item.id}`;

        const editFormContent = document.getElementById('edit-form-content');
        editFormContent.innerHTML = `
            @csrf
            @method('PUT')
            <div class="space-y-4 text-xs">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Category <span class="text-rose-500">*</span></label>
                        <select name="category" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm font-medium">
                            <option value="Product" ${item.category === 'Product' ? 'selected' : ''}>Product</option>
                            <option value="Tooling" ${item.category === 'Tooling' ? 'selected' : ''}>Tooling</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Group Mfg Process <span class="text-rose-500">*</span></label>
                        <input type="text" name="process_group" value="${item.process_group || ''}" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm font-medium">
                    </div>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Mfg Process Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="process_name" value="${item.process_name || ''}" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm font-medium">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Control Point</label>
                        <input type="text" name="control_point" value="${item.control_point || ''}" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm font-medium">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">UOM</label>
                        <input type="text" name="uom" value="${item.uom || ''}" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm font-medium">
                    </div>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Rate Unit (Idr / Units)</label>
                    <input type="text" name="rate_unit" value="${item.rate_unit || ''}" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm font-medium">
                </div>
                <div class="p-3 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-sm space-y-3">
                    <span class="block font-extrabold text-[11px] uppercase tracking-wider text-slate-600 dark:text-slate-400">SAI Cost Rate (Eng COGM)</span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Min Cost Rate <span class="text-slate-400 font-normal">(Optional)</span></label>
                            <input type="number" step="0.01" name="min_cost_rate" value="${item.min_cost_rate !== null ? item.min_cost_rate : ''}" class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-sm font-semibold">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Std Cost Rate <span class="text-rose-500">*</span></label>
                            <input type="number" step="0.01" name="std_cost_rate" value="${item.std_cost_rate !== null ? item.std_cost_rate : ''}" required class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-sm font-semibold">
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Rate Source <span class="text-rose-500">*</span></label>
                    <select name="rate_source" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm font-medium">
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
                text: "This Manufacturing Process Cost will be deleted from the system.",
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
        form.action = `{{ url('management/mfg-process-cost') }}/${id}`;

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
