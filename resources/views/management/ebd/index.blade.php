@extends('layouts.app')

@section('title', 'Engineering Breakdown (EBD) · Promise Management')
@section('page_title', 'Engineering Breakdown (EBD)')
@section('header-title', 'Feasibility Study')

@section('content')
<div class="flex-1 overflow-y-auto p-4 pt-17.5 space-y-4 text-gray-900 dark:text-gray-100">

    {{-- ===== HEADER ACTIONS ===== --}}
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h2 class="text-xl xl:text-2xl font-bold text-gray-900 dark:text-gray-100 tracking-tighter leading-none">Engineering Breakdown (EBD)</h2>
            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400 font-normal">Manage engineering breakdown specifications and BOM lists.</p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-2">
            <button type="button" id="btn-open-create-modal"
                    class="inline-flex items-center justify-center gap-2 px-4 h-9 bg-emerald-600 hover:bg-emerald-700 border border-transparent rounded-xs text-xs font-medium text-white active:scale-[0.98] transition-all shadow-sm cursor-pointer">
                <i class="fa-solid fa-plus"></i>
                Add New EBD
            </button>
            <button type="button" id="btn-open-import-modal"
                    class="inline-flex items-center justify-center gap-2 px-4 h-9 bg-indigo-600 hover:bg-indigo-700 border border-transparent rounded-xs text-xs font-medium text-white active:scale-[0.98] transition-all shadow-sm cursor-pointer">
                <i class="fa-solid fa-file-import"></i>
                Import EBD File
            </button>
        </div>
    </div>

    {{-- ===== EBD HEADERS TABLE ===== --}}
    <x-table id="ebd-table">
        <thead>
            <tr class="border-b-2 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/30 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                <th class="px-3 py-2.5 w-8 text-center">#</th>
                <th class="px-3 py-2.5">WO No.</th>
                <th class="px-3 py-2.5">Customer</th>
                <th class="px-3 py-2.5">Model</th>
                <th class="px-3 py-2.5">EBD Date</th>
                <th class="px-3 py-2.5 text-center">Revision</th>
                <th class="px-3 py-2.5 text-center">Status</th>
                <th class="px-3 py-2.5">Created By</th>
                <th class="px-3 py-2.5 text-right w-32">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ebdHeaders as $index => $ebd)
                @php
                    $statusCls = match($ebd->status) {
                        'Released' => 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-900/30',
                        default    => 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-900/30',
                    };
                @endphp
                <tr class="border-b border-slate-100 dark:border-slate-700/50 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                    <td class="px-3 py-2.5 text-center text-slate-400 font-mono text-[10px]">{{ $index + 1 }}</td>
                    <td class="px-3 py-2.5 font-bold text-slate-800 dark:text-slate-100">
                        {{ $ebd->workOrder->wo_number ?? '—' }}
                    </td>
                    <td class="px-3 py-2.5 text-slate-700 dark:text-slate-200 font-semibold">
                        {{ $ebd->customer->code ?? '—' }}
                    </td>
                    <td class="px-3 py-2.5 text-slate-600 dark:text-slate-300">
                        {{ $ebd->projectModel->name ?? '—' }}
                    </td>
                    <td class="px-3 py-2.5 text-slate-600 dark:text-slate-300 font-mono text-xs">
                        {{ $ebd->date ? $ebd->date->format('d M Y') : '—' }}
                    </td>
                    <td class="px-3 py-2.5 text-center font-mono text-slate-600 dark:text-slate-300">
                        Rev. {{ $ebd->revision }}
                    </td>
                    <td class="px-3 py-2.5 text-center">
                        <span class="inline-block px-2 py-0.5 text-[10px] font-bold border rounded-xs {{ $statusCls }}">
                            {{ $ebd->status }}
                        </span>
                    </td>
                    <td class="px-3 py-2.5 text-slate-500 dark:text-slate-400 text-xs">
                        {{ $ebd->created_by ?? '—' }}
                    </td>
                    <td class="px-3 py-2.5 text-right flex justify-end gap-1.5 align-middle">
                        {{-- View --}}
                        <a href="{{ route('management.ebd.show', $ebd->id) }}"
                           title="View BOM Detail"
                           class="w-6 h-6 flex items-center justify-center bg-slate-100 dark:bg-slate-700 border border-slate-300 dark:border-slate-600 hover:border-blue-400 hover:text-blue-700 text-slate-600 dark:text-slate-300 transition-colors">
                            <i class="fa-solid fa-eye text-[10px]"></i>
                        </a>
                        {{-- Delete --}}
                        <button type="button"
                                title="Delete EBD"
                                onclick="confirmDeleteEbd({{ $ebd->id }})"
                                class="w-6 h-6 flex items-center justify-center bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/30 hover:bg-rose-500 hover:border-rose-500 hover:text-white text-rose-600 dark:text-rose-400 transition-colors cursor-pointer">
                            <i class="fa-solid fa-trash text-[10px]"></i>
                        </button>
                    </td>
                </tr>
            @empty
                {{-- Handled by DataTable emptyTable --}}
            @endforelse
        </tbody>
    </x-table>
</div>

{{-- ===== IMPORT MODAL ===== --}}
<div id="import-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-2xl w-full max-w-lg mx-4 animate-fade-in">

        {{-- Modal Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-700">
            <div>
                <h2 class="text-sm font-bold text-slate-800 dark:text-white">Import EBD File</h2>
                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Upload an XLSX file to create a new EBD document</p>
            </div>
            <button type="button" id="btn-close-import-modal"
                    class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer">
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
                            class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        <option value="">— No Work Order —</option>
                        @foreach($workOrders as $wo)
                            <option value="{{ $wo->id }}">{{ $wo->wo_number }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Customer --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                        Customer <span class="text-slate-400 font-normal normal-case">(Optional)</span>
                    </label>
                    <select name="customer_id" id="input-customer-id"
                            class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        <option value="">— Select Customer —</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->code }} — {{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Model --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                        Model <span class="text-slate-400 font-normal normal-case">(Optional)</span>
                    </label>
                    <select name="model_id" id="input-model-id"
                            class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
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
                               class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                            Revision
                        </label>
                        <input type="text" name="revision" id="input-revision"
                               value="0" placeholder="e.g. 0, 1, A"
                               class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                    </div>
                </div>

                {{-- File Upload --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                        EBD File (XLSX) <span class="text-rose-500">*</span>
                    </label>
                    <div id="drop-zone"
                         class="relative border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-lg p-5 text-center cursor-pointer hover:border-indigo-400 hover:bg-indigo-50/30 dark:hover:bg-indigo-950/20 transition-all group">
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
                <div id="importResult" class="hidden text-xs rounded-lg border"></div>

            </div>

            {{-- Modal Footer --}}
            <div class="px-5 py-3.5 bg-slate-50 dark:bg-slate-900/30 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-2 rounded-b-xl">
                <button type="button" id="btn-cancel-import"
                        class="px-4 py-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                    Cancel
                </button>
                <button type="submit" id="btn-submit-import"
                        class="px-4 py-1.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm hover:shadow transition-all flex items-center gap-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
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

            </div>
        </form>
    </div>
</div>

{{-- ===== CREATE EBD MODAL (MANUAL) ===== --}}
<div id="create-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-2xl w-full max-w-lg mx-4 animate-fade-in">
        {{-- Modal Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-700">
            <div>
                <h2 class="text-sm font-bold text-slate-800 dark:text-white">Create New EBD</h2>
                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Add a new Engineering Breakdown header manually</p>
            </div>
            <button type="button" id="btn-close-create-modal"
                    class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        {{-- Modal Body --}}
        <form id="form-create-ebd" action="{{ route('management.ebd.store') }}" method="POST">
            @csrf
            <div class="px-5 py-4 space-y-4">
                {{-- WO Selection --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                        Work Order (SPK) <span class="text-slate-400 font-normal normal-case">(Optional)</span>
                    </label>
                    <select name="wo_id" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        <option value="">— No Work Order —</option>
                        @foreach($workOrders as $wo)
                            <option value="{{ $wo->id }}">{{ $wo->wo_number }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Customer --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                        Customer <span class="text-slate-400 font-normal normal-case">(Optional)</span>
                    </label>
                    <select name="customer_id" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        <option value="">— Select Customer —</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->code }} — {{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Model --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                        Model <span class="text-slate-400 font-normal normal-case">(Optional)</span>
                    </label>
                    <select name="model_id" class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
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
                        <input type="date" name="date" required value="{{ date('Y-m-d') }}"
                               class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                            Revision
                        </label>
                        <input type="text" name="revision" value="0" placeholder="e.g. 0, 1, A"
                               class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-5 py-3.5 bg-slate-50 dark:bg-slate-900/30 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-2 rounded-b-xl">
                <button type="button" id="btn-cancel-create"
                        class="px-4 py-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-1.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm hover:shadow transition-all flex items-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-floppy-disk text-[10px]"></i>
                    <span>Save EBD</span>
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
        order: [[0, 'asc']],
        language: {
            emptyTable: `
                <div class="py-16 flex flex-col items-center justify-center text-center w-full">
                    <div>
                        <i class="fa-solid fa-file-circle-plus text-3xl text-slate-300 dark:text-slate-600 m-4"></i>
                    </div>
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-widest mb-2">No EBD Records Found</h4>
                    <p class="text-xs text-gray-400 dark:text-gray-500 max-w-xs mx-auto font-medium leading-relaxed">
                        Click "Add New EBD" or "Import EBD File" to create your first Engineering Breakdown document.
                    </p>
                </div>
            `
        }
    });

    // =========================================================================
    // MODALS — Open / Close
    // =========================================================================
    function openImportModal() {
        $('#import-modal').removeClass('hidden').addClass('flex');
        $('#importResult').addClass('hidden').removeClass('bg-rose-50 text-rose-900 border-rose-100 p-4').html('');
    }

    function closeImportModal() {
        $('#import-modal').addClass('hidden').removeClass('flex');
        $('#form-import-ebd')[0].reset();
        resetDropZone();
        setSubmitLoading(false);
        $('#importResult').addClass('hidden').removeClass('bg-rose-50 text-rose-900 border-rose-100 p-4').html('');
    }

    function openCreateModal() {
        $('#create-modal').removeClass('hidden').addClass('flex');
    }

    function closeCreateModal() {
        $('#create-modal').addClass('hidden').removeClass('flex');
        $('#form-create-ebd')[0].reset();
    }

    $('#btn-open-import-modal').on('click', openImportModal);
    $('#btn-close-import-modal, #btn-cancel-import').on('click', closeImportModal);

    $('#btn-open-create-modal').on('click', openCreateModal);
    $('#btn-close-create-modal, #btn-cancel-create').on('click', closeCreateModal);

    // Close on backdrop click
    $('#import-modal').on('click', function (e) {
        if ($(e.target).is('#import-modal')) closeImportModal();
    });
    $('#create-modal').on('click', function (e) {
        if ($(e.target).is('#create-modal')) closeCreateModal();
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
