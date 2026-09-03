@extends('layouts.app')

@section('title', 'EBD Request · Promise Management')
@section('page_title', 'EBD Request')
@section('header-title', 'Feasibility Study')

@section('content')
<div class="flex-1 overflow-y-auto p-4 pt-17.5 space-y-4 transition-colors duration-200">

    {{-- ===== HEADER ACTIONS ===== --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-lg font-bold tracking-tight text-slate-800 dark:text-white">EBD Request</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">Manage change requests for Engineering Breakdown (EBD) revisions</p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-2">
            <button type="button" id="btn-open-create-modal"
                    class="inline-flex items-center justify-center gap-2 px-4 h-9 bg-indigo-600 hover:bg-indigo-700 border border-transparent rounded-sm text-xs font-medium text-white active:scale-[0.98] transition-all cursor-pointer">
                <i class="fa-solid fa-plus"></i>
                New EBD Request
            </button>
        </div>
    </div>

    {{-- ===== EBD REQUESTS TABLE ===== --}}
    <x-table id="ebd-request-table">
        <thead>
            <tr class="border-b-2 border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900/30 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                <th class="px-3 py-2.5 w-12 text-center bg-slate-100/50 dark:bg-slate-900/50">#</th>
                <th class="px-3 py-2.5 bg-slate-100/50 dark:bg-slate-900/50">Request No</th>
                <th class="px-3 py-2.5 bg-slate-100/50 dark:bg-slate-900/50">Date</th>
                <th class="px-3 py-2.5 bg-slate-100/50 dark:bg-slate-900/50">Work Order</th>
                <th class="px-3 py-2.5 bg-slate-100/50 dark:bg-slate-900/50">Customer</th>
                <th class="px-3 py-2.5 bg-slate-100/50 dark:bg-slate-900/50">Model</th>
                <th class="px-3 py-2.5 text-center bg-slate-100/50 dark:bg-slate-900/50">Target EBD</th>
                <th class="px-3 py-2.5 bg-slate-100/50 dark:bg-slate-900/50">Request Type</th>
                <th class="px-3 py-2.5 text-center bg-slate-100/50 dark:bg-slate-900/50">Status</th>
                <th class="px-3 py-2.5 bg-slate-100/50 dark:bg-slate-900/50">Requester</th>
                <th class="px-3 py-2.5 text-right w-28 bg-slate-100/50 dark:bg-slate-900/50">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700/80 text-sm">
        </tbody>
    </x-table>
</div>
{{-- ===== CREATE REQUEST MODAL ===== --}}
<x-modal id="create-request-modal" title="Create New EBD Request" subtitle="Submit a change or revision request for an EBD document" maxWidth="max-w-lg">
    <form id="form-create-request" enctype="multipart/form-data">
        @csrf
        <input type="hidden" id="req-id" name="id" value="">
        <div class="space-y-4 text-xs">

            {{-- Request No & Date --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                        Request No
                    </label>
                    <input type="text" id="req-request-no" name="request_no" value="{{ $defaultRequestNo }}" readonly
                           class="w-full px-3 py-2 text-xs bg-slate-100 dark:bg-slate-900/60 border border-slate-300 dark:border-slate-600 rounded-sm font-mono text-slate-700 dark:text-slate-300 font-bold focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                        Request Date <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="request_date" required value="{{ date('Y-m-d') }}"
                           class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-sm text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                </div>
            </div>

            {{-- Work Order (SPK) Selection --}}
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">
                        Work Order (SPK) <span class="text-slate-400 font-normal normal-case">(Optional)</span>
                    </label>
                    <span id="req-wo-spinner" class="hidden text-[10px] text-indigo-600 dark:text-indigo-400">
                        <i class="fa-solid fa-spinner fa-spin"></i> Loading...
                    </span>
                </div>
                <select name="wo_id" id="req-wo-id"
                        class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-sm text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                    <option value="">— Select Work Order (Optional) —</option>
                    @foreach($workOrders as $wo)
                        <option value="{{ $wo->id }}">{{ $wo->wo_number }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Customer Selection (Full Width) --}}
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                    Customer <span class="text-rose-500">*</span>
                </label>
                <select name="customer_id" id="req-customer-id" required
                        class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-sm text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                    <option value="">— Select Customer —</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->code }} — {{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Model Selection --}}
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                    Model <span class="text-rose-500">*</span>
                </label>
                <select name="model_id" id="req-model-id" required
                        class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-sm text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                    <option value="">— Select Model —</option>
                    @foreach($models as $model)
                        <option value="{{ $model->id }}">{{ $model->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Target EBD Info Box --}}
            <div id="req-ebd-box" class="p-3 rounded-sm border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40 text-xs">
                <input type="hidden" name="ebd_header_id" id="req-ebd-header-id" value="">
                <div class="flex items-center justify-between">
                    <span class="font-semibold text-slate-700 dark:text-slate-300">
                        <i class="fa-solid fa-link text-indigo-500 mr-1.5"></i> Target EBD (Current Active)
                    </span>
                    <span id="req-ebd-badge" class="px-2 py-0.5 text-[10px] font-mono font-bold rounded-xs bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                        No EBD Linked
                    </span>
                </div>
                <p id="req-ebd-desc" class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                    Select a Work Order or Customer/Model above to automatically find the active EBD.
                </p>
            </div>

            {{-- Request Type --}}
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                    Request Type <span class="text-rose-500">*</span>
                </label>
                <select name="request_type" id="req-request-type" required
                        class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-sm text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                    <option value="Drawing / Dimension Change">Drawing / Dimension Change</option>
                    <option value="Material Spec Update">Material Spec Update</option>
                    <option value="Tooling Process Revision">Tooling Process Revision</option>
                    <option value="BOM Addition / Removal">BOM Addition / Removal</option>
                    <option value="Customer ECO / ECN">Customer ECO / ECN</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            {{-- Description --}}
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                    Change Description & Reason <span class="text-rose-500">*</span>
                </label>
                <textarea name="description" id="req-description" rows="3" required
                          placeholder="Explain what parts, dimensions, or processes need to be updated and the reason behind this request..."
                          class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-sm text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"></textarea>
            </div>

            {{-- Attachment --}}
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                    Customer Attachment / Drawing <span class="text-slate-400 font-normal normal-case">(PDF, Image, XLSX - Max 20MB)</span>
                </label>
                <input type="file" name="attachment_file" id="req-attachment-file"
                       accept=".pdf,.png,.jpg,.jpeg,.xlsx,.zip"
                       class="w-full text-xs text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-sm file:mr-3 file:py-2 file:px-3 file:rounded-none file:border-0 file:border-r file:border-slate-300 dark:file:border-slate-600 file:text-xs file:font-semibold file:bg-slate-100 dark:file:bg-slate-800 file:text-slate-700 dark:file:text-slate-200 hover:file:bg-slate-200 dark:hover:file:bg-slate-700 cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

        </div>

        <x-slot:footer>
            <button type="button" class="btn-close-modal px-4 py-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-slate-600 rounded-sm hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                Cancel
            </button>
            <button type="button" id="btn-submit-create-req" onclick="$('#form-create-request').submit()"
                    class="px-4 py-1.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-sm shadow-sm hover:shadow transition-all flex items-center gap-2 cursor-pointer">
                <span id="btn-create-req-text">Submit Request</span>
                <span id="btn-create-req-spinner" class="hidden"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Submitting...</span>
            </button>
        </x-slot:footer>
    </form>
</x-modal>

{{-- ===== VIEW DETAIL MODAL ===== --}}
<x-modal id="view-request-modal" title="EBD Request Details" subtitle="Sales Change Request details & fulfillment information" maxWidth="max-w-lg">
    <div class="space-y-3.5 text-xs">
        <div class="flex items-center justify-between pb-2 border-b border-slate-200 dark:border-slate-700">
            <span class="font-mono font-bold text-sm text-indigo-700 dark:text-indigo-400" id="view-req-no"></span>
            <span id="view-req-badge-status" class="inline-block px-2 py-0.5 text-[10px] font-bold border rounded-sm"></span>
        </div>

        <div class="grid grid-cols-2 gap-3 pb-3 border-b border-slate-100 dark:border-slate-700">
            <div>
                <span class="text-[10px] text-slate-400 uppercase font-semibold block">Work Order (SPK)</span>
                <span class="font-bold text-slate-800 dark:text-slate-200" id="view-req-wo"></span>
            </div>
            <div>
                <span class="text-[10px] text-slate-400 uppercase font-semibold block">Request Date</span>
                <span class="text-slate-700 dark:text-slate-300 font-mono" id="view-req-date"></span>
            </div>
            <div>
                <span class="text-[10px] text-slate-400 uppercase font-semibold block">Customer</span>
                <span class="text-slate-700 dark:text-slate-300 font-medium" id="view-req-customer"></span>
            </div>
            <div>
                <span class="text-[10px] text-slate-400 uppercase font-semibold block">Model</span>
                <span class="text-slate-700 dark:text-slate-300 font-medium" id="view-req-model"></span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 pb-3 border-b border-slate-100 dark:border-slate-700">
            <div>
                <span class="text-[10px] text-slate-400 uppercase font-semibold block">Base EBD Document</span>
                <div id="view-req-base-ebd" class="mt-0.5"></div>
            </div>
            <div>
                <span class="text-[10px] text-slate-400 uppercase font-semibold block">Revised EBD (Result)</span>
                <div id="view-req-revised-ebd" class="mt-0.5"></div>
            </div>
        </div>

        {{-- Unified Subject & Body Layout (No Indent) --}}
        <div class="border border-slate-200 dark:border-slate-700 rounded-sm overflow-hidden bg-slate-50/50 dark:bg-slate-900/50">
            <div class="px-3.5 py-2 bg-slate-100/70 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold text-slate-400 dark:text-slate-400 uppercase tracking-wider flex-shrink-0">Request Type :</span>
                    <span class="font-bold text-slate-800 dark:text-slate-100 text-xs" id="view-req-type"></span>
                </div>
            </div>
            <div class="p-3 text-slate-700 dark:text-slate-200 leading-relaxed font-sans text-xs whitespace-pre-line text-left" id="view-req-desc"></div>
        </div>

        <div id="view-req-attachment-container" class="hidden">
            <span class="text-[10px] text-slate-400 uppercase font-semibold block mb-1">Customer Attachment</span>
            <a id="view-req-attachment-link" href="#" target="_blank"
               class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/40 rounded-sm border border-indigo-200 dark:border-indigo-800 hover:underline">
                <i class="fa-solid fa-file-arrow-down mr-1.5"></i> Download Attachment
            </a>
        </div>

        <div id="view-req-rejection-box" class="hidden p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 text-rose-800 dark:text-rose-300 rounded-sm">
            <span class="text-[10px] uppercase font-bold block mb-0.5 text-rose-600 dark:text-rose-400">Rejection Note</span>
            <p id="view-req-rejection-note" class="text-xs"></p>
        </div>

        <div class="pt-2 text-[11px] text-slate-400 flex items-center justify-between">
            <span>Requested by: <b class="text-slate-600 dark:text-slate-300" id="view-req-by"></b></span>
            <span id="view-req-processed-by"></span>
        </div>
    </div>

    <x-slot:footer>
        <button type="button" class="btn-close-modal px-4 py-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-slate-600 rounded-sm hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer">
            Close
        </button>
    </x-slot:footer>
</x-modal>

<x-sweetalert />

@push('scripts')
<script>
$(function () {

    // =========================================================================
    // DATATABLE INITIALIZATION VIA defaultDataTable
    // =========================================================================
    defaultDataTable('#ebd-request-table', {
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("management.ebd-request.index") }}'
        },
        columns: [
            { data: 'index_num', name: 'index_num', orderable: false, searchable: false, className: 'text-center text-slate-400 font-mono text-[10px]' },
            { 
                data: 'request_no', 
                name: 'request_no', 
                className: 'font-bold font-mono text-slate-800 dark:text-slate-100',
                render: function(data, type, row) {
                    return `<button type="button" class="btn-view-detail text-left hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors cursor-pointer" data-id="${row.id}">${data}</button>`;
                }
            },
            { data: 'request_date', name: 'request_date', className: 'font-mono text-slate-600 dark:text-slate-300 text-xs' },
            { 
                data: 'wo_number', 
                name: 'wo_number',
                className: 'font-mono text-slate-700 dark:text-slate-200 font-semibold',
                render: function(data) {
                    return data && data !== '—' ? `<span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-900 rounded-xs border border-slate-200 dark:border-slate-700 text-[11px]">${data}</span>` : '<span class="text-slate-400">—</span>';
                }
            },
            { 
                data: 'customer_code', 
                name: 'customer_code',
                className: 'text-slate-700 dark:text-slate-200 font-semibold',
                render: function(data, type, row) {
                    return `<span title="${row.customer_name}">${data}</span>`;
                }
            },
            { data: 'model_name', name: 'model_name', className: 'text-slate-600 dark:text-slate-300' },
            { 
                data: 'base_ebd_revision', 
                name: 'base_ebd_revision',
                className: 'text-center font-mono',
                render: function(data, type, row) {
                    let baseHtml = row.base_ebd_url 
                        ? `<a href="${row.base_ebd_url}" target="_blank" class="inline-flex items-center px-2 py-0.5 text-[10px] font-bold border rounded-sm bg-indigo-50/80 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800 hover:underline">${data}</a>`
                        : `<span class="text-slate-400">${data}</span>`;

                    if (row.revised_ebd_url) {
                        baseHtml += ` <i class="fa-solid fa-arrow-right text-[9px] text-slate-400 mx-0.5"></i> <a href="${row.revised_ebd_url}" target="_blank" class="inline-flex items-center px-2 py-0.5 text-[10px] font-bold border rounded-sm bg-emerald-50/80 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800 hover:underline">${row.revised_ebd_revision}</a>`;
                    }
                    return baseHtml;
                }
            },
            { 
                data: 'request_type', 
                name: 'request_type',
                className: 'text-slate-600 dark:text-slate-300'
            },
            { 
                data: 'status', 
                name: 'status', 
                className: 'text-center',
                render: function(data) {
                    let cls = 'bg-slate-100 text-slate-700 border-slate-200';
                    if (data === 'Submitted') cls = 'bg-amber-100/70 text-amber-800 border-amber-200/60 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-900/30';
                    else if (data === 'In Progress') cls = 'bg-blue-100/70 text-blue-800 border-blue-200/60 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-900/30';
                    else if (data === 'Completed') cls = 'bg-emerald-100/70 text-emerald-800 border-emerald-200/60 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900/30';
                    else if (data === 'Rejected') cls = 'bg-rose-100/70 text-rose-800 border-rose-200/60 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-900/30';
                    return `<span class="inline-block px-2 py-0.5 text-[10px] font-bold border rounded-sm ${cls}">${data}</span>`;
                }
            },
            { data: 'requested_by', name: 'requested_by', className: 'text-slate-500 dark:text-slate-400 text-xs' },
            { 
                data: 'id', 
                name: 'id', 
                orderable: false, 
                searchable: false, 
                className: 'text-right',
                render: function(data, type, row) {
                    let actions = `<div class="flex justify-end gap-1.5 align-middle">`;
                    
                    // View Button
                    actions += `<button type="button" class="btn-view-detail w-6 h-6 flex items-center justify-center bg-slate-100 dark:bg-slate-700 border border-slate-300 dark:border-slate-600 hover:border-blue-400 hover:text-blue-700 text-slate-600 dark:text-slate-300 transition-colors rounded-sm cursor-pointer" title="View Detail" data-id="${row.id}"><i class="fa-solid fa-eye text-[10px]"></i></button>`;

                    // Edit Button (Only if still Submitted)
                    if (row.status === 'Submitted') {
                        actions += `<button type="button" class="btn-edit-request w-6 h-6 flex items-center justify-center bg-amber-50 hover:bg-amber-500 text-amber-700 hover:text-white dark:bg-amber-950/40 dark:text-amber-400 border border-amber-300 dark:border-amber-800 transition-colors rounded-sm cursor-pointer" title="Edit Request" data-id="${row.id}"><i class="fa-solid fa-pen-to-square text-[10px]"></i></button>`;
                    }

                    // Delete Button (If not Completed)
                    if (row.status !== 'Completed') {
                        actions += `<button type="button" class="w-6 h-6 flex items-center justify-center bg-rose-100/60 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/30 hover:bg-rose-500 hover:border-rose-500 hover:text-white text-rose-600 dark:text-rose-450 transition-colors cursor-pointer rounded-sm" title="Delete" onclick="deleteRequest(${row.id})"><i class="fa-solid fa-trash text-[10px]"></i></button>`;
                    }

                    actions += `</div>`;
                    return actions;
                }
            }
        ],
        order: [[1, 'desc']]
    });

    // =========================================================================
    // MODAL OPEN / CLOSE
    // =========================================================================
    $('#btn-open-create-modal').on('click', function () {
        $('#form-create-request')[0].reset();
        $('#req-id').val('');
        $('#req-request-no').val('{{ $defaultRequestNo }}');
        $('#btn-create-req-text').text('Submit Request');
        $('#create-request-modal h2').first().text('Create New EBD Request');
        $('#create-request-modal').removeClass('hidden').addClass('flex');

        resetEbdBox();
    });

    $(document).on('click', '.btn-edit-request', function () {
        const reqId = $(this).data('id');
        const rowData = $('#ebd-request-table').DataTable().rows().data().toArray().find(r => r.id == reqId);
        if (!rowData) return;

        $('#form-create-request')[0].reset();
        $('#req-id').val(rowData.id);
        $('#req-request-no').val(rowData.request_no);
        if (rowData.request_date_raw) {
            $('input[name="request_date"]').val(rowData.request_date_raw);
        }
        $('#req-wo-id').val(rowData.wo_id || '');
        $('#req-customer-id').val(rowData.customer_id || '');
        $('#req-model-id').val(rowData.model_id || '');
        $('#req-request-type').val(rowData.request_type);
        $('#req-description').val(rowData.description);

        $('#btn-create-req-text').text('Save Changes');
        $('#create-request-modal h2').first().text('Edit EBD Request');
        $('#create-request-modal').removeClass('hidden').addClass('flex');

        fetchActiveEbd(rowData.wo_id, rowData.customer_id, rowData.model_id);
    });

    $('.btn-close-modal').on('click', function () {
        $('.fixed.z-50').addClass('hidden').removeClass('flex');
    });

    function resetEbdBox() {
        $('#req-ebd-header-id').val('');
        $('#req-ebd-badge').text('No EBD Linked').removeClass('bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300').addClass('bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300');
        $('#req-ebd-desc').text('Select a Work Order or Customer/Model above to automatically find the active EBD.');
    }

    function fetchActiveEbd(woId, customerId, modelId) {
        $('#req-wo-spinner').removeClass('hidden');
        $.ajax({
            url: '{{ url("management/ebd-request/get-ebd-by-wo") }}/' + (woId || 'null'),
            data: { customer_id: customerId, model_id: modelId },
            success: function (res) {
                $('#req-wo-spinner').addClass('hidden');
                if (res.customer_id) $('#req-customer-id').val(res.customer_id).trigger('change.select2');
                if (res.model_id) $('#req-model-id').val(res.model_id).trigger('change.select2');

                if (res.has_ebd && res.ebd_id) {
                    $('#req-ebd-header-id').val(res.ebd_id);
                    $('#req-ebd-badge')
                        .text(res.ebd_revision + ' (Active)')
                        .removeClass('bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300')
                        .addClass('bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300');
                    $('#req-ebd-desc').html(`Target document: <b>${res.ebd_revision}</b> dated ${res.ebd_date}. Revision will advance to next version upon processing.`);
                } else {
                    resetEbdBox();
                    $('#req-ebd-desc').text('No existing EBD found for this selection. (A new Rev. 0 EBD will be created).');
                }
            },
            error: function () {
                $('#req-wo-spinner').addClass('hidden');
            }
        });
    }

    $('#req-wo-id').on('change', function () {
        const woId = $(this).val();
        if (woId) {
            fetchActiveEbd(woId, null, null);
        }
    });

    $('#req-customer-id, #req-model-id').on('change', function () {
        const woId = $('#req-wo-id').val();
        const customerId = $('#req-customer-id').val();
        const modelId = $('#req-model-id').val();
        if (!woId && customerId && modelId) {
            fetchActiveEbd(null, customerId, modelId);
        }
    });

    // Form Submit Create / Edit Request
    $('#form-create-request').on('submit', function (e) {
        e.preventDefault();
        $('#btn-create-req-text').addClass('hidden');
        $('#btn-create-req-spinner').removeClass('hidden');
        $('#btn-submit-create-req').prop('disabled', true);

        const reqId = $('#req-id').val();
        const url = reqId 
            ? `{{ url('management/ebd-request') }}/${reqId}/update`
            : `{{ route('management.ebd-request.store') }}`;

        const formData = new FormData(this);

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#btn-create-req-text').removeClass('hidden');
                $('#btn-create-req-spinner').addClass('hidden');
                $('#btn-submit-create-req').prop('disabled', false);
                $('#create-request-modal').addClass('hidden').removeClass('flex');
                showToast(res.message || 'Request saved successfully!', 'success');
                $('#ebd-request-table').DataTable().ajax.reload(null, false);
            },
            error: function (xhr) {
                $('#btn-create-req-text').removeClass('hidden');
                $('#btn-create-req-spinner').addClass('hidden');
                $('#btn-submit-create-req').prop('disabled', false);
                const res = xhr.responseJSON;
                showToast(res?.message || 'Failed to save request.', 'error');
            }
        });
    });

    // =========================================================================
    // VIEW DETAIL MODAL
    // =========================================================================
    $(document).on('click', '.btn-view-detail', function () {
        const reqId = $(this).data('id');
        const rowData = $('#ebd-request-table').DataTable().rows().data().toArray().find(r => r.id == reqId);
        if (!rowData) return;

        $('#view-req-no').text(rowData.request_no);
        $('#view-req-wo').text(rowData.wo_number);
        $('#view-req-date').text(rowData.request_date);
        $('#view-req-customer').text(`${rowData.customer_code} — ${rowData.customer_name}`);
        $('#view-req-model').text(rowData.model_name);
        $('#view-req-type').text(rowData.request_type);
        $('#view-req-desc').text(rowData.description);
        $('#view-req-by').text(rowData.requested_by);

        if (rowData.processed_by && rowData.processed_by !== '—') {
            $('#view-req-processed-by').text(`Processed by: ${rowData.processed_by} (${rowData.processed_at})`).removeClass('hidden');
        } else {
            $('#view-req-processed-by').addClass('hidden');
        }

        // Status Badge
        let statusCls = 'bg-slate-100 text-slate-700 border-slate-200';
        if (rowData.status === 'Submitted') statusCls = 'bg-amber-100/70 text-amber-800 border-amber-200/60 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-900/30';
        else if (rowData.status === 'In Progress') statusCls = 'bg-blue-100/70 text-blue-800 border-blue-200/60 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-900/30';
        else if (rowData.status === 'Completed') statusCls = 'bg-emerald-100/70 text-emerald-800 border-emerald-200/60 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900/30';
        else if (rowData.status === 'Rejected') statusCls = 'bg-rose-100/70 text-rose-800 border-rose-200/60 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-900/30';

        $('#view-req-badge-status').attr('class', `inline-block px-2 py-0.5 text-[10px] font-bold border rounded-sm mb-1 ${statusCls}`).text(rowData.status);

        // Base & Revised EBD Links
        if (rowData.base_ebd_url) {
            $('#view-req-base-ebd').html(`<a href="${rowData.base_ebd_url}" target="_blank" class="font-bold text-indigo-600 dark:text-indigo-400 hover:underline"><i class="fa-solid fa-arrow-up-right-from-square text-[10px] mr-1"></i>${rowData.base_ebd_revision}</a>`);
        } else {
            $('#view-req-base-ebd').html('<span class="text-slate-400">None</span>');
        }

        if (rowData.revised_ebd_url) {
            $('#view-req-revised-ebd').html(`<a href="${rowData.revised_ebd_url}" target="_blank" class="font-bold text-emerald-600 dark:text-emerald-400 hover:underline"><i class="fa-solid fa-arrow-up-right-from-square text-[10px] mr-1"></i>${rowData.revised_ebd_revision}</a>`);
        } else {
            $('#view-req-revised-ebd').html('<span class="text-slate-400">Pending Revision</span>');
        }

        // Attachment link
        if (rowData.attachment_url) {
            $('#view-req-attachment-container').removeClass('hidden');
            $('#view-req-attachment-link').attr('href', rowData.attachment_url);
        } else {
            $('#view-req-attachment-container').addClass('hidden');
        }

        // Rejection note
        if (rowData.status === 'Rejected' && rowData.rejection_note) {
            $('#view-req-rejection-box').removeClass('hidden');
            $('#view-req-rejection-note').text(rowData.rejection_note);
        } else {
            $('#view-req-rejection-box').addClass('hidden');
        }

        $('#view-request-modal').removeClass('hidden').addClass('flex');
    });

    // =========================================================================
    // DELETE REQUEST
    // =========================================================================
    window.deleteRequest = function (id) {
        confirmDialog({
            title: 'Delete EBD Request?',
            text: 'Are you sure you want to delete this EBD request?',
            icon: 'warning',
            confirmButtonText: 'Yes, Delete',
            confirmButtonColor: '#e11d48',
            onConfirm: function () {
                $.ajax({
                    url: `{{ url('management/ebd-request') }}/${id}/delete`,
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function (res) {
                        showToast(res.message || 'Request deleted.', 'success');
                        $('#ebd-request-table').DataTable().ajax.reload(null, false);
                    },
                    error: function (xhr) {
                        const res = xhr.responseJSON;
                        showToast(res?.message || 'Failed to delete request.', 'error');
                    }
                });
            }
        });
    };

});
</script>
@endpush
@endsection
