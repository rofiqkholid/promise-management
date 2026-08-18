@extends('layouts.app')

@section('title', 'Supplier Quotation Comparison · Promise Management')
@section('page_title', 'Supplier Quotation Comparison')
@section('header-title', 'Cost Comparison')

@section('content')
<div class="flex-1 overflow-y-auto p-4 pt-17.5 space-y-4 transition-colors duration-200">
    
    {{-- ===== HEADER ACTIONS ===== --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">Supplier Quotation Comparison</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">List of SPK 2 Tooling Cost Work Orders with Approved status ready for supplier quotation comparison</p>
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

    {{-- ===== QUOTATION TOOLING TABLE ===== --}}
    <x-table id="quotation-tooling-table">
        <thead>
            <tr class="border-b-2 border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900/30 text-[10px] font-medium text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                <th class="px-3 py-2.5 w-12 text-center bg-slate-100/50 dark:bg-slate-900/50">#</th>
                <th class="px-3 py-2.5 bg-slate-100/50 dark:bg-slate-900/50">WO No.</th>
                <th class="px-3 py-2.5 bg-slate-100/50 dark:bg-slate-900/50">Customer / Model</th>
                <th class="px-3 py-2.5 bg-slate-100/50 dark:bg-slate-900/50">EBD Reference</th>
                <th class="px-3 py-2.5 text-center bg-slate-100/50 dark:bg-slate-900/50">Status</th>
                <th class="px-3 py-2.5 text-center bg-slate-100/50 dark:bg-slate-900/50">Supplier Quotes</th>
                <th class="px-3 py-2.5 text-right w-48 bg-slate-100/50 dark:bg-slate-900/50">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700/80 text-sm font-normal">
            {{-- Populated via DataTables Server-Side --}}
        </tbody>
    </x-table>
</div>

<!-- Modal: Download Injected EBD Dynamic Template -->
<div id="exportDynamicEbdModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
    <div class="bg-white dark:bg-slate-800 w-full max-w-lg border border-slate-200 dark:border-slate-700 shadow-2xl p-6 relative">
        <button onclick="closeDynamicEbdModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 dark:hover:text-white">
            <i class="fa-solid fa-xmark text-lg"></i>
        </button>

        <h2 class="text-lg font-bold text-slate-800 dark:text-white mb-1 flex items-center gap-2">
            <i class="fa-solid fa-file-invoice text-blue-600"></i> Download Form Customer (Injected Data EBD)
        </h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Pilih template master milik Customer/Vendor. Sistem akan menyuntikkan data EBD & Work Order ke dalam sel template yang telah di-mapping.</p>

        <form id="exportDynamicEbdForm" method="GET" action="" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Pilih Layout Template Master Customer <span class="text-rose-500">*</span></label>
                <select name="template_id" id="dynamicTemplateSelect" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500">
                    <option value="">-- Pilih Template Customer / Vendor --</option>
                    @foreach($templates ?? [] as $tpl)
                        <option value="{{ $tpl->id }}">{{ $tpl->template_name }} (Rev {{ $tpl->revision ?? '0' }}) [{{ strtoupper($tpl->template_type) }}]</option>
                    @endforeach
                </select>
                <p class="text-[11px] text-slate-400 mt-1">Logo, border, formula, dan layout master file customer akan dipertahankan 100%.</p>
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-200 dark:border-slate-700">
                <button type="button" onclick="closeDynamicEbdModal()" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-medium hover:bg-slate-50">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium flex items-center gap-1.5">
                    <i class="fa-solid fa-cloud-arrow-down text-xs"></i> Download Form Customer (.xlsx)
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openDynamicEbdModal(downloadUrl) {
        document.getElementById('exportDynamicEbdForm').action = downloadUrl;
        document.getElementById('exportDynamicEbdModal').classList.remove('hidden');
    }

    function closeDynamicEbdModal() {
        document.getElementById('exportDynamicEbdModal').classList.add('hidden');
    }

    $(function() {
        defaultDataTable('#quotation-tooling-table', {
            processing: true,
            serverSide: true,
            order: [[1, 'desc']],
            ajax: "{{ route('management.tooling-quotation.index') }}",
            columns: [
                { data: 'index_num', orderable: false, searchable: false, className: 'text-center text-slate-400 font-mono text-xs font-normal' },
                { data: 'wo_number', orderable: true, searchable: true, className: 'font-normal text-slate-800 dark:text-slate-100' },
                { data: 'customer_model', orderable: false, searchable: true },
                { data: 'ebd_ref', orderable: false, searchable: true },
                { 
                    data: 'status', 
                    orderable: true, 
                    searchable: false, 
                    className: 'text-center',
                    render: function(data) {
                        return `<span class="inline-block px-2.5 py-0.5 text-[10px] font-medium border rounded-xs bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-900/30">${data}</span>`;
                    }
                },
                { data: 'quotation_count', orderable: false, searchable: false, className: 'text-center font-mono font-normal text-indigo-600 dark:text-indigo-400' },
                {
                    data: 'actions',
                    orderable: false,
                    searchable: false,
                    className: 'text-right',
                    render: function(data, type, row) {
                        return `
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="${row.download_template_url}" title="Download Standard Excel Template"
                                   class="inline-flex items-center justify-center gap-1 px-2.5 h-7 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xs text-[11px] font-normal transition-all active:scale-98">
                                    <i class="fa-solid fa-file-excel text-[10px]"></i> Standard Template
                                </a>
                                <button type="button" onclick="openDynamicEbdModal('${row.download_template_url}')" title="Download Customer Form with Injected EBD Data"
                                   class="inline-flex items-center justify-center gap-1 px-2.5 h-7 bg-blue-600 hover:bg-blue-700 text-white rounded-xs text-[11px] font-normal transition-all active:scale-98 cursor-pointer">
                                    <i class="fa-solid fa-file-invoice text-[10px]"></i> Form Customer (Injected EBD)
                                </button>
                                <a href="${row.compare_url}" title="View Detail Comparison"
                                   class="inline-flex items-center justify-center gap-1 px-2.5 h-7 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xs text-[11px] font-normal transition-all active:scale-98">
                                    <i class="fa-solid fa-code-compare text-[10px]"></i> Detail Comparison
                                </a>
                            </div>
                        `;
                    }
                }
            ],
            language: {
                emptyTable: `
                    <div class="py-16 flex flex-col items-center justify-center text-center w-full">
                        <div>
                            <i class="fa-solid fa-folder-open text-3xl text-slate-300 dark:text-slate-600 m-4"></i>
                        </div>
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-widest mb-2">No Approved SPK 2 Tooling Cost Found</h4>
                        <p class="text-xs text-gray-400 dark:text-gray-500 max-w-xs mx-auto font-medium leading-relaxed">Only Work Orders with "Approved" or "Released" status will appear here for supplier quotation comparison.</p>
                    </div>
                `
            }
        });
    });
</script>
@endpush
@endsection
