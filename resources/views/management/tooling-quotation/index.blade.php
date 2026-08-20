@extends('layouts.app')

@section('title', 'Supplier Quotation Comparison · Promise Management')
@section('page_title', 'Supplier Quotation Comparison')
@section('header-title', 'Cost Comparison')

@section('content')
<div class="flex-1 overflow-y-auto p-4 pt-17.5 space-y-5 transition-colors duration-200">
    
    {{-- ===== HEADER ACTIONS ===== --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-lg font-bold tracking-tight text-slate-800 dark:text-white">Tooling Quotation Comparison</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">List of Engineering Breakdown Data (EBD) for Customer & Supplier Tooling Quotation comparison</p>
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
    <x-table id="quotation-tooling-table" class="w-full text-xs text-left border-collapse">
        <thead>
            <tr class="border-b-2 border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900 text-[10px] font-extrabold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                <th class="px-3 py-2.5 w-12 text-center border-r border-slate-200 dark:border-slate-700">No.</th>
                <th class="px-4 py-2.5 border-r border-slate-200 dark:border-slate-700">EBD Reference</th>
                <th class="px-4 py-2.5 border-r border-slate-200 dark:border-slate-700">Customer / Project Model</th>
                <th class="px-3 py-2.5 border-r border-slate-200 dark:border-slate-700">Work Order Ref</th>
                <th class="px-3 py-2.5 text-center border-r border-slate-200 dark:border-slate-700">Status</th>
                <th class="px-3 py-2.5 text-center border-r border-slate-200 dark:border-slate-700">Quotes (Cust / Supp)</th>
                <th class="px-3 py-2.5 text-center w-28">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            {{-- Populated via DataTables Server-Side --}}
        </tbody>
    </x-table>
</div>

<!-- Modal: Download Injected EBD Dynamic Template -->
<div id="exportDynamicEbdModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
    <div class="bg-white dark:bg-slate-800 w-full max-w-lg border border-slate-200 dark:border-slate-700 rounded-sm p-5 relative shadow-xl">
        <button onclick="closeDynamicEbdModal()" class="absolute top-3.5 right-3.5 text-slate-400 hover:text-slate-600 dark:hover:text-white cursor-pointer">
            <i class="fa-solid fa-xmark text-base"></i>
        </button>

        <h2 class="text-sm font-bold text-slate-800 dark:text-white mb-1 flex items-center gap-2">
            <i class="fa-solid fa-file-invoice text-indigo-600 dark:text-indigo-400"></i> Download Customer Form (Injected EBD)
        </h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Select the customer/vendor master template layout. System will inject EBD breakdown data into the mapped cells.</p>

        <form id="exportDynamicEbdForm" method="GET" action="" class="space-y-4">
            <div>
                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1">
                    Select Master Template Layout <span class="text-rose-500">*</span>
                </label>
                <select name="template_id" id="dynamicTemplateSelect" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-xs font-medium text-slate-800 dark:text-slate-100 rounded-sm focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- Select Customer / Vendor Template --</option>
                    @foreach($templates ?? [] as $tpl)
                        <option value="{{ $tpl->id }}">{{ $tpl->template_name }} (Rev {{ $tpl->revision ?? '0' }}) [{{ strtoupper($tpl->template_type) }}]</option>
                    @endforeach
                </select>
                <p class="text-[10px] text-slate-400 mt-1">Logo, formulas, formatting, and layout of the master file will be 100% preserved.</p>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-700">
                <button type="button" onclick="closeDynamicEbdModal()" class="px-3.5 h-8 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-200 text-xs font-medium rounded-sm cursor-pointer transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-3.5 h-8 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 text-xs font-semibold rounded-sm flex items-center gap-1.5 cursor-pointer transition-colors">
                    <i class="fa-solid fa-cloud-arrow-down text-xs"></i> Download Form (.xlsx)
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
        if (typeof defaultDataTable === 'function') {
            defaultDataTable('#quotation-tooling-table', {
                processing: true,
                serverSide: true,
                order: [[1, 'desc']],
                ajax: "{{ route('management.tooling-quotation.index') }}",
                columns: [
                    {
                        data: 'index_num',
                        orderable: false,
                        searchable: false,
                        className: 'px-3 py-2.5 text-center font-bold text-slate-400 border-r border-slate-100 dark:border-slate-800'
                    },
                    {
                        data: 'ebd_ref',
                        orderable: true,
                        searchable: true,
                        className: 'px-4 py-2.5 font-bold text-slate-800 dark:text-slate-100 border-r border-slate-100 dark:border-slate-800'
                    },
                    {
                        data: 'customer_model',
                        orderable: false,
                        searchable: true,
                        className: 'px-4 py-2.5 text-slate-700 dark:text-slate-200 border-r border-slate-100 dark:border-slate-800'
                    },
                    {
                        data: 'wo_ref',
                        orderable: false,
                        searchable: true,
                        className: 'px-3 py-2.5 text-slate-600 dark:text-slate-300 border-r border-slate-100 dark:border-slate-800'
                    },
                    { 
                        data: 'status', 
                        orderable: true, 
                        searchable: false, 
                        className: 'px-3 py-2.5 text-center border-r border-slate-100 dark:border-slate-800',
                        render: function(data) {
                            const isApproved = (data === 'Approved' || data === 'Finish' || data === 'Released');
                            const bgClass = isApproved ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700';
                            return `<span class="inline-block px-2 py-0.5 text-[10px] font-black rounded-sm border ${bgClass}">${data}</span>`;
                        }
                    },
                    {
                        data: 'quotation_count',
                        orderable: false,
                        searchable: false,
                        className: 'px-3 py-2.5 text-center border-r border-slate-100 dark:border-slate-800 font-bold',
                        render: function(data) {
                            return `<div class="flex items-center justify-center gap-1.5 flex-wrap">${data}</div>`;
                        }
                    },
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false,
                        className: 'px-3 py-2.5 text-center',
                        render: function(data, type, row) {
                            return `
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="${row.compare_url}"
                                       title="Open Quotation Comparison (EBD > Customer > Supplier)"
                                       class="inline-flex items-center justify-center gap-1.5 px-3 h-7 bg-indigo-600 hover:bg-indigo-700 text-white rounded-sm text-xs font-semibold shadow-xs transition-all cursor-pointer">
                                        <i class="fa-solid fa-code-compare text-xs"></i>
                                        <span>Compare</span>
                                    </a>
                                </div>
                            `;
                        }
                    }
                ],
                language: {
                    emptyTable: `
                        <div class="py-12 flex flex-col items-center justify-center text-center w-full">
                            <i class="fa-solid fa-folder-open text-2xl text-slate-300 dark:text-slate-600 mb-2"></i>
                            <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider mb-1">No Approved SPK 2 Tooling Cost Found</h4>
                            <p class="text-[11px] text-slate-400 max-w-xs mx-auto">Only Work Orders with "Approved" or "Released" status will appear here for supplier quotation comparison.</p>
                        </div>
                    `
                }
            });
        }
    });
</script>
@endpush
@endsection

