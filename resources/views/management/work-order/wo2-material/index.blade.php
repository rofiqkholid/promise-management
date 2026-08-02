@extends('layouts.app')

@section('title', 'SPK 2 Raw Material Specification · Promise Management')

@section('content')
<div class="flex-1 overflow-y-auto p-4 pt-17.5 space-y-4 transition-colors duration-200">
    
    {{-- Header with KPI Cards side by side --}}
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 border-b border-slate-100 dark:border-slate-800/80 mb-3 pb-3">
        <div class="flex items-center gap-4 flex-shrink-0">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">SPK 2 Raw Material Specification List</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Manage Raw Material Specification Work Orders (SPK 2)</p>
            </div>
            <button type="button" id="btn-open-select-ebd-modal"
                    class="inline-flex items-center gap-2 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xs text-xs font-semibold shadow-xs transition-all cursor-pointer">
                <i class="fa-solid fa-plus text-[10px]"></i> Create SPK 2 Raw Material
            </button>
        </div>
        
        {{-- KPI Cards --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5 gap-3 w-full lg:w-auto">
            {{-- Card 1: Total WO --}}
            <div class="bg-white dark:bg-slate-900 p-2.5 rounded-xs border border-slate-200 dark:border-slate-800 flex items-center gap-3">
                <div class="w-8 h-8 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-xs flex items-center justify-center text-sm flex-shrink-0">
                    <i class="fa-solid fa-file-signature"></i>
                </div>
                <div>
                    <span class="text-[10px] font-semibold text-slate-600 dark:text-slate-500 uppercase tracking-wider block">SPK 2 Total</span>
                    <span class="text-base font-extrabold text-slate-800 dark:text-white leading-none">{{ $totalWo }}</span>
                </div>
            </div>
            {{-- Card 2: Urgent --}}
            <div class="bg-white dark:bg-slate-900 p-2.5 rounded-xs border border-slate-200 dark:border-slate-800 flex items-center gap-3">
                <div class="w-8 h-8 bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 rounded-xs flex items-center justify-center text-sm flex-shrink-0">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <span class="text-[10px] font-semibold text-slate-600 dark:text-slate-500 uppercase tracking-wider block">Urgent Priority</span>
                    <span class="text-base font-extrabold text-slate-800 dark:text-white leading-none">{{ $urgentWo }}</span>
                </div>
            </div>
            {{-- Card 3: Standard --}}
            <div class="bg-white dark:bg-slate-900 p-2.5 rounded-xs border border-slate-200 dark:border-slate-800 flex items-center gap-3">
                <div class="w-8 h-8 bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 rounded-xs flex items-center justify-center text-sm flex-shrink-0">
                    <i class="fa-solid fa-circle-exclamation"></i>
                </div>
                <div>
                    <span class="text-[10px] font-semibold text-slate-600 dark:text-slate-500 uppercase tracking-wider block">Standard Priority</span>
                    <span class="text-base font-extrabold text-slate-800 dark:text-white leading-none">{{ $standardWo }}</span>
                </div>
            </div>
            {{-- Card 4: Low --}}
            <div class="bg-white dark:bg-slate-900 p-2.5 rounded-xs border border-slate-200 dark:border-slate-800 flex items-center gap-3">
                <div class="w-8 h-8 bg-slate-50 dark:bg-slate-950 text-slate-500 dark:text-slate-400 rounded-xs flex items-center justify-center text-sm flex-shrink-0">
                    <i class="fa-solid fa-circle-info"></i>
                </div>
                <div>
                    <span class="text-[10px] font-semibold text-slate-600 dark:text-slate-500 uppercase tracking-wider block">Low Priority</span>
                    <span class="text-base font-extrabold text-slate-800 dark:text-white leading-none">{{ $lowWo }}</span>
                </div>
            </div>
            {{-- Card 5: Completion Rate --}}
            <div class="bg-white dark:bg-slate-900 p-2.5 rounded-xs border border-slate-200 dark:border-slate-800 flex items-center gap-3">
                <div class="w-8 h-8 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-xs flex items-center justify-center text-sm flex-shrink-0">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
                <div>
                    <span class="text-[10px] font-semibold text-slate-600 dark:text-slate-500 uppercase tracking-wider block">Completion Rate</span>
                    <span class="text-base font-extrabold text-slate-800 dark:text-white leading-none">{{ $completionRate }}%</span>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        @push('scripts')
        <script>
            $(document).ready(function() {
                if (typeof showToast === 'function') {
                    showToast("{{ session('success') }}", 'success');
                }
            });
        </script>
        @endpush
    @endif

    @if(session('error'))
        @push('scripts')
        <script>
            $(document).ready(function() {
                if (typeof showToast === 'function') {
                    showToast("{{ session('error') }}", 'error');
                }
            });
        </script>
        @endpush
    @endif

    {{-- SPK List Card with Integrated Filter Bar --}}
    <div class="bg-white dark:bg-slate-800 rounded-xs border border-slate-200 dark:border-slate-700 overflow-hidden relative">
        
        {{-- Integrated Filter Bar --}}
        <div class="flex flex-wrap items-center gap-4 p-4 bg-slate-50/50 dark:bg-slate-900/30">
            <div class="flex items-center gap-2">
                <label for="filter-priority" class="text-xs font-medium text-slate-600 dark:text-slate-500 uppercase tracking-wider">Priority:</label>
                <select id="filter-priority" class="bg-white dark:bg-slate-955 border border-slate-300 dark:border-slate-800 rounded-xs text-xs px-2.5 py-1.5 focus:outline-none focus:border-indigo-500 text-slate-700 dark:text-slate-300">
                    <option value="">All Priorities</option>
                    <option value="Urgent">Urgent</option>
                    <option value="Standard">Standard</option>
                    <option value="Low">Low</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <label for="filter-status" class="text-xs font-medium text-slate-600 dark:text-slate-500 uppercase tracking-wider">Approval Status:</label>
                <select id="filter-status" class="bg-white dark:bg-slate-955 border border-slate-300 dark:border-slate-800 rounded-xs text-xs px-2.5 py-1.5 focus:outline-none focus:border-indigo-500 text-slate-700 dark:text-slate-300">
                    <option value="">All Statuses</option>
                    <option value="Draft">Draft</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Approved">Approved</option>
                    <option value="Rejected">Rejected</option>
                </select>
            </div>
        </div>

        <div class="border-t border-slate-100 dark:border-slate-700 p-4 bg-white dark:bg-slate-800">
            <table id="work-orders-table" class="custom-table w-full text-left border-collapse">
                <thead>
                    <tr class="border-b-2 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/30 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                        <th class="px-3 py-2.5 w-8 text-center">#</th>
                        <th class="px-3 py-2.5">WO No</th>
                        <th class="px-3 py-2.5">Revision</th>
                        <th class="px-3 py-2.5">Customer &amp; Model</th>
                        <th class="px-3 py-2.5 text-center">Priority</th>
                        <th class="px-3 py-2.5 w-56">Task Progress</th>
                        <th class="px-3 py-2.5 w-44">Approval Status</th>
                        <th class="px-3 py-2.5 text-right w-40">Actions</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="hidden"><x-table class="hidden" id="dummy-table-to-load-js"></x-table></div>

@include('management.work-order.modal-wo-progress')

{{-- SELECT EBD MODAL --}}
<div id="select-ebd-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xs shadow-2xl w-full max-w-2xl mx-4 overflow-hidden animate-fade-in flex flex-col max-h-[85vh]">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50 flex-shrink-0">
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                <i class="fa-solid fa-file-invoice text-indigo-500"></i> Select EBD Reference for SPK 2 Raw Material
            </h3>
            <button type="button" class="btn-close-ebd-modal text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>
        <form action="{{ route('management.work-order-material.create') }}" method="GET" class="flex flex-col flex-1 overflow-hidden">
            <div class="p-5 space-y-4 flex-1 overflow-y-auto">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Choose EBD Header Reference <span class="text-rose-500">*</span></label>
                    <select id="modal-ebd-select" name="ebd_header_id" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:border-indigo-500 select2-custom" required>
                        <option value="">-- Select EBD Header --</option>
                        @foreach($ebdHeadersData as $ebd)
                            <option value="{{ $ebd['hashed_id'] }}">
                                WO: {{ $ebd['wo_number'] }} | Customer: {{ $ebd['customer_code'] }} | Model: {{ $ebd['model_name'] }} (Rev {{ $ebd['revision'] }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- BOM Items Checklist Section --}}
                <div id="bom-checklist-wrapper" class="hidden space-y-2 border-t border-slate-200 dark:border-slate-700 pt-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Select BOM Parts to Include</span>
                        <label class="inline-flex items-center gap-1.5 text-xs text-indigo-600 dark:text-indigo-400 font-semibold cursor-pointer">
                            <input type="checkbox" id="chk-select-all-bom" checked class="rounded-xs border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            Select / Deselect All
                        </label>
                    </div>
                    <div class="overflow-x-auto border border-slate-200 dark:border-slate-700 rounded-xs bg-slate-50/50 dark:bg-slate-900/30 max-h-[280px]">
                        <table class="w-full text-xs text-left border-collapse">
                            <thead class="sticky top-0 bg-slate-100 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700 text-[10px] uppercase font-bold text-slate-500">
                                <tr>
                                    <th class="p-2 w-10 text-center">#</th>
                                    <th class="p-2">BOM Level</th>
                                    <th class="p-2">Part No</th>
                                    <th class="p-2">Part Name</th>
                                    <th class="p-2">Material Spec</th>
                                    <th class="p-2">Material Size</th>
                                </tr>
                            </thead>
                            <tbody id="bom-checklist-tbody" class="divide-y divide-slate-200 dark:divide-slate-800 bg-white dark:bg-slate-900">
                                {{-- Dynamically populated via JS --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 px-5 py-3 border-t border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50 flex-shrink-0">
                <button type="button" class="btn-close-ebd-modal px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xs transition-colors cursor-pointer">Cancel</button>
                <button type="submit" class="px-4 py-2 text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white rounded-xs transition-colors cursor-pointer">
                    Proceed to Form <i class="fa-solid fa-arrow-right ml-1"></i>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function () {
        const ebdData = @json($ebdHeadersData);
        const ebdLookup = {};
        const ebdList = Array.isArray(ebdData) ? ebdData : (ebdData ? Object.values(ebdData) : []);
        ebdList.forEach(e => ebdLookup[String(e.hashed_id)] = e);

        function initSelect2InModal() {
            if ($.fn.select2) {
                if ($('#modal-ebd-select').hasClass('select2-hidden-accessible')) {
                    $('#modal-ebd-select').select2('destroy');
                }
                $('#modal-ebd-select').select2({
                    dropdownParent: $('#select-ebd-modal'),
                    placeholder: '-- Select EBD Header --',
                    allowClear: true,
                    width: '100%'
                });
            }
        }

        $('#btn-open-select-ebd-modal').on('click', function() {
            $('#select-ebd-modal').removeClass('hidden').addClass('flex');
            setTimeout(initSelect2InModal, 50);
        });
        $('.btn-close-ebd-modal').on('click', function() {
            $('#select-ebd-modal').addClass('hidden').removeClass('flex');
        });

        $('#modal-ebd-select').on('change select2:select', function() {
            const hashedId = String($(this).val() || '');
            const ebd = ebdLookup[hashedId];
            const tbody = $('#bom-checklist-tbody');
            tbody.empty();

            if (!ebd || !ebd.items || ebd.items.length === 0) {
                $('#bom-checklist-wrapper').addClass('hidden');
                return;
            }

            ebd.items.forEach(item => {
                const bomLevel = item.bom_level || '1';
                const matSpec = item.mat_spec || '—';
                const matSize = item.mat_size || '—';
                const row = `
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                        <td class="p-2 text-center">
                            <input type="checkbox" name="selected_bom_ids[]" value="${item.id}" checked class="bom-chk-item rounded-xs border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                        </td>
                        <td class="p-2 font-mono font-bold text-indigo-600 dark:text-indigo-400">${bomLevel}</td>
                        <td class="p-2 font-mono font-semibold text-slate-800 dark:text-slate-200">${item.part_no || '—'}</td>
                        <td class="p-2 text-slate-700 dark:text-slate-300">${item.part_name || '—'}</td>
                        <td class="p-2 font-semibold text-slate-700 dark:text-slate-300">${matSpec}</td>
                        <td class="p-2 font-mono text-slate-600 dark:text-slate-400">${matSize}</td>
                    </tr>
                `;
                tbody.append(row);
            });

            $('#chk-select-all-bom').prop('checked', true);
            $('#bom-checklist-wrapper').removeClass('hidden');
        });

        $('#chk-select-all-bom').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.bom-chk-item').prop('checked', isChecked);
        });

        const table = defaultDataTable('#work-orders-table', {
            processing: true,
            serverSide: true,
            order: [[1, 'desc']],
            ajax: {
                url: "{{ route('management.work-order-material.index') }}",
                data: function (d) {
                    d.priority = $('#filter-priority').val();
                    d.status = $('#filter-status').val();
                }
            },
            columns: [
                { data: 'index_num', orderable: false, searchable: false, className: 'text-center text-slate-400 font-mono text-[10px]' },
                {
                    data: 'wo_number',
                    className: 'font-bold text-slate-800 dark:text-slate-100',
                    render: function (data, type, row) {
                        return `<a href="${row.show_url}" class="text-blue-600 dark:text-blue-400 hover:underline font-mono">${data}</a>`;
                    }
                },
                {
                    data: 'revision_no',
                    className: 'text-center font-mono text-xs',
                    render: function(data) {
                        return `${data}`;
                    }
                },
                {
                    data: 'customer_code',
                    render: function (data, type, row) {
                        return `
                            <div>
                                <div class="font-bold text-slate-800 dark:text-slate-200">${data}</div>
                                <div class="text-[10px] text-slate-400">${row.model_name}</div>
                            </div>
                        `;
                    }
                },
                {
                    data: 'priority',
                    className: 'text-center',
                    render: function (data) {
                        const priorityClasses = {
                            'Urgent': 'bg-rose-50 text-rose-600 border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-900/30',
                            'Standard': 'bg-amber-50 text-amber-600 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-900/30',
                            'Low': 'bg-slate-50 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700'
                        };
                        const cls = priorityClasses[data] || priorityClasses['Standard'];
                        return `<span class="inline-block px-2 py-0.5 text-[10px] font-bold uppercase border rounded-xs ${cls}">${data}</span>`;
                    }
                },
                {
                    data: 'dept_progress',
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row) {
                        if (!data || data.length === 0) {
                            return '<span class="text-slate-400">—</span>';
                        }
                        let html = '<div class="flex flex-col gap-2">';
                        data.forEach(function (dp) {
                            html += `
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-bold font-mono text-slate-500 dark:text-slate-455 w-8 tracking-wider">${dp.code}</span>
                                    <div class="w-32 bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden border border-slate-200 dark:border-slate-700/40">
                                        <div class="bg-indigo-600 h-full rounded-full transition-all" style="width: ${dp.percent}%"></div>
                                    </div>
                                    <span class="text-[10px] font-mono font-bold text-slate-600 dark:text-slate-400 whitespace-nowrap">${dp.completed}/${dp.total}</span>
                                </div>
                            `;
                        });
                        html += '</div>';
                        return html;
                    }
                },
                {
                    data: 'display_status',
                    orderable: true,
                    searchable: false,
                    render: function (data, type, row) {
                        const cls = data === 'Draft' 
                            ? 'bg-blue-50 dark:bg-blue-955/40 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-900/30' 
                            : (data === 'In Progress' ? 'bg-amber-50 dark:bg-amber-955/40 text-amber-700 dark:text-amber-400 border-amber-300 dark:border-amber-900/50' 
                            : (data === 'Rejected' ? 'bg-rose-50 dark:bg-rose-955/40 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-900/30'
                            : 'bg-emerald-50 dark:bg-emerald-955/40 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-900/30'));
                        
                        let progressHtml = '';
                        if (row.total_approvals > 0) {
                            progressHtml = `
                                <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden border border-slate-200 dark:border-slate-700/40">
                                    <div class="bg-emerald-500 h-full rounded-full transition-all" style="width: ${row.approval_percent}%"></div>
                                </div>
                            `;
                        }

                        return `
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="inline-block px-2 py-0.5 text-[10px] font-bold border rounded-xs ${cls}">
                                        ${data}
                                    </span>
                                    <span class="text-[9px] font-mono font-bold text-slate-500">${row.approved_approvals}/${row.total_approvals}</span>
                                </div>
                                ${progressHtml}
                            </div>
                        `;
                    }
                },
                {
                    data: 'hashed_id',
                    orderable: false,
                    searchable: false,
                    className: 'text-right',
                    render: function (data, type, row) {
                        return `
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="${row.show_url}" title="View Details"
                                   class="w-6 h-6 flex items-center justify-center bg-slate-100 dark:bg-slate-700 border border-slate-300 dark:border-slate-600 hover:border-blue-400 hover:text-blue-700 text-slate-600 dark:text-slate-300 transition-colors">
                                   <i class="fa-solid fa-eye text-[10px]"></i>
                                </a>
                                <button type="button" title="Track Progress & Checklist"
                                        onclick="window.dispatchEvent(new CustomEvent('open-wo-progress', { detail: { hashedId: '${data}' } }))"
                                        class="w-6 h-6 flex items-center justify-center bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 hover:border-indigo-400 text-indigo-600 transition-colors cursor-pointer">
                                    <i class="fa-solid fa-list-check text-[10px]"></i>
                                </button>
                            </div>
                        `;
                    }
                }
            ],
            language: {
                emptyTable: `
                    <div class="py-16 flex flex-col items-center justify-center text-center w-full">
                        <div>
                            <i class="fa-solid fa-file-signature text-3xl text-slate-300 dark:text-slate-600 m-4"></i>
                        </div>
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-widest mb-2">No SPK 2 Raw Material Created Yet</h4>
                        <p class="text-xs text-gray-400 dark:text-gray-500 max-w-xs mx-auto font-medium leading-relaxed">Click "Create SPK 2 Raw Material" above and select an EBD Header to generate an SPK 2.</p>
                    </div>
                `
            }
        });

        $('#filter-priority, #filter-status').on('change', function() {
            table.ajax.reload();
        });
    });
</script>
@endpush
@endsection
