@extends('layouts.app')

@section('title', 'Project Inquiries - Promise Management')

@section('content')
<div class="flex-1 overflow-y-auto p-4 pt-17.5 space-y-4 transition-colors duration-200"
     x-data="inquiryIndex">
    
    <!-- Title & Action -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">Project Inquiries</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Manage customer RFQs, product lists, and feasibility studies</p>
        </div>
        <button @click="showWizard = true; step = 1; inquiryId = null; inquiryNo = null; customer_id = ''; project_id = ''; project_name = ''; inquiry_date = '{{ date('Y-m-d') }}'; remarks = ''; products = []; validationErrors = []; excelError = ''; document.getElementById('excel_file').value = '';"
           class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-medium rounded-none shadow-sm transition-colors text-sm">
            <i class="fa-solid fa-plus text-xs"></i>
            Create New Inquiry
        </button>
    </div>

    <!-- Sessions Alert -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/30 border-l-4 border-emerald-500 text-emerald-800 dark:text-emerald-400 text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 bg-rose-50 dark:bg-rose-950/30 border-l-4 border-rose-500 text-rose-800 dark:text-rose-400 text-sm">
            {{ session('error') }}
        </div>
    @endif

    <!-- Filters Card -->
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 p-4 transition-colors duration-200">
        <form method="GET" action="{{ route('management.inquiry.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search No, Customer, Project..." 
                       class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-none px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 transition-colors">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Status</label>
                <select name="status" 
                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-none px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 transition-colors">
                    <option value="">All Statuses</option>
                    <option value="Draft" {{ ($filters['status'] ?? '') === 'Draft' ? 'selected' : '' }}>Draft</option>
                    <option value="Active" {{ ($filters['status'] ?? '') === 'Active' ? 'selected' : '' }}>Active</option>
                    <option value="Cancelled" {{ ($filters['status'] ?? '') === 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                    <option value="Closed" {{ ($filters['status'] ?? '') === 'Closed' ? 'selected' : '' }}>Closed</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">From Date</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" 
                       class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-none px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 transition-colors">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">To Date</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" 
                       class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-none px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 transition-colors">
            </div>
            <div class="flex gap-2">
                <button type="submit" 
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-none text-sm transition-colors flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i> Filter
                </button>
                <a href="{{ route('management.inquiry.index') }}" 
                   class="bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-200 font-medium py-2 px-4 rounded-none text-sm transition-colors flex items-center justify-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- List Table Card -->
    <x-table id="inquiries-table">
        <thead>
            <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400">
                <th class="p-3 text-xs font-semibold uppercase tracking-wider">Inquiry No</th>
                <th class="p-3 text-xs font-semibold uppercase tracking-wider">Customer</th>
                <th class="p-3 text-xs font-semibold uppercase tracking-wider">Model</th>
                <th class="p-3 text-xs font-semibold uppercase tracking-wider">Project Name</th>
                <th class="p-3 text-xs font-semibold uppercase tracking-wider">Inquiry Date</th>
                <th class="p-3 text-xs font-semibold uppercase tracking-wider text-center">Products</th>
                <th class="p-3 text-xs font-semibold uppercase tracking-wider">Status</th>
                <th class="p-3 text-xs font-semibold uppercase tracking-wider text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700/80 text-sm">
            @foreach($inquiries as $inquiry)
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 text-slate-800 dark:text-slate-100 transition-colors duration-150">
                    <td class="p-3 font-semibold text-blue-600 dark:text-blue-400">
                        <a href="{{ route('management.inquiry.show', $inquiry->id) }}" class="hover:underline">
                            {{ $inquiry->inquiry_no }}
                        </a>
                    </td>
                    <td class="p-3 font-mono text-xs">{{ $inquiry->customer->code ?? '—' }}</td>
                    <td class="p-3 text-xs">{{ $inquiry->model_name ?? '—' }}</td>
                    <td class="p-3">{{ $inquiry->project_name }}</td>
                    <td class="p-3">{{ $inquiry->inquiry_date->format('d M Y') }}</td>
                    <td class="p-3 text-center">
                        <span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-900 font-semibold text-xs rounded-none">
                            {{ $inquiry->products()->count() }}
                        </span>
                    </td>
                    <td class="p-3">
                        @if($inquiry->status === 'Draft')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-semibold bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-900/30">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Draft
                            </span>
                        @elseif($inquiry->status === 'Active')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-semibold bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-400 border border-sky-200 dark:border-sky-900/30">
                                <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span> Active
                            </span>
                        @elseif($inquiry->status === 'Closed')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900/30">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Closed
                            </span>
                        @elseif($inquiry->status === 'Cancelled')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-semibold bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-900/30">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Cancelled
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-semibold bg-slate-50 dark:bg-slate-900 text-slate-700 dark:text-slate-400 border border-slate-200 dark:border-slate-800">
                                {{ $inquiry->status }}
                            </span>
                        @endif
                    </td>
                    <td class="p-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('management.inquiry.show', $inquiry->id) }}" 
                               class="px-2.5 py-1 text-xs font-semibold bg-slate-100 dark:bg-slate-900 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 transition-colors">
                                View Detail
                            </a>
                            @if($inquiry->status === 'Draft')
                                <button @click="
                                    editForm = {
                                        id: '{{ $inquiry->id }}',
                                        customer_id: '{{ $inquiry->customer_id }}',
                                        project_id: '{{ $inquiry->model_id }}',
                                        project_name: '{{ addslashes($inquiry->project_name) }}',
                                        inquiry_date: '{{ $inquiry->inquiry_date->format('Y-m-d') }}',
                                        remarks: '{{ addslashes($inquiry->remarks) }}'
                                    };
                                    showEditModal = true;"
                                    class="px-2.5 py-1 text-xs font-semibold bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-700 text-blue-600 dark:text-blue-400 transition-colors">
                                    Edit
                                </button>
                            @endif
                            <form action="{{ route('management.inquiry.destroy', $inquiry->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this Project Inquiry and all related data? This action cannot be undone.');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-2.5 py-1 text-xs font-semibold bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-400 transition-colors">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-table>

    <!-- Wizard (Add) Modal -->
    <div x-show="showWizard" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4" style="display: none;">
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 w-full max-w-4xl p-6 relative flex flex-col max-h-[90vh]">
            
            <!-- Close Button -->
            <button @click="showWizard = false" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>

            <!-- Wizard Header Indicator -->
            <div class="border-b border-slate-100 dark:border-slate-700 pb-4 mb-4">
                <h3 class="text-lg font-bold text-slate-800 dark:text-white flex items-center gap-2">
                    <span>Create Project Inquiry Wizard</span>
                    <span x-show="inquiryNo" class="text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300 px-2 py-0.5 rounded-xs" x-text="inquiryNo"></span>
                </h3>
                
                <!-- Steps indicators -->
                <div class="flex items-center gap-3 mt-4 text-xs">
                    <span :class="step >= 1 ? 'text-blue-600 dark:text-blue-400 font-bold' : 'text-slate-400'">1. Inquiry Header</span>
                    <span class="text-slate-300 dark:text-slate-600">&rarr;</span>
                    <span :class="step >= 2 ? 'text-blue-600 dark:text-blue-400 font-bold' : 'text-slate-400'">2. Excel Upload</span>
                    <span class="text-slate-300 dark:text-slate-600">&rarr;</span>
                    <span :class="step >= 3 ? 'text-blue-600 dark:text-blue-400 font-bold' : 'text-slate-400'">3. Validate & Finish</span>
                </div>
            </div>

            <!-- Loader Overlay -->
            <div x-show="loading" class="absolute inset-0 bg-white/70 dark:bg-slate-800/70 z-10 flex items-center justify-center">
                <div class="flex flex-col items-center gap-2">
                    <i class="fa-solid fa-circle-notch fa-spin text-blue-600 text-3xl"></i>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Processing... Please wait</span>
                </div>
            </div>

            <!-- Wizard Content Wrapper (Scrollable if data is large) -->
            <div class="flex-grow overflow-y-auto space-y-4 pr-1">
                
                <!-- STEP 1: Inquiry Header -->
                <div x-show="step === 1" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Project Name <span class="text-rose-500">*</span></label>
                            <input type="text" x-model="project_name" required placeholder="e.g. Project 5P45"
                                   class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Customer <span class="text-rose-500">*</span></label>
                            <select x-model="customer_id" required @change="project_id = ''"
                                    class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500">
                                <option value="">Select Customer</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}">{{ $c->code }} - {{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Model <span class="text-rose-500">*</span></label>
                            <select x-model="project_id" required :disabled="!customer_id"
                                    class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 disabled:opacity-50">
                                <option value="">Select Model</option>
                                <template x-for="m in getFilteredModels(customer_id)" :key="m.id">
                                    <option :value="m.id" x-text="m.name" :selected="m.id == project_id"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Inquiry Date <span class="text-rose-500">*</span></label>
                            <input type="date" x-model="inquiry_date" required
                                   class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Remarks</label>
                        <textarea x-model="remarks" rows="6" placeholder="Add additional comments or context about the RFQ"
                                  class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500"></textarea>
                    </div>
                </div>

                <!-- STEP 2: Excel Upload -->
                <div x-show="step === 2" class="space-y-4">
                    <div class="p-8 border-2 border-dashed border-slate-300 dark:border-slate-700 text-center space-y-3 bg-slate-50/50 dark:bg-slate-900/30">
                        <i class="fa-solid fa-file-excel text-slate-400 text-5xl"></i>
                        <div>
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Choose products Excel file to upload</p>
                            <p class="text-xs text-slate-400 mt-1">Accepts Excel (.xlsx, .xls) up to 10MB</p>
                            <div class="mt-2">
                                <a href="{{ route('management.inquiry.download-template') }}" 
                                   class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 hover:underline inline-flex items-center gap-1.5 font-semibold">
                                    <i class="fa-solid fa-download text-[10px]"></i> Download Excel Template
                                </a>
                            </div>
                        </div>
                        <div class="inline-block">
                            <input type="file" id="excel_file" class="hidden" accept=".xlsx,.xls" @change="excelError = ''">
                            <button type="button" @click="document.getElementById('excel_file').click()" 
                                    class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-xs font-semibold uppercase tracking-wider">
                                Select File
                            </button>
                        </div>
                    </div>
                    
                    <div x-show="excelError" class="p-3 bg-rose-50 dark:bg-rose-950/20 text-rose-600 text-xs border-l-4 border-rose-500" x-text="excelError"></div>
                </div>

                <!-- STEP 3: Validate & Finish -->
                <div x-show="step === 3" class="space-y-4">
                    <div class="flex items-center gap-3 bg-emerald-50 dark:bg-emerald-950/20 text-emerald-800 dark:text-emerald-400 p-4 border border-emerald-200 dark:border-emerald-900/30">
                        <i class="fa-solid fa-circle-check text-xl text-emerald-500"></i>
                        <div>
                            <p class="text-sm font-bold">Inquiry products successfully uploaded</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400" x-text="`Loaded ${importedCount} products. Let's review before saving.`"></p>
                        </div>
                    </div>

                    <!-- Warning/Error rows summary if any -->
                    <div x-show="validationErrors.length > 0" class="space-y-2">
                        <p class="text-xs font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider"><i class="fa-solid fa-triangle-exclamation mr-1"></i> Row Warnings/Skipped records (<span x-text="validationErrors.length"></span>)</p>
                        <div class="max-h-[150px] overflow-y-auto bg-amber-50/50 dark:bg-amber-950/10 border border-amber-200 dark:border-amber-900/30 p-2 text-xs text-amber-800 dark:text-amber-400 divide-y divide-amber-100 dark:divide-amber-900/20">
                            <template x-for="err in validationErrors" :key="err.row">
                                <div class="py-1">
                                    <strong x-text="`Row ${err.row}: `"></strong>
                                    <span x-text="err.errors.join(', ')"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Products Preview Table -->
                    <div class="border border-slate-200 dark:border-slate-700/80">
                        <p class="p-2.5 font-bold text-xs uppercase bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700">Preview Imported Products (<span x-text="products.length"></span>)</p>
                        <div class="overflow-x-auto max-h-[200px]">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="bg-slate-50/50 dark:bg-slate-900/30 text-slate-500 border-b border-slate-200 dark:border-slate-700">
                                        <th class="p-2 font-semibold">Model Name</th>
                                        <th class="p-2 font-semibold">Part No</th>
                                        <th class="p-2 font-semibold">Part Name</th>
                                        <th class="p-2 font-semibold">Annual Vol.</th>
                                        <th class="p-2 font-semibold">Destination</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="p in products" :key="p.id">
                                        <tr class="border-b border-slate-100 dark:border-slate-800/60 hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                            <td class="p-2" x-text="p.model_name"></td>
                                            <td class="p-2 font-semibold" x-text="p.customer_part_no"></td>
                                            <td class="p-2" x-text="p.customer_part_name"></td>
                                            <td class="p-2" x-text="p.annual_volume ? parseInt(p.annual_volume).toLocaleString() : '-'"></td>
                                            <td class="p-2" x-text="p.destination || '-'"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Wizard Footer Action Buttons -->
            <div class="flex justify-between items-center pt-4 border-t border-slate-100 dark:border-slate-700 mt-4">
                <!-- Left Button: Back (Step 2 Only) -->
                <div>
                    <button type="button" x-show="step === 2" @click="step = 1" 
                            class="px-4 py-2 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold uppercase tracking-wider">
                        Back to Header
                    </button>
                    <button type="button" x-show="step === 3" @click="step = 2" 
                            class="px-4 py-2 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold uppercase tracking-wider">
                        Re-upload Excel
                    </button>
                </div>

                <!-- Right Button: Next / Finalize -->
                <div class="flex gap-2">
                    <button type="button" @click="showWizard = false" 
                            class="px-4 py-2 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold uppercase tracking-wider">
                        Close
                    </button>
                    <!-- Step 1 Next -->
                    <button type="button" x-show="step === 1" @click="submitHeader" 
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold uppercase tracking-wider">
                        Next
                    </button>
                    <!-- Step 2 Upload -->
                    <button type="button" x-show="step === 2" @click="uploadExcel" 
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold uppercase tracking-wider">
                        Validate & Next
                    </button>
                    <!-- Step 3 Finish -->
                    <button type="button" x-show="step === 3" @click="finalizeInquiry" 
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold uppercase tracking-wider">
                        Save & Finish
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- Edit Modal -->
    <div x-show="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4" style="display: none;">
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 w-full max-w-lg p-6 relative">
            <h3 class="text-base font-bold text-slate-800 dark:text-white mb-4">Edit Project Inquiry Header</h3>
            
            <form @submit.prevent="submitEdit" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Project Name</label>
                    <input type="text" x-model="editForm.project_name" required placeholder="Project Name"
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Customer</label>
                    <select x-model="editForm.customer_id" required @change="editForm.project_id = ''"
                            class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none">
                        <option value="">Select Customer</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->code }} - {{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Model</label>
                    <select x-model="editForm.project_id" required :disabled="!editForm.customer_id"
                            class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none disabled:opacity-50">
                        <option value="">Select Model</option>
                        <template x-for="m in getFilteredModels(editForm.customer_id)" :key="m.id">
                            <option :value="m.id" x-text="m.name" :selected="m.id == editForm.project_id"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Inquiry Date</label>
                    <input type="date" x-model="editForm.inquiry_date" required
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Remarks</label>
                    <textarea x-model="editForm.remarks" rows="4"
                              class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-700">
                    <button type="button" @click="showEditModal = false" class="px-4 py-2 border border-slate-300 text-slate-700 text-xs uppercase tracking-wider font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs uppercase tracking-wider font-semibold">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('inquiryIndex', () => ({
            showWizard: false,
            step: 1,
            loading: false,
            inquiryId: null,
            inquiryNo: null,
            customer_id: '',
            project_id: '',
            project_name: '',
            inquiry_date: '{{ date("Y-m-d") }}',
            remarks: '',
            excelError: '',
            validationErrors: [],
            importedCount: 0,
            products: [],
            
            showEditModal: false,
            editForm: { id: '', customer_id: '', project_id: '', project_name: '', inquiry_date: '', remarks: '' },

            getFilteredModels(customerId) {
                const allModels = @json($models);
                return allModels.filter(m => m.customer_id == customerId);
            },

            submitHeader() {
                if (!this.customer_id || !this.project_id || !this.project_name || !this.inquiry_date) {
                    alert('Please fill out all required fields.');
                    return;
                }
                this.loading = true;
                fetch('{{ route('management.inquiry.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        customer_id: this.customer_id,
                        project_id: this.project_id,
                        project_name: this.project_name,
                        inquiry_date: this.inquiry_date,
                        remarks: this.remarks
                    })
                })
                .then(res => res.json())
                .then(data => {
                    this.loading = false;
                    if (data.success) {
                        this.inquiryId = data.inquiry.id;
                        this.inquiryNo = data.inquiry.inquiry_no;
                        this.step = 2;
                    } else {
                        alert('Error: ' + (data.message || 'Failed to create inquiry header'));
                    }
                })
                .catch(err => {
                    this.loading = false;
                    console.error(err);
                    alert('An error occurred while saving the header.');
                });
            },

            uploadExcel() {
                const fileInput = document.getElementById('excel_file');
                if (!fileInput.files.length) {
                    alert('Please choose an Excel file to upload.');
                    return;
                }
                this.loading = true;
                this.excelError = '';
                this.validationErrors = [];

                const formData = new FormData();
                formData.append('excel_file', fileInput.files[0]);

                fetch('{{ url('management/inquiry') }}/' + this.inquiryId + '/parse-excel', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    this.loading = false;
                    if (data.success) {
                        this.importedCount = data.imported_count;
                        this.validationErrors = data.errors || [];
                        this.products = data.products || [];
                        this.step = 3;
                    } else {
                        this.excelError = data.message || 'Failed to parse Excel file.';
                    }
                })
                .catch(err => {
                    this.loading = false;
                    console.error(err);
                    this.excelError = 'An error occurred during Excel upload.';
                });
            },

            finalizeInquiry() {
                this.loading = true;
                fetch('{{ url('management/inquiry') }}/' + this.inquiryId + '/finalize', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    this.loading = false;
                    if (data.success) {
                        window.location.href = data.redirect_url;
                    } else {
                        alert('Error finalizing inquiry: ' + data.message);
                    }
                })
                .catch(err => {
                    this.loading = false;
                    console.error(err);
                    alert('An error occurred during finalization.');
                });
            },

            submitEdit() {
                if (!this.editForm.customer_id || !this.editForm.project_id || !this.editForm.project_name || !this.editForm.inquiry_date) {
                    alert('Please fill out all required fields.');
                    return;
                }
                this.loading = true;
                fetch('{{ url('management/inquiry') }}/' + this.editForm.id, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        customer_id: this.editForm.customer_id,
                        project_id: this.editForm.project_id,
                        project_name: this.editForm.project_name,
                        inquiry_date: this.editForm.inquiry_date,
                        remarks: this.editForm.remarks
                    })
                })
                .then(res => res.json())
                .then(data => {
                    this.loading = false;
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(err => {
                    this.loading = false;
                    console.error(err);
                    alert('An error occurred while updating the inquiry.');
                });
            }
        }));
    });

    $(function() {
        defaultDataTable('#inquiries-table', {
            order: [[0, 'desc']],
            language: {
                emptyTable: `
                    <div class="py-16 flex flex-col items-center justify-center text-center w-full">
                        <div>
                            <i class="fa-solid fa-inbox text-3xl text-slate-300 dark:text-slate-600 m-4"></i>
                        </div>
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-widest mb-2">No Project Inquiries Found</h4>
                        <p class="text-xs text-gray-400 dark:text-gray-500 max-w-xs mx-auto font-medium leading-relaxed">It looks like there are no inquiries created yet. Try creating a new project inquiry using the button above.</p>
                    </div>
                `
            }
        });
    });
</script>
@endpush
@endsection

