@extends('layouts.app')

@section('title', 'Excel Templates Engine - Promise Management')

@section('content')
<div class="flex-1 overflow-y-auto p-4 pt-17.5 space-y-4 transition-colors duration-200" x-data="excelTemplatesIndex()">
    
    <!-- Title & Action -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">Excel Templates Configurations</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">View and manage uploaded Excel templates and coordinate cell mappings</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="showImportModal = true"
               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-medium rounded-none transition-colors text-sm">
                <i class="fa-solid fa-plus text-xs"></i>
                Import New Template
            </button>
        </div>
    </div>

    <!-- SweetAlert & Notifications -->
    <x-sweetalert />
    @if(session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                showToast("{{ session('success') }}", 'success');
            });
        </script>
    @endif

    <!-- Card with Integrated Filter & Table Component -->
    <div class="bg-white dark:bg-slate-800 rounded-xs border border-slate-200 dark:border-slate-700 overflow-hidden relative">
        
        <!-- Integrated Filter Bar -->
        <div class="flex flex-wrap items-center justify-between gap-4 p-4 bg-slate-100/50 dark:bg-slate-900/30">
            <div class="flex items-center gap-2">
                <label for="filter-module" class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Module Domain:</label>
                <select id="filter-module" class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-none text-xs px-3 py-1.5 focus:outline-none focus:border-blue-500 text-slate-700 dark:text-slate-300">
                    <option value="">All Modules</option>
                    <option value="tooling_quotation">Tooling Quotation Engine</option>
                    <option value="quotation">Part Quotation Engine</option>
                    <option value="purchase_order">Purchase Order Engine</option>
                    <option value="invoice">Invoice Engine</option>
                </select>
            </div>
        </div>

        <div class="border-t border-slate-100 dark:border-slate-700 p-4 bg-white dark:bg-slate-800">
            <x-table id="excel-templates-table">
                <thead>
                    <tr class="border-b-2 border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900/30 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                        <th class="px-3 py-2.5 w-12 text-center bg-slate-100/50 dark:bg-slate-900/50">#</th>
                        <th class="px-3 py-2.5 bg-slate-100/50 dark:bg-slate-900/50">Template Name</th>
                        <th class="px-3 py-2.5 bg-slate-100/50 dark:bg-slate-900/50">Revision</th>
                        <th class="px-3 py-2.5 bg-slate-100/50 dark:bg-slate-900/50">Module Domain</th>
                        <th class="px-3 py-2.5 bg-slate-100/50 dark:bg-slate-900/50">Master Excel File</th>
                        <th class="px-3 py-2.5 bg-slate-100/50 dark:bg-slate-900/50">Configured Mappings</th>
                        <th class="px-3 py-2.5 bg-slate-100/50 dark:bg-slate-900/50">Status</th>
                        <th class="px-3 py-2.5 text-right w-52 bg-slate-100/50 dark:bg-slate-900/50">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700/80 text-sm">
                    @foreach($templates as $tpl)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors">
                        <td class="px-3 py-3 text-center text-xs text-slate-500 dark:text-slate-400">{{ $loop->iteration }}</td>
                        <td class="px-3 py-3 font-semibold text-slate-800 dark:text-white">{{ $tpl->template_name }}</td>
                        <td class="px-3 py-3 font-mono text-xs text-slate-600 dark:text-slate-300">
                            <span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-700 rounded text-[11px] font-semibold">Rev {{ $tpl->revision ?? '0' }}</span>
                        </td>
                        <td class="px-3 py-3">
                            <span class="px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wider bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-600">
                                {{ $tpl->template_type }}
                            </span>
                        </td>
                        <td class="px-3 py-3 font-mono text-xs text-blue-600 dark:text-blue-400">
                            <i class="fa-regular fa-file-excel me-1"></i>{{ basename($tpl->file_path) }}
                        </td>
                        <td class="px-3 py-3 space-x-1">
                            @php
                                $singleCount = count($tpl->mapping_config['single_fields'] ?? []);
                                $loopCount = count($tpl->mapping_config['table_loops'] ?? []);
                            @endphp
                            <span class="px-2 py-0.5 text-[11px] font-medium bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                {{ $singleCount }} Single Cells
                            </span>
                            <span class="px-2 py-0.5 text-[11px] font-medium bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800">
                                {{ $loopCount }} Table Loops
                            </span>
                        </td>
                        <td class="px-3 py-3">
                            @if($tpl->is_active)
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 text-[11px] font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 text-[11px] font-medium bg-rose-50 text-rose-700 dark:bg-rose-950/50 dark:text-rose-400 border border-rose-200 dark:border-rose-800">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Inactive
                                </span>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-right space-x-1">
                            <button @click="openEditModal({{ json_encode($tpl) }})" class="p-1.5 text-slate-500 hover:text-blue-600 dark:hover:text-blue-400" title="Edit Template Metadata / Replace File">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <a href="{{ route('management.excel-templates.builder', $tpl->id) }}" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium transition-colors" title="Open Spreadsheet Visual Mapper">
                                <i class="fa-solid fa-table-cells"></i> Mapper
                            </a>
                            <form action="{{ route('management.excel-templates.destroy', $tpl->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this template and its physical excel file?')">
                                @csrf
                                <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600" title="Delete Template">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </x-table>
        </div>
    </div>

    <!-- Modal: Import New Template -->
    <div x-show="showImportModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
        <div class="bg-white dark:bg-slate-800 w-full max-w-lg border border-slate-200 dark:border-slate-700 shadow-2xl p-6 relative">
            <button @click="showImportModal = false" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 dark:hover:text-white">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>

            <h2 class="text-lg font-bold text-slate-800 dark:text-white mb-1 flex items-center gap-2">
                <i class="fa-solid fa-cloud-arrow-up text-blue-600"></i> Import Master Excel Template (.xlsx)
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Upload customer or vendor master spreadsheet layout for visual mapping.</p>

            <form action="{{ route('management.excel-templates.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Template Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="template_name" required placeholder="e.g. Quotation Tooling Format Honda"
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Target Module Domain <span class="text-rose-500">*</span></label>
                        <select name="template_type" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500">
                            <option value="tooling_quotation">Tooling Quotation Engine</option>
                            <option value="quotation">Part Quotation Engine</option>
                            <option value="purchase_order">Purchase Order Engine</option>
                            <option value="invoice">Invoice Engine</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Revision / Version</label>
                        <input type="text" name="revision" value="0" placeholder="e.g. 0 / Rev 1.2"
                               class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Master Excel File (.xlsx) <span class="text-rose-500">*</span></label>
                    <input type="file" name="file" accept=".xlsx" required class="w-full text-xs text-slate-500 dark:text-slate-400 file:mr-3 file:py-1.5 file:px-3 file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-slate-700 dark:file:text-slate-200">
                    <p class="text-[11px] text-slate-400 mt-1">Accepts .xlsx only. Visual layout, borders, formulas, merged cells, and logos will be 100% preserved.</p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-200 dark:border-slate-700">
                    <button type="button" @click="showImportModal = false" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-medium hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium">
                        Upload & Proceed to Mapping
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Edit Template Metadata & Replace File -->
    <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
        <div class="bg-white dark:bg-slate-800 w-full max-w-lg border border-slate-200 dark:border-slate-700 shadow-2xl p-6 relative">
            <button @click="showEditModal = false" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 dark:hover:text-white">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>

            <h2 class="text-lg font-bold text-slate-800 dark:text-white mb-1 flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-blue-600"></i> Edit Template & Replace Master File
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Update template details, bump revision number, or replace the physical master .xlsx file.</p>

            <form :action="`/management/excel-templates/${editingTemplate.id}/update`" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Template Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="template_name" x-model="editingTemplate.template_name" required
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Target Module Domain <span class="text-rose-500">*</span></label>
                        <select name="template_type" x-model="editingTemplate.template_type" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500">
                            <option value="tooling_quotation">Tooling Quotation Engine</option>
                            <option value="quotation">Part Quotation Engine</option>
                            <option value="purchase_order">Purchase Order Engine</option>
                            <option value="invoice">Invoice Engine</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Revision / Version</label>
                        <input type="text" name="revision" x-model="editingTemplate.revision" placeholder="e.g. Rev 1.1"
                               class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Replace Master Excel File (.xlsx)</label>
                    <input type="file" name="file" accept=".xlsx" class="w-full text-xs text-slate-500 dark:text-slate-400 file:mr-3 file:py-1.5 file:px-3 file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-slate-700 dark:file:text-slate-200">
                    <p class="text-[11px] text-slate-400 mt-1">Leave empty to keep the current master file. Uploading a new file will replace the old file permanently.</p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-200 dark:border-slate-700">
                    <button type="button" @click="showEditModal = false" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-medium hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function excelTemplatesIndex() {
        return {
            showImportModal: false,
            showEditModal: false,
            editingTemplate: {},

            openEditModal(tpl) {
                this.editingTemplate = Object.assign({}, tpl);
                this.showEditModal = true;
            },

            init() {
                this.$nextTick(() => {
                    if (typeof window.defaultDataTable === 'function') {
                        const table = window.defaultDataTable('#excel-templates-table', {
                            order: [[0, 'asc']]
                        });

                        $('#filter-module').on('change', function () {
                            const val = $(this).val();
                            table.column(3).search(val ? '^' + val + '$' : '', true, false).draw();
                        });
                    }
                });
            }
        };
    }
</script>
@endsection
