@extends('layouts.app')

@section('title', 'Master Data Material Cost · Promise Management')

@section('content')
<x-sweetalert />
<div class="flex-1 overflow-y-auto p-4 pt-17.5 space-y-5 transition-colors duration-200">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-lg font-bold tracking-tight text-slate-800 dark:text-white">Material Cost Rate</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Manage master material rates (Spec, Type, Thickness, Price/Kg, Scrap Price, & Rate Source).
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('management.material-cost.export') }}"
               class="inline-flex items-center gap-2 px-3.5 h-9 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-sm transition-colors">
                <i class="fa-solid fa-file-excel text-xs"></i> Export CSV
            </a>

            <button onclick="openAddModal()"
                    class="inline-flex items-center gap-2 px-3.5 h-9 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-sm transition-colors cursor-pointer">
                <i class="fa-solid fa-plus text-xs"></i> Add Material Rate
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
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Total Material Rates</span>
                <div class="text-lg font-black text-slate-800 dark:text-white mt-0.5" id="stat-total">{{ $totalItems }}</div>
            </div>
            <div class="w-8 h-8 rounded-none bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold">
                <i class="fa-solid fa-boxes-stacked text-sm"></i>
            </div>
        </div>

        <div class="p-3 bg-white dark:bg-slate-800 rounded-sm border border-slate-300 dark:border-slate-700 flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Global Standard Rates</span>
                <div class="text-lg font-black text-slate-800 dark:text-white mt-0.5" id="stat-general">{{ $generalCount }}</div>
            </div>
            <div class="w-8 h-8 rounded-none bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                <i class="fa-solid fa-globe text-sm"></i>
            </div>
        </div>

        <div class="p-3 bg-white dark:bg-slate-800 rounded-sm border border-slate-300 dark:border-slate-700 flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Customer Specific Rates</span>
                <div class="text-lg font-black text-slate-800 dark:text-white mt-0.5" id="stat-custom">{{ $customCount }}</div>
            </div>
            <div class="w-8 h-8 rounded-none bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                <i class="fa-solid fa-user-tag text-sm"></i>
            </div>
        </div>

        <div class="p-3 bg-white dark:bg-slate-800 rounded-sm border border-slate-300 dark:border-slate-700 flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Sheet Material</span>
                <div class="text-lg font-black text-slate-800 dark:text-white mt-0.5" id="stat-sheet">{{ $sheetCount }}</div>
            </div>
            <div class="w-8 h-8 rounded-none bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold">
                <i class="fa-solid fa-layer-group text-sm"></i>
            </div>
        </div>
    </div>

    {{-- Main DataTable with Filter Popover --}}
    <x-table id="material-cost-table" class="w-full text-xs text-left border-collapse">
        <x-slot:filters>
            <div class="space-y-3">
                {{-- Customer Filter --}}
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Customer Context</label>
                    <select id="filter-customer" class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm text-xs font-semibold text-slate-700 dark:text-slate-200 focus:outline-none focus:border-blue-500">
                        <option value="">All Contexts</option>
                        <option value="general">Global (Standard)</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Material Type Filter --}}
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Material Type</label>
                    <select id="filter-type" class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm text-xs font-semibold text-slate-700 dark:text-slate-200 focus:outline-none focus:border-blue-500">
                        <option value="">All Types</option>
                        @foreach($materialTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
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
            <tr class="border-b-2 border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900 text-[10px] font-extrabold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                <th class="px-3 py-2.5 w-12 text-center border-r border-slate-200 dark:border-slate-700">No.</th>
                <th class="px-4 py-2.5 border-r border-slate-200 dark:border-slate-700">Customer Context</th>
                <th class="px-4 py-2.5 border-r border-slate-200 dark:border-slate-700">Material Spec</th>
                <th class="px-3 py-2.5 text-center border-r border-slate-200 dark:border-slate-700">Type</th>
                <th class="px-3 py-2.5 text-right border-r border-slate-200 dark:border-slate-700">Thickness (mm)</th>
                <th class="px-4 py-2.5 text-right border-r border-slate-200 dark:border-slate-700 font-extrabold text-slate-800 dark:text-slate-100">Price / Kg (IDR) <span class="text-rose-500">*</span></th>
                <th class="px-4 py-2.5 text-right border-r border-slate-200 dark:border-slate-700">Scrap / Kg (IDR)</th>
                <th class="px-4 py-2.5 border-r border-slate-200 dark:border-slate-700">Rate Source</th>
                <th class="px-3 py-2.5 text-center border-r border-slate-200 dark:border-slate-700">Valid From</th>
                <th class="px-3 py-2.5 text-center w-20">Actions</th>
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
                <i class="fa-solid fa-plus-circle text-blue-600"></i> Add Material Cost Rate
            </h3>
            <button onclick="closeAddModal()" class="text-slate-400 hover:text-slate-600 text-lg leading-none cursor-pointer">&times;</button>
        </div>
        <form action="{{ route('management.material-cost.store') }}" method="POST" class="p-5 overflow-y-auto space-y-4">
            @include('management.material-cost._form', ['item' => null])
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
                <i class="fa-solid fa-pen-to-square text-amber-500"></i> Edit Material Cost Rate
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
        const dataTable = window.defaultDataTable('#material-cost-table', {
            serverSide: true,
            processing: true,
            ordering: true,
            order: [[2, 'asc']],
            ajax: {
                url: '{{ route("management.material-cost.data") }}',
                data: function(d) {
                    d.customer_id = $('#filter-customer').val();
                    d.material_type = $('#filter-type').val();
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
                    data: 'customer',
                    orderable: true,
                    className: 'font-semibold',
                    render: function (data, type, row) {
                        if (row.customer) {
                            return `<span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300">${row.customer.code || row.customer.name}</span>`;
                        }
                        return `<span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">Global (Standard)</span>`;
                    }
                },
                { data: 'material_spec', orderable: true, className: 'font-extrabold text-slate-900 dark:text-white' },
                {
                    data: 'material_type',
                    orderable: true,
                    className: 'text-center font-semibold',
                    render: function(data) {
                        if (data === 'Sheet') {
                            return `<span class="px-2 py-0.5 text-[10px] font-bold rounded-sm bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">Sheet</span>`;
                        } else if (data === 'Coil') {
                            return `<span class="px-2 py-0.5 text-[10px] font-bold rounded-sm bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">Coil</span>`;
                        }
                        return data;
                    }
                },
                {
                    data: 'thickness',
                    orderable: true,
                    className: 'text-right font-semibold text-slate-800 dark:text-slate-200',
                    render: function(data) {
                        if (data === null || data === undefined || data === '') return '-';
                        return `${parseFloat(data).toFixed(2)} mm`;
                    }
                },
                {
                    data: 'price_per_kg',
                    orderable: true,
                    className: 'text-right font-bold text-slate-900 dark:text-white bg-blue-50/30 dark:bg-blue-950/10',
                    render: function(data) {
                        if (data === null || data === undefined) return '0.00';
                        return `Rp ${parseFloat(data).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                    }
                },
                {
                    data: 'scrap_price_per_kg',
                    orderable: true,
                    className: 'text-right font-semibold text-emerald-700 dark:text-emerald-400 bg-emerald-50/30 dark:bg-emerald-950/10',
                    render: function(data) {
                        if (data === null || data === undefined || data == 0) return 'Rp 0.00';
                        return `Rp ${parseFloat(data).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
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
                    data: 'valid_from',
                    orderable: true,
                    className: 'text-center text-slate-600 dark:text-slate-400',
                    render: function(data) {
                        if (!data) return '-';
                        return data.substring(0, 10);
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

        $('#filter-customer, #filter-type, #filter-source').on('change', function() {
            dataTable.ajax.reload();
        });

        $('#btn-reset-filters').on('click', function() {
            $('#filter-customer').val('');
            $('#filter-type').val('');
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
        editForm.action = `{{ url('management/material-cost') }}/${item.id}`;

        const validFromStr = item.valid_from ? item.valid_from.substring(0, 10) : '';

        const editFormContent = document.getElementById('edit-form-content');
        editFormContent.innerHTML = `
            @csrf
            @method('PUT')
            <div class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Customer Context</label>
                    <select name="customer_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm font-medium">
                        <option value="">Global (General Standard)</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" ${item.customer_id == {{ $c->id }} ? 'selected' : ''}>{{ $c->name }} ({{ $c->code ?? '-' }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Material Spec <span class="text-rose-500">*</span></label>
                        <input type="text" name="material_spec" value="${item.material_spec || ''}" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm font-semibold">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Material Type <span class="text-rose-500">*</span></label>
                        <select name="material_type" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm font-medium">
                            <option value="Sheet" ${item.material_type === 'Sheet' ? 'selected' : ''}>Sheet</option>
                            <option value="Coil" ${item.material_type === 'Coil' ? 'selected' : ''}>Coil</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Thickness (mm)</label>
                    <input type="number" step="0.01" name="thickness" value="${item.thickness !== null ? item.thickness : ''}" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm font-medium">
                </div>

                <div class="p-3 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-sm space-y-3">
                    <span class="block font-extrabold text-[11px] uppercase tracking-wider text-slate-600 dark:text-slate-400 flex items-center gap-1.5">
                        <i class="fa-solid fa-money-bill-wave text-emerald-500"></i> Material Price & Scrap Rate per Kg (IDR)
                    </span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Price per Kg (IDR) <span class="text-rose-500">*</span></label>
                            <input type="number" step="0.01" name="price_per_kg" value="${item.price_per_kg || ''}" required class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-sm font-bold">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Scrap Price per Kg (IDR)</label>
                            <input type="number" step="0.01" name="scrap_price_per_kg" value="${item.scrap_price_per_kg || 0}" class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-sm font-semibold">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Rate Source <span class="text-rose-500">*</span></label>
                        <select name="rate_source" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm font-medium">
                            <option value="Sales" ${item.rate_source === 'Sales' ? 'selected' : ''}>Sales</option>
                            <option value="Engineering" ${item.rate_source === 'Engineering' ? 'selected' : ''}>Engineering</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Valid From</label>
                        <input type="date" name="valid_from" value="${validFromStr}" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm font-medium">
                    </div>
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
                text: "This Material Cost Rate will be deleted from the system.",
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
        form.action = `{{ url('management/material-cost') }}/${id}`;

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
