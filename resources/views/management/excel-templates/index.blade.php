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
            <button @click="openImportModal()"
               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold rounded-xs transition-colors text-sm shadow-2xs">
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
            <div class="flex flex-wrap items-center gap-4">
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
                <div class="flex items-center gap-2">
                    <label for="filter-direction" class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Purpose / Direction:</label>
                    <select id="filter-direction" class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-none text-xs px-3 py-1.5 focus:outline-none focus:border-blue-500 text-slate-700 dark:text-slate-300">
                        <option value="">All Directions</option>
                        <option value="export">📤 Export (Generate Excel)</option>
                        <option value="import">📥 Import (Parse Excel)</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-100 dark:border-slate-700 p-4 bg-white dark:bg-slate-800">
            <x-table id="excel-templates-table">
                <thead>
                    <tr class="border-b-2 border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900/30 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                        <th class="px-3 py-2.5 w-10 text-center bg-slate-100/50 dark:bg-slate-900/50">#</th>
                        <th class="px-3 py-2.5 bg-slate-100/50 dark:bg-slate-900/50">Template Name</th>
                        <th class="px-3 py-2.5 bg-slate-100/50 dark:bg-slate-900/50">Direction</th>
                        <th class="px-3 py-2.5 bg-slate-100/50 dark:bg-slate-900/50">Revision</th>
                        <th class="px-3 py-2.5 bg-slate-100/50 dark:bg-slate-900/50">Module Domain</th>
                        <th class="px-3 py-2.5 bg-slate-100/50 dark:bg-slate-900/50">Master Excel File</th>
                        <th class="px-3 py-2.5 bg-slate-100/50 dark:bg-slate-900/50">Configured Mappings</th>
                        <th class="px-3 py-2.5 bg-slate-100/50 dark:bg-slate-900/50">Status</th>
                        <th class="px-3 py-2.5 text-right w-44 bg-slate-100/50 dark:bg-slate-900/50">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700/80 text-sm">
                    @foreach($templates as $tpl)
                    @php
                        $fileName = basename($tpl->file_path);
                        $shortName = \Illuminate\Support\Str::limit($fileName, 24, '...');
                        $singleCount = count($tpl->mapping_config['single_fields'] ?? []);
                        $loopCount = count($tpl->mapping_config['table_loops'] ?? []);
                        $ruleCount = count($tpl->mapping_config['conditional_rules'] ?? []);
                    @endphp
                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition-colors">
                        <td class="px-3 py-3 text-center text-xs text-slate-400 dark:text-slate-500 font-mono">{{ $loop->iteration }}</td>
                        <td class="px-3 py-3 font-semibold text-slate-800 dark:text-white">
                            <span class="block text-xs font-bold text-slate-800 dark:text-slate-100">{{ ucwords($tpl->template_name) }}</span>
                        </td>
                        <td class="px-3 py-3">
                            @if(($tpl->direction ?? 'export') === 'import')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-xs bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800/60 shadow-2xs">
                                    <i class="fa-solid fa-cloud-arrow-up text-purple-500"></i> Import
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-xs bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800/60 shadow-2xs">
                                    <i class="fa-solid fa-cloud-arrow-down text-blue-500"></i> Export
                                </span>
                            @endif
                        </td>
                        <td class="px-3 py-3 font-mono text-xs text-slate-600 dark:text-slate-300">
                            <span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-xs text-[10px] font-semibold border border-slate-200 dark:border-slate-700">Rev {{ $tpl->revision ?? '0' }}</span>
                        </td>
                        <td class="px-3 py-3">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-xs bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/60">
                                <i class="fa-solid fa-cube text-indigo-500 text-[9px]"></i> {{ str_replace('_', ' ', strtoupper($tpl->template_type)) }}
                            </span>
                        </td>
                        <td class="px-3 py-3 font-mono text-xs">
                            <a href="{{ Storage::url($tpl->file_path) }}" target="_blank" download class="inline-flex items-center gap-1.5 px-2 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xs text-slate-700 dark:text-slate-300 hover:text-emerald-600 dark:hover:text-emerald-400 hover:border-emerald-300 transition-colors group" title="{{ $fileName }}">
                                <i class="fa-solid fa-file-excel text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform"></i>
                                <span class="truncate max-w-[150px] text-[11px] font-medium">{{ $shortName }}</span>
                            </a>
                        </td>
                        <td class="px-3 py-3">
                            @if($singleCount === 0 && $loopCount === 0 && $ruleCount === 0)
                                <span class="px-2 py-0.5 text-[10px] font-semibold bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 rounded-xs">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Unmapped
                                </span>
                            @else
                                <div class="flex flex-wrap items-center gap-1">
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 rounded-xs" title="{{ $singleCount }} Single Cell Mappings">
                                        <i class="fa-solid fa-tag text-[9px]"></i> {{ $singleCount }} Single
                                    </span>
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 text-[10px] font-bold bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800/60 rounded-xs" title="{{ $loopCount }} Table Loop Mappings">
                                        <i class="fa-solid fa-rotate text-[9px]"></i> {{ $loopCount }} Loops
                                    </span>
                                    @if($ruleCount > 0)
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 text-[10px] font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60 rounded-xs" title="{{ $ruleCount }} Conditional Rules">
                                            <i class="fa-solid fa-code-branch text-[9px]"></i> {{ $ruleCount }} IF
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td class="px-3 py-3">
                            @if($tpl->is_active)
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-xs bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Active
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-xs bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Inactive
                                </span>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="{{ route('management.excel-templates.builder', $tpl->id) }}" class="inline-flex items-center gap-1.5 h-7 px-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-bold rounded-xs shadow-2xs transition-colors shrink-0" title="Open Spreadsheet Visual Mapper">
                                    <i class="fa-solid fa-table-cells text-xs"></i> Mapper
                                </a>
                                <button @click="openEditModal({{ json_encode($tpl) }})" class="w-7 h-7 inline-flex items-center justify-center text-slate-500 hover:text-blue-600 dark:text-slate-400 dark:hover:text-blue-400 border border-slate-200 dark:border-slate-700 rounded-xs bg-white dark:bg-slate-900 transition-colors shrink-0 hover:border-blue-300 dark:hover:border-blue-700" title="Edit Template Metadata / Replace File">
                                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                                </button>
                                <form action="{{ route('management.excel-templates.duplicate', $tpl->id) }}" method="POST" class="inline-flex m-0 p-0 shrink-0" onsubmit="return confirm('Duplicate this template configuration and physical master file?')">
                                    @csrf
                                    <button type="submit" class="w-7 h-7 inline-flex items-center justify-center text-slate-500 hover:text-amber-600 dark:text-slate-400 dark:hover:text-amber-400 border border-slate-200 dark:border-slate-700 rounded-xs bg-white dark:bg-slate-900 transition-colors hover:border-amber-300 dark:hover:border-amber-700" title="Duplicate Template">
                                        <i class="fa-solid fa-copy text-xs"></i>
                                    </button>
                                </form>
                                <form action="{{ route('management.excel-templates.destroy', $tpl->id) }}" method="POST" class="inline-flex m-0 p-0 shrink-0" onsubmit="return confirm('Are you sure you want to delete this template and its physical excel file?')">
                                    @csrf
                                    <button type="submit" class="w-7 h-7 inline-flex items-center justify-center text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 border border-slate-200 dark:border-slate-700 rounded-xs bg-white dark:bg-slate-900 transition-colors hover:border-rose-300 dark:hover:border-rose-700" title="Delete Template">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </x-table>
        </div>
    </div>

    <!-- Modal: Import New Template -->
    <div x-show="showImportModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
        <div class="bg-white dark:bg-slate-800 w-full max-w-lg border border-slate-200 dark:border-slate-700 shadow-2xl p-6 relative rounded-xs">
            <button @click="showImportModal = false" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 dark:hover:text-white">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>

            <h2 class="text-lg font-bold text-slate-800 dark:text-white mb-1 flex items-center gap-2">
                <i class="fa-solid fa-cloud-arrow-up text-blue-600"></i> Import Master Excel Template (.xlsx)
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Upload customer or vendor master spreadsheet layout for visual mapping.</p>

            <form action="{{ route('management.excel-templates.store') }}" method="POST" enctype="multipart/form-data" @submit="submitForm($event)" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Template Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="template_name" required placeholder="e.g. Quotation Tooling Format Honda"
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 rounded-xs focus:outline-none focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Template Purpose / Direction <span class="text-rose-500">*</span></label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center gap-2 p-2 border border-slate-300 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50 rounded-xs cursor-pointer hover:border-blue-500">
                            <input type="radio" name="direction" value="export" checked class="text-blue-600 focus:ring-0">
                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">
                                📤 <strong>Export</strong> (DB to Excel)
                            </span>
                        </label>
                        <label class="flex items-center gap-2 p-2 border border-slate-300 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50 rounded-xs cursor-pointer hover:border-purple-500">
                            <input type="radio" name="direction" value="import" class="text-purple-600 focus:ring-0">
                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">
                                📥 <strong>Import</strong> (Excel to DB)
                            </span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Target Module Domain <span class="text-rose-500">*</span></label>
                        <select name="template_type" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 rounded-xs focus:outline-none focus:border-blue-500">
                            <option value="tooling_quotation">Tooling Quotation Engine</option>
                            <option value="quotation">Part Quotation Engine</option>
                            <option value="purchase_order">Purchase Order Engine</option>
                            <option value="invoice">Invoice Engine</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Revision / Version</label>
                        <input type="text" name="revision" value="0" placeholder="e.g. 0 / Rev 1.2"
                               class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 rounded-xs focus:outline-none focus:border-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Master Excel File (.xlsx) <span class="text-rose-500">*</span></label>
                    <input type="file" name="file" accept=".xlsx" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-600 dark:text-slate-300 rounded-xs focus:outline-none focus:border-blue-500 file:mr-3 file:py-2 file:px-3 file:border-0 file:border-r file:border-slate-300 dark:file:border-slate-700 file:text-xs file:font-semibold file:bg-slate-100 dark:file:bg-slate-800 file:text-slate-700 dark:file:text-slate-300 hover:file:bg-slate-200 dark:hover:file:bg-slate-700 cursor-pointer">
                    <p class="text-[11px] text-slate-400 mt-1">Accepts .xlsx only. Visual layout, borders, formulas, merged cells, and logos will be 100% preserved.</p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-200 dark:border-slate-700">
                    <button type="button" @click="showImportModal = false" :disabled="isSubmitting" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-xs hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" :disabled="isSubmitting" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 disabled:bg-blue-400 text-white text-xs font-semibold rounded-xs transition-colors shadow-2xs inline-flex items-center gap-1.5">
                        <template x-if="isSubmitting">
                            <i class="fa-solid fa-circle-notch fa-spin text-xs"></i>
                        </template>
                        <template x-if="!isSubmitting">
                            <i class="fa-solid fa-cloud-arrow-up text-xs"></i>
                        </template>
                        <span x-text="isSubmitting ? 'Uploading & Processing...' : 'Upload & Proceed to Mapping'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Edit Template Metadata & Replace File -->
    <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
        <div class="bg-white dark:bg-slate-800 w-full max-w-lg border border-slate-200 dark:border-slate-700 shadow-2xl p-6 relative rounded-xs">
            <button @click="showEditModal = false" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 dark:hover:text-white">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>

            <h2 class="text-lg font-bold text-slate-800 dark:text-white mb-1 flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-blue-600"></i> Edit Template & Replace Master File
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Update template details, bump revision number, or replace the physical master .xlsx file.</p>

            <form :action="`/management/excel-templates/${editingTemplate.id}/update`" method="POST" enctype="multipart/form-data" @submit="submitForm($event)" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Template Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="template_name" x-model="editingTemplate.template_name" required
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 rounded-xs focus:outline-none focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Template Purpose / Direction <span class="text-rose-500">*</span></label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center gap-2 p-2 border border-slate-300 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50 rounded-xs cursor-pointer hover:border-blue-500">
                            <input type="radio" name="direction" value="export" x-model="editingTemplate.direction" class="text-blue-600 focus:ring-0">
                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">
                                📤 <strong>Export</strong> (DB to Excel)
                            </span>
                        </label>
                        <label class="flex items-center gap-2 p-2 border border-slate-300 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50 rounded-xs cursor-pointer hover:border-purple-500">
                            <input type="radio" name="direction" value="import" x-model="editingTemplate.direction" class="text-purple-600 focus:ring-0">
                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">
                                📥 <strong>Import</strong> (Excel to DB)
                            </span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Target Module Domain <span class="text-rose-500">*</span></label>
                        <select name="template_type" x-model="editingTemplate.template_type" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 rounded-xs focus:outline-none focus:border-blue-500">
                            <option value="tooling_quotation">Tooling Quotation Engine</option>
                            <option value="quotation">Part Quotation Engine</option>
                            <option value="purchase_order">Purchase Order Engine</option>
                            <option value="invoice">Invoice Engine</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Revision / Version</label>
                        <input type="text" name="revision" x-model="editingTemplate.revision" placeholder="e.g. Rev 1.1"
                               class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 rounded-xs focus:outline-none focus:border-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Replace Master Excel File (.xlsx)</label>
                    <input type="file" name="file" accept=".xlsx" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-600 dark:text-slate-300 rounded-xs focus:outline-none focus:border-blue-500 file:mr-3 file:py-2 file:px-3 file:border-0 file:border-r file:border-slate-300 dark:file:border-slate-700 file:text-xs file:font-semibold file:bg-slate-100 dark:file:bg-slate-800 file:text-slate-700 dark:file:text-slate-300 hover:file:bg-slate-200 dark:hover:file:bg-slate-700 cursor-pointer">
                    <p class="text-[11px] text-slate-400 mt-1">Leave empty to keep the current master file. Uploading a new file will replace the old file permanently.</p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-200 dark:border-slate-700">
                    <button type="button" @click="showEditModal = false" :disabled="isSubmitting" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-xs hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" :disabled="isSubmitting" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 disabled:bg-blue-400 text-white text-xs font-semibold rounded-xs transition-colors shadow-2xs inline-flex items-center gap-1.5">
                        <template x-if="isSubmitting">
                            <i class="fa-solid fa-circle-notch fa-spin text-xs"></i>
                        </template>
                        <template x-if="!isSubmitting">
                            <i class="fa-solid fa-floppy-disk text-xs"></i>
                        </template>
                        <span x-text="isSubmitting ? 'Saving Changes...' : 'Save Changes'"></span>
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
            isSubmitting: false,

            openEditModal(tpl) {
                this.editingTemplate = Object.assign({}, tpl);
                this.showEditModal = true;
                this.isSubmitting = false;
            },

            openImportModal() {
                this.showImportModal = true;
                this.isSubmitting = false;
            },

            submitForm(e) {
                this.isSubmitting = true;
                if (window.showToast) {
                    window.showToast('Saving template configuration...', 'info');
                }
            },

            init() {
                this.$nextTick(() => {
                    if (typeof window.defaultDataTable === 'function') {
                        const table = window.defaultDataTable('#excel-templates-table', {
                            order: [[0, 'asc']]
                        });

                        $('#filter-module').on('change', function () {
                            const val = $(this).val();
                            table.column(4).search(val ? '^' + val + '$' : '', true, false).draw();
                        });

                        $('#filter-direction').on('change', function () {
                            const val = $(this).val();
                            table.column(2).search(val ? val : '', true, false).draw();
                        });
                    }
                });
            }
        };
    }
</script>
@endsection
