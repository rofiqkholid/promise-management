@extends('layouts.app')

@section('title', 'SPK 2 Fastener / Standard Part · Promise Management')

@section('content')
<div class="flex-1 overflow-y-auto p-4 pt-17.5 space-y-4 transition-colors duration-200">
    
    {{-- ===== HEADER ACTIONS ===== --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-lg font-bold tracking-tight text-slate-800 dark:text-white">SPK 2 Fastener / Standard Part</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">Manage Standard Part & Fastener Work Orders (SPK 2)</p>
        </div>
        <div class="flex gap-2">
            <button type="button" id="btn-open-select-ebd-modal"
                    class="inline-flex items-center justify-center gap-2 px-3.5 h-9 bg-indigo-600 hover:bg-indigo-700 text-white rounded-sm text-xs font-semibold shadow-xs transition-all cursor-pointer">
                <i class="fa-solid fa-plus text-[10px]"></i> Create SPK 2 Fastener
            </button>
        </div>
    </div>
    
    {{-- KPI Summary Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5 gap-2.5">
        {{-- Card 1: Total WO --}}
        <div class="bg-white dark:bg-slate-800 p-3 rounded-sm border border-slate-300 dark:border-slate-700 flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">SPK 2 Total</span>
                <span class="text-lg font-black text-slate-800 dark:text-white leading-none mt-0.5 block">{{ $totalWo }}</span>
            </div>
            <div class="w-8 h-8 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-sm flex items-center justify-center text-sm flex-shrink-0">
                <i class="fa-solid fa-file-signature"></i>
            </div>
        </div>
        {{-- Card 2: Urgent --}}
        <div class="bg-white dark:bg-slate-800 p-3 rounded-sm border border-slate-300 dark:border-slate-700 flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Urgent Priority</span>
                <span class="text-lg font-black text-slate-800 dark:text-white leading-none mt-0.5 block">{{ $urgentWo }}</span>
            </div>
            <div class="w-8 h-8 bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 rounded-sm flex items-center justify-center text-sm flex-shrink-0">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
        </div>
        {{-- Card 3: Standard --}}
        <div class="bg-white dark:bg-slate-800 p-3 rounded-sm border border-slate-300 dark:border-slate-700 flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Standard Priority</span>
                <span class="text-lg font-black text-slate-800 dark:text-white leading-none mt-0.5 block">{{ $standardWo }}</span>
            </div>
            <div class="w-8 h-8 bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 rounded-sm flex items-center justify-center text-sm flex-shrink-0">
                <i class="fa-solid fa-circle-exclamation"></i>
            </div>
        </div>
        {{-- Card 4: Low --}}
        <div class="bg-white dark:bg-slate-800 p-3 rounded-sm border border-slate-300 dark:border-slate-700 flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Low Priority</span>
                <span class="text-lg font-black text-slate-800 dark:text-white leading-none mt-0.5 block">{{ $lowWo }}</span>
            </div>
            <div class="w-8 h-8 bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 rounded-sm flex items-center justify-center text-sm flex-shrink-0">
                <i class="fa-solid fa-circle-info"></i>
            </div>
        </div>
        {{-- Card 5: Completion Rate --}}
        <div class="bg-white dark:bg-slate-800 p-3 rounded-sm border border-slate-300 dark:border-slate-700 flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Completion Rate</span>
                <span class="text-lg font-black text-slate-800 dark:text-white leading-none mt-0.5 block">{{ $completionRate }}%</span>
            </div>
            <div class="w-8 h-8 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-sm flex items-center justify-center text-sm flex-shrink-0">
                <i class="fa-solid fa-chart-pie"></i>
            </div>
        </div>
    </div>

    @if(session('success'))
        @push('scripts')
        <script>
            $(document).ready(function() {
                if (typeof showToast === 'function') {
                    showToast('{{ session('success') }}', 'success');
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
                    showToast('{{ session('error') }}', 'error');
                }
            });
        </script>
        @endpush
    @endif

    {{-- SPK List Card with x-table & Filter Popover --}}
    <x-table id="work-orders-table">
        <x-slot:filters>
            <div>
                <label for="filter-priority" class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Priority</label>
                <select id="filter-priority" class="w-full px-2.5 py-1.5 text-xs bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm font-semibold text-slate-700 dark:text-slate-200 focus:outline-none focus:border-blue-500">
                    <option value="">All Priorities</option>
                    <option value="URGENT">URGENT</option>
                    <option value="STANDARD">STANDARD</option>
                    <option value="LOW">LOW</option>
                </select>
            </div>
            <div>
                <label for="filter-status" class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Status</label>
                <select id="filter-status" class="w-full px-2.5 py-1.5 text-xs bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm font-semibold text-slate-700 dark:text-slate-200 focus:outline-none focus:border-blue-500">
                    <option value="">All Statuses</option>
                    <option value="Draft">Draft</option>
                    <option value="Pending Approval">Pending Approval</option>
                    <option value="Finish">Approved / Released</option>
                    <option value="Rejected">Rejected</option>
                </select>
            </div>
        </x-slot:filters>

        <thead>
            <tr class="border-b-2 border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900 text-[10px] font-extrabold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                <th class="px-3 py-2.5 text-center w-10 border-r border-slate-200 dark:border-slate-700">No</th>
                <th class="px-3 py-2.5 border-r border-slate-200 dark:border-slate-700">WO Number</th>
                <th class="px-3 py-2.5 border-r border-slate-200 dark:border-slate-700">Revision</th>
                <th class="px-3 py-2.5 border-r border-slate-200 dark:border-slate-700">Inquiry No</th>
                <th class="px-3 py-2.5 border-r border-slate-200 dark:border-slate-700">Customer & Model</th>
                <th class="px-3 py-2.5 text-center border-r border-slate-200 dark:border-slate-700">Priority</th>
                <th class="px-3 py-2.5 w-44 border-r border-slate-200 dark:border-slate-700">Status</th>
                <th class="px-3 py-2.5 w-56 border-r border-slate-200 dark:border-slate-700">Dept PIC Progress</th>
                <th class="px-3 py-2.5 text-right w-40">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
        </tbody>
    </x-table>
</div>

{{-- MODAL SELECT EBD HEADER --}}
<div id="select-ebd-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-sm shadow-xl w-full max-w-xl flex flex-col overflow-hidden animate-in fade-in zoom-in duration-150">
        <div class="flex justify-between items-center px-5 py-3.5 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-layer-group text-indigo-600 dark:text-indigo-400 text-base"></i>
                <h3 class="text-sm font-bold text-slate-800 dark:text-white uppercase tracking-wider">Select EBD Header to Create SPK 2 Fastener</h3>
            </div>
            <button type="button" class="btn-close-ebd-modal text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-sm">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('management.work-order-fastener.create') }}" method="GET" class="flex flex-col flex-1 overflow-hidden">
            <div class="p-5 space-y-4 overflow-y-auto max-h-[70vh]">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                        Choose EBD Header <span class="text-rose-500">*</span>
                    </label>
                    <select id="modal-ebd-select" name="ebd_id" class="w-full text-xs" required>
                        <option value="">-- Select EBD Header --</option>
                        @foreach($ebdHeadersData as $ebd)
                            <option value="{{ $ebd['hashed_id'] }}">
                                WO: {{ $ebd['wo_number'] }} | Customer: {{ $ebd['customer_code'] }} | Model: {{ $ebd['model_name'] }} (Rev {{ $ebd['revision'] }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1.5">
                        Select an Engineering Breakdown (EBD) header to load Standard Part & Fastener data into your new SPK 2.
                    </p>
                </div>

                {{-- BOM Items Checklist Section --}}
                <div id="bom-checklist-wrapper" class="hidden border border-slate-200 dark:border-slate-800 rounded-sm p-3 space-y-2 bg-slate-50/50 dark:bg-slate-800/30">
                    <div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-700 pb-2">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-200">Standard Part / EBD Items</span>
                        <label class="flex items-center gap-1.5 text-xs text-indigo-600 font-semibold cursor-pointer">
                            <input type="checkbox" id="chk-select-all-bom" checked class="rounded-sm border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            Select All
                        </label>
                    </div>
                    
                    <div class="max-h-48 overflow-y-auto border border-slate-200 dark:border-slate-700/80 rounded-sm bg-white dark:bg-slate-900">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-100 dark:bg-slate-800 text-[10px] uppercase font-bold text-slate-500 sticky top-0">
                                <tr>
                                    <th class="p-2 w-8 text-center">Sel</th>
                                    <th class="p-2 w-14">BOM Lvl</th>
                                    <th class="p-2">Std Part No</th>
                                    <th class="p-2">Std Part Name</th>
                                    <th class="p-2 w-12 text-center">Qty</th>
                                    <th class="p-2 w-14 text-center">UOM</th>
                                    <th class="p-2 w-16 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody id="bom-checklist-tbody" class="divide-y divide-slate-100 dark:divide-slate-800">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 px-5 py-3 border-t border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50 flex-shrink-0">
                <button type="button" class="btn-close-ebd-modal px-3.5 py-1.5 text-xs font-medium text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 rounded-sm cursor-pointer">Cancel</button>
                <button type="submit" class="px-4 py-1.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-sm shadow-xs cursor-pointer flex items-center gap-1.5">
                    Continue <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    $(function() {
        const ebdData = @json($ebdHeadersData);
        const ebdLookup = {};
        ebdData.forEach(e => ebdLookup[String(e.hashed_id)] = e);

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

            let visibleCount = 0;
            ebd.items.forEach(item => {
                if (!item.std_part_no || item.std_part_no === '—' || item.std_part_no.trim() === '') {
                    return; // Skip items without std_part_no
                }
                visibleCount++;
                const bomLevel = item.bom_level || '1';
                const stdPartNo = item.std_part_no;
                const stdPartName = (item.std_part_name && item.std_part_name !== '—') ? item.std_part_name : '—';
                const stdQty = (item.std_qty !== null && item.std_qty !== undefined && item.std_qty !== '') ? item.std_qty : '—';
                const stdUom = (item.std_uom && item.std_uom !== '—') ? item.std_uom : '—';
                const partStatus = item.status || '—';
                const row = `
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                        <td class="p-2 text-center">
                            <input type="checkbox" name="items[]" value="${item.id}" checked class="bom-chk-item rounded-sm border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                        </td>
                        <td class="p-2 font-mono font-bold text-indigo-600 dark:text-indigo-400">${bomLevel}</td>
                        <td class="p-2 font-mono font-semibold text-slate-800 dark:text-slate-200">${stdPartNo || '—'}</td>
                        <td class="p-2 text-slate-700 dark:text-slate-300">${stdPartName || '—'}</td>
                        <td class="p-2 text-center font-bold text-slate-700 dark:text-slate-300">${stdQty}</td>
                        <td class="p-2 text-center text-slate-500 text-[11px]">${stdUom}</td>
                        <td class="p-2 text-center font-semibold text-slate-600 dark:text-slate-300">
                            <span class="inline-block px-1.5 py-0.5 text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-700 rounded-sm font-mono">${partStatus}</span>
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });

            if (visibleCount === 0) {
                $('#bom-checklist-wrapper').addClass('hidden');
            } else {
                $('#chk-select-all-bom').prop('checked', true);
                $('#bom-checklist-wrapper').removeClass('hidden');
            }
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
                url: "{{ route('management.work-order-fastener.index') }}",
                data: function(d) {
                    d.priority = $('#filter-priority').val();
                    d.status = $('#filter-status').val();
                }
            },
            columns: [
                { data: 'index_num', orderable: false, searchable: false, className: 'text-center text-slate-400 font-mono text-xs' },
                { data: 'wo_number', orderable: true, searchable: true, className: 'font-bold text-slate-800 dark:text-slate-100' },
                { data: 'revision_no', orderable: true, searchable: false, className: 'font-mono text-slate-600 dark:text-slate-330' },
                { 
                    data: 'inquiry_no', 
                    orderable: false, 
                    searchable: true,
                    render: function (data, type, row) {
                        return '<span class="text-xs text-slate-400">—</span>';
                    }
                },
                {
                    data: 'customer_code',
                    orderable: false,
                    searchable: true,
                    render: function(data, type, row) {
                        return `
                            <span class="font-bold">${data}</span>
                            <span class="text-slate-400 mx-1">•</span>
                            <span class="text-slate-600 dark:text-slate-350 font-semibold">${row.model_name}</span>
                            <div style="display:none; font-size:0px;">${row.hidden_products}</div>
                        `;
                    }
                },
                {
                    data: 'priority',
                    orderable: true,
                    searchable: false,
                    className: 'text-center',
                    render: function(data, type, row) {
                        const cls = data === 'URGENT' 
                            ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border-rose-300 dark:border-rose-900/50' 
                            : (data === 'STANDARD' ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border-amber-300 dark:border-amber-900/50' : 'bg-slate-50 dark:bg-slate-900 text-slate-500 dark:text-slate-400 border-slate-200 dark:border-slate-700');
                        return `<span class="inline-block px-2 py-0.5 text-[10px] font-bold border rounded-sm ${cls}">${data}</span>`;
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
                                    <span class="inline-block px-2 py-0.5 text-[10px] font-bold border rounded-sm ${cls}">
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
                    data: 'hashed_id',
                    orderable: false,
                    searchable: false,
                    className: 'text-right',
                    render: function (data, type, row) {
                        return `
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="${row.show_url}" title="View Details"
                                   class="w-6 h-6 flex items-center justify-center bg-slate-100 dark:bg-slate-700 border border-slate-300 dark:border-slate-600 hover:border-blue-400 hover:text-blue-700 text-slate-600 dark:text-slate-300 transition-colors rounded-sm">
                                   <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                </a>
                                <a href="${row.show_url}" target="_blank" onclick="const w = window.open(this.href, '_blank'); w.onload = function() { setTimeout(() => { w.print(); }, 500); }; return false;" title="Print"
                                   class="w-6 h-6 flex items-center justify-center bg-blue-600 hover:bg-blue-750 text-white transition-colors">
                                    <i class="fa-solid fa-print text-[10px]"></i>
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
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-widest mb-2">No SPK 2 Fastener Created Yet</h4>
                        <p class="text-xs text-gray-400 dark:text-gray-500 max-w-xs mx-auto font-medium leading-relaxed">Click "Create SPK 2 Fastener" above and select an EBD Header to generate an SPK 2.</p>
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
