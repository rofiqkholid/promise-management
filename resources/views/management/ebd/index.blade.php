@extends('layouts.app')

@section('title', 'Engineering Breakdown (EBD) · Promise Management')
@section('page_title', 'Engineering Breakdown (EBD)')
@section('header-title', 'Feasibility Study')



@section('content')
<div class="flex-1 overflow-y-auto p-4 pt-17.5 space-y-4 transition-colors duration-200">

    {{-- ===== HEADER ACTIONS ===== --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">Engineering Breakdown (EBD)</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Manage engineering breakdown specifications and BOM lists</p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-2">
            <button type="button" id="btn-open-import-modal"
                    class="inline-flex items-center justify-center gap-2 px-4 h-9 bg-indigo-600 hover:bg-indigo-700 border border-transparent rounded-xs text-xs font-medium text-white active:scale-[0.98] transition-all cursor-pointer">
                <i class="fa-solid fa-file-import"></i>
                Import EBD File
            </button>
        </div>
    </div>

    {{-- ===== EBD HEADERS TABLE ===== --}}
    <x-table id="ebd-table">
        <thead>
            <tr class="border-b-2 border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900/30 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                <th class="px-3 py-2.5 w-12 text-center bg-slate-100/50 dark:bg-slate-900/50">#</th>
                <th class="px-3 py-2.5 bg-slate-100/50 dark:bg-slate-900/50">WO No.</th>
                <th class="px-3 py-2.5 bg-slate-100/50 dark:bg-slate-900/50">Customer</th>
                <th class="px-3 py-2.5 bg-slate-100/50 dark:bg-slate-900/50">Model</th>
                <th class="px-3 py-2.5 bg-slate-100/50 dark:bg-slate-900/50">EBD Date</th>
                <th class="px-3 py-2.5 text-center bg-slate-100/50 dark:bg-slate-900/50">Revision</th>
                <th class="px-3 py-2.5 text-center bg-slate-100/50 dark:bg-slate-900/50">Status</th>
                <th class="px-3 py-2.5 bg-slate-100/50 dark:bg-slate-900/50">Created By</th>
                <th class="px-3 py-2.5 text-right w-32 bg-slate-100/50 dark:bg-slate-900/50">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700/80 text-sm">
        </tbody>
    </x-table>
</div>

{{-- ===== IMPORT MODAL ===== --}}
<div id="import-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xs shadow-2xl w-full max-w-lg mx-4 animate-fade-in">

        {{-- Modal Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-700">
            <div>
                <h2 class="text-sm font-bold text-slate-800 dark:text-white">Import EBD File</h2>
                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Upload an XLSX file to create a new EBD document</p>
            </div>
            <button type="button" id="btn-close-import-modal"
                    class="w-7 h-7 flex items-center justify-center rounded-xs text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        {{-- Modal Body --}}
        <form id="form-import-ebd" enctype="multipart/form-data">
            @csrf
            <div class="px-5 py-4 space-y-4">

                {{-- WO Selection --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                        Work Order (SPK) <span class="text-slate-400 font-normal normal-case">(Optional)</span>
                    </label>
                    <select name="wo_id" id="input-wo-id"
                            class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        <option value="">— No Work Order —</option>
                        @foreach($workOrders as $wo)
                            <option value="{{ $wo->id }}"
                                    data-customer-id="{{ $wo->inquiry->customer_id ?? '' }}"
                                    data-model-id="{{ $wo->inquiry->model_id ?? '' }}">
                                {{ $wo->wo_number }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Customer --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                        Customer <span class="text-rose-500">*</span>
                    </label>
                    <select name="customer_id" id="input-customer-id" required
                            class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        <option value="">— Select Customer —</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->code }} — {{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Model --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                        Model <span class="text-rose-500">*</span>
                    </label>
                    <select name="model_id" id="input-model-id" required
                            class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        <option value="">— Select Model —</option>
                        @foreach($models as $model)
                            <option value="{{ $model->id }}">{{ $model->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Date & Revision --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                            EBD Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="date" id="input-date" required
                               value="{{ date('Y-m-d') }}"
                               class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                            Revision
                        </label>
                        <input type="text" name="revision" id="input-revision"
                               value="0" placeholder="e.g. 0, 1, A"
                               class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                    </div>
                </div>

                {{-- File Upload --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                        EBD File (XLSX) <span class="text-rose-500">*</span>
                    </label>
                    <div id="drop-zone"
                         class="relative border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-xs p-5 text-center cursor-pointer hover:border-indigo-400 hover:bg-indigo-50/30 dark:hover:bg-indigo-950/20 transition-all group">
                        <input type="file" name="file_ebd" id="input-file-ebd" required
                               accept=".xlsx,.zip"
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                        <div id="drop-zone-content">
                            <i class="fa-solid fa-cloud-arrow-up text-2xl text-slate-300 dark:text-slate-600 group-hover:text-indigo-400 transition-colors mb-2 block"></i>
                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                                Drop your XLSX file here, or <span class="text-indigo-500">browse</span>
                            </p>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">Accepts .xlsx or .zip (max 20 MB)</p>
                        </div>
                        <div id="file-selected-info" class="hidden">
                            <i class="fa-solid fa-file-excel text-2xl text-emerald-500 mb-1 block"></i>
                            <p class="text-xs font-bold text-slate-700 dark:text-slate-200" id="file-selected-name"></p>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500" id="file-selected-size"></p>
                        </div>
                    </div>
                </div>

                {{-- Import Result Alert Container (Errors/Warnings) --}}
                <div id="importResult" class="hidden text-xs rounded-xs border"></div>

            </div>

            {{-- Modal Footer --}}
            <div class="px-5 py-3.5 bg-slate-50 dark:bg-slate-900/30 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-2 rounded-b-xs">
                <button type="button" id="btn-cancel-import"
                        class="px-4 py-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-slate-600 rounded-xs hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                    Cancel
                </button>
                <button type="submit" id="btn-submit-import"
                        class="px-4 py-1.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xs shadow-sm hover:shadow transition-all flex items-center gap-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                    <i class="fa-solid fa-file-import text-[10px]"></i>
                    <span id="btn-submit-text">Start Import</span>
                    <span id="btn-submit-spinner" class="hidden">
                        <i class="fa-solid fa-spinner animate-spin text-[10px]"></i>
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ===== EDIT MODAL ===== --}}
<div id="edit-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xs shadow-2xl w-full max-w-lg mx-4 animate-fade-in">
        {{-- Modal Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-700">
            <div>
                <h2 class="text-sm font-bold text-slate-850 dark:text-white">Edit EBD Document</h2>
                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Update EBD header metadata</p>
            </div>
            <button type="button" id="btn-close-edit-modal"
                    class="w-7 h-7 flex items-center justify-center rounded-xs text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        {{-- Modal Body --}}
        <form id="form-edit-ebd">
            @csrf
            <input type="hidden" id="edit-ebd-id">
            <div class="px-5 py-4 space-y-4">
                {{-- WO Selection --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                        Work Order (SPK) <span class="text-slate-400 font-normal normal-case">(Optional)</span>
                    </label>
                    <select name="wo_id" id="edit-wo-id"
                            class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        <option value="">— No Work Order —</option>
                        @foreach($workOrders as $wo)
                            <option value="{{ $wo->id }}"
                                    data-customer-id="{{ $wo->inquiry->customer_id ?? '' }}"
                                    data-model-id="{{ $wo->inquiry->model_id ?? '' }}">
                                {{ $wo->wo_number }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Customer --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                        Customer <span class="text-rose-500">*</span>
                    </label>
                    <select name="customer_id" id="edit-customer-id" required
                            class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        <option value="">— Select Customer —</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->code }} — {{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Model --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                        Model <span class="text-rose-500">*</span>
                    </label>
                    <select name="model_id" id="edit-model-id" required
                            class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        <option value="">— Select Model —</option>
                        @foreach($models as $model)
                            <option value="{{ $model->id }}">{{ $model->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Date & Revision --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                            EBD Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="date" id="edit-date" required
                               class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                            Revision <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="revision" id="edit-revision" required
                               placeholder="e.g. 0, 1, A"
                               class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-5 py-3.5 bg-slate-50 dark:bg-slate-900/30 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-2 rounded-b-xs">
                <button type="button" id="btn-cancel-edit"
                        class="px-4 py-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-slate-600 rounded-xs hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                    Cancel
                </button>
                <button type="submit" id="btn-submit-edit"
                        class="px-4 py-1.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xs shadow-sm hover:shadow transition-all flex items-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-floppy-disk text-[10px]"></i>
                    <span>Save Changes</span>
                </button>
            </div>
        </form>
    </div>
</div>

<x-sweetalert />

@push('scripts')
<script>
$(function () {

    // =========================================================================
    // DATATABLE INIT
    // =========================================================================
    defaultDataTable('#ebd-table', {
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("management.ebd.index") }}'
        },
        columns: [
            { data: 'index_num', name: 'index_num', orderable: false, searchable: false, className: 'text-center text-slate-400 font-mono text-[10px]' },
            { 
                data: 'wo_number', 
                name: 'wo_number',
                className: 'font-bold text-slate-800 dark:text-slate-100'
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
            { data: 'date', name: 'date', className: 'text-slate-600 dark:text-slate-300 font-mono text-xs' },
            { 
                data: 'revision', 
                name: 'revision', 
                className: 'text-center font-mono text-slate-600 dark:text-slate-300',
                render: function(data) {
                    return 'Rev. ' + data;
                }
            },
            { 
                data: 'status', 
                name: 'status', 
                className: 'text-center',
                render: function(data) {
                    let statusCls = data === 'Released' 
                        ? 'bg-emerald-100/70 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border-emerald-200/60 dark:border-emerald-900/30'
                        : 'bg-blue-100/70 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400 border-blue-200/60 dark:border-blue-900/30';
                    return `<span class="inline-block px-2 py-0.5 text-[10px] font-bold border rounded-xs ${statusCls}">${data}</span>`;
                }
            },
            { data: 'created_by', name: 'created_by', className: 'text-slate-500 dark:text-slate-400 text-xs' },
            { 
                data: 'id', 
                name: 'id', 
                orderable: false, 
                searchable: false, 
                className: 'text-right',
                render: function(data, type, row) {
                    return `<div class="flex justify-end gap-1.5 align-middle">
                        <a href="${row.show_url}"
                           title="View BOM Detail"
                           class="w-6 h-6 flex items-center justify-center bg-slate-100 dark:bg-slate-700 border border-slate-300 dark:border-slate-600 hover:border-blue-400 hover:text-blue-700 text-slate-600 dark:text-slate-300 transition-colors rounded-xs">
                            <i class="fa-solid fa-eye text-[10px]"></i>
                        </a>
                        <button type="button"
                                title="Edit EBD Header"
                                onclick="openEditModal(${row.id}, ${row.wo_id || 'null'}, ${row.customer_id || 'null'}, ${row.model_id || 'null'}, '${row.date_raw || ''}', '${row.revision || '0'}')"
                                class="w-6 h-6 flex items-center justify-center bg-slate-100 dark:bg-slate-700 border border-slate-300 dark:border-slate-600 hover:border-indigo-400 hover:text-indigo-700 text-slate-600 dark:text-slate-300 transition-colors rounded-xs cursor-pointer">
                            <i class="fa-solid fa-pencil text-[10px]"></i>
                        </button>
                        <button type="button"
                                title="Delete EBD"
                                onclick="confirmDeleteEbd(${data})"
                                class="w-6 h-6 flex items-center justify-center bg-rose-100/60 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/30 hover:bg-rose-500 hover:border-rose-500 hover:text-white text-rose-600 dark:text-rose-450 transition-colors cursor-pointer rounded-xs">
                            <i class="fa-solid fa-trash text-[10px]"></i>
                        </button>
                    </div>`;
                }
            }
        ],
        order: [[1, 'desc']],
        language: {
            emptyTable: `
                <div class="py-16 flex flex-col items-center justify-center text-center w-full">
                    <div>
                        <i class="fa-solid fa-file-circle-plus text-3xl text-slate-300 dark:text-slate-600 m-4"></i>
                    </div>
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-widest mb-2">No EBD Records Found</h4>
                    <p class="text-xs text-gray-400 dark:text-gray-500 max-w-xs mx-auto font-medium leading-relaxed">
                        Click "Import EBD File" to upload your first Engineering Breakdown document.
                    </p>
                </div>
            `
        }
    });

    // =========================================================================
    // MODAL — Open / Close
    // =========================================================================
    function openImportModal() {
        $('#import-modal').removeClass('hidden').addClass('flex');
        $('#importResult').addClass('hidden').removeClass('bg-rose-50 text-rose-900 border-rose-100 p-4').html('');
        
        $('#input-wo-id').select2({ dropdownParent: $('#import-modal'), width: '100%' });
        $('#input-customer-id').select2({ dropdownParent: $('#import-modal'), width: '100%' });
        $('#input-model-id').select2({ dropdownParent: $('#import-modal'), width: '100%' });
    }

    function closeImportModal() {
        $('#import-modal').addClass('hidden').removeClass('flex');
        $('#form-import-ebd')[0].reset();
        resetDropZone();
        setSubmitLoading(false);
        $('#importResult').addClass('hidden').removeClass('bg-rose-50 text-rose-900 border-rose-100 p-4').html('');
    }

    $('#btn-open-import-modal').on('click', openImportModal);
    $('#btn-close-import-modal, #btn-cancel-import').on('click', closeImportModal);

    // Close on backdrop click
    $('#import-modal').on('click', function (e) {
        if ($(e.target).is('#import-modal')) closeImportModal();
    });

    // =========================================================================
    // FILE DROP ZONE
    // =========================================================================
    $('#input-file-ebd').on('change', function () {
        const file = this.files[0];
        if (!file) { resetDropZone(); return; }

        const sizeMB = (file.size / 1024 / 1024).toFixed(2);
        $('#drop-zone-content').addClass('hidden');
        $('#file-selected-info').removeClass('hidden');
        $('#file-selected-name').text(file.name);
        $('#file-selected-size').text(sizeMB + ' MB');
    });

    function resetDropZone() {
        $('#drop-zone-content').removeClass('hidden');
        $('#file-selected-info').addClass('hidden');
        $('#file-selected-name').text('');
        $('#file-selected-size').text('');
    }

    // =========================================================================
    // SUBMIT — Import Form (AJAX)
    // =========================================================================
    // SUBMIT — Import Form (AJAX)
    // =========================================================================
    function setSubmitLoading(state) {
        if (state) {
            $('#btn-submit-text').text('Importing...');
            $('#btn-submit-spinner').removeClass('hidden');
            $('#btn-submit-import').prop('disabled', true);
        } else {
            $('#btn-submit-text').text('Start Import');
            $('#btn-submit-spinner').addClass('hidden');
            $('#btn-submit-import').prop('disabled', false);
        }
    }

    $('#form-import-ebd').on('submit', function (e) {
        e.preventDefault();
        setSubmitLoading(true);
        $('#importResult').addClass('hidden').removeClass('bg-rose-50 text-rose-900 border-rose-100 p-4').html('');

        const formData = new FormData(this);

        $.ajax({
            url: '{{ route("management.ebd.import") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                closeImportModal();
                showToast(response.message || 'EBD imported successfully!', 'success');
                // Redirect to the new EBD show page after short delay
                setTimeout(function () {
                    if (response.id) {
                        window.location.href = '{{ url("management/ebd") }}/' + response.id;
                    } else {
                        window.location.reload();
                    }
                }, 1200);
            },
            error: function (xhr) {
                setSubmitLoading(false);
                const res = xhr.responseJSON;
                let errorHtml = `<div class="font-bold mb-1"><i class="fa-solid fa-circle-exclamation mr-1"></i> ${res?.message || 'Import failed. Please check the file format.'}</div>`;

                if (res?.errors && Array.isArray(res.errors)) {
                    errorHtml += '<ul class="list-disc pl-5 mt-2 space-y-1">';
                    res.errors.forEach(function (err) {
                        if (err.errors && Array.isArray(err.errors)) {
                            err.errors.forEach(function (msg) {
                                errorHtml += `<li>Row ${err.row || '?'}: ${msg}</li>`;
                            });
                        }
                    });
                    errorHtml += '</ul>';
                }

                $('#importResult')
                    .removeClass('hidden')
                    .addClass('bg-rose-50 text-rose-900 border-rose-100 p-4')
                    .html(errorHtml);

                showToast('Import failed - check error details', 'error');
            }
        });
    });

    // Auto select customer and model based on chosen WO in Import Modal
    $('#input-wo-id').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const customerId = selectedOption.data('customer-id');
        const modelId = selectedOption.data('model-id');
        if (customerId) $('#input-customer-id').val(customerId).trigger('change.select2');
        if (modelId) $('#input-model-id').val(modelId).trigger('change.select2');
    });

    // Auto select customer and model based on chosen WO in Edit Modal
    $('#edit-wo-id').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const customerId = selectedOption.data('customer-id');
        const modelId = selectedOption.data('model-id');
        if (customerId) $('#edit-customer-id').val(customerId).trigger('change.select2');
        if (modelId) $('#edit-model-id').val(modelId).trigger('change.select2');
    });

    // =========================================================================
    // EDIT MODAL ACTIONS
    // =========================================================================
    window.openEditModal = function (id, woId, customerId, modelId, dateRaw, revision) {
        $('#form-edit-ebd')[0].reset();
        $('#edit-ebd-id').val(id);
        
        $('#edit-modal').removeClass('hidden').addClass('flex');

        $('#edit-wo-id').select2({ dropdownParent: $('#edit-modal'), width: '100%' });
        $('#edit-customer-id').select2({ dropdownParent: $('#edit-modal'), width: '100%' });
        $('#edit-model-id').select2({ dropdownParent: $('#edit-modal'), width: '100%' });

        $('#edit-wo-id').val(woId || '').trigger('change.select2');
        $('#edit-customer-id').val(customerId || '').trigger('change.select2');
        $('#edit-model-id').val(modelId || '').trigger('change.select2');

        $('#edit-date').val(dateRaw || '');
        $('#edit-revision').val(revision || '0');
    };

    window.closeEditModal = function () {
        $('#edit-modal').addClass('hidden').removeClass('flex');
        $('#form-edit-ebd')[0].reset();
    };

    $('#btn-close-edit-modal, #btn-cancel-edit').on('click', closeEditModal);

    // Close edit modal on backdrop click
    $('#edit-modal').on('click', function (e) {
        if ($(e.target).is('#edit-modal')) closeEditModal();
    });

    $('#form-edit-ebd').on('submit', function (e) {
        e.preventDefault();
        const id = $('#edit-ebd-id').val();
        
        $.ajax({
            url: `{{ url('management/ebd') }}/${id}/update`,
            type: 'POST',
            data: $(this).serialize(),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                closeEditModal();
                showToast(response.message || 'EBD header updated successfully!', 'success');
                // Reload DataTable
                $('#ebd-table').DataTable().ajax.reload(null, false);
            },
            error: function (xhr) {
                const res = xhr.responseJSON;
                showToast(res?.message || 'Failed to update EBD header.', 'error');
            }
        });
    });

    // =========================================================================
    // DELETE CONFIRM
    // =========================================================================
    window.confirmDeleteEbd = function (ebdId) {
        confirmDialog({
            title: 'Delete EBD Record?',
            text: 'This will permanently delete the EBD header along with all its BOM items, tooling processes, and add-processes. This action cannot be undone.',
            icon: 'warning',
            confirmButtonText: 'Yes, Delete',
            confirmButtonColor: '#e11d48',
            onConfirm: function () {
                $.ajax({
                    url: '{{ url("management/ebd") }}/' + ebdId + '/delete',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        showToast(response.message || 'EBD deleted successfully.', 'success');
                        setTimeout(() => window.location.reload(), 1200);
                    },
                    error: function (xhr) {
                        const res = xhr.responseJSON;
                        showToast(res?.message || 'Failed to delete EBD.', 'error');
                    }
                });
            }
        });
    };

});
</script>
@endpush
@endsection
