@extends('layouts.app')

@section('title', 'Upload Excel Template - Promise Management')

@section('content')
<main class="flex-1 overflow-y-auto p-4 md:p-6 bg-slate-50 dark:bg-slate-900">
    <div class="max-w-3xl mx-auto space-y-6">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h2 class="text-xl font-bold text-slate-800 dark:text-white mb-2 flex items-center gap-2">
                <i class="fa-solid fa-cloud-arrow-up text-indigo-600"></i> Upload Master Template Excel (.xlsx)
            </h2>
            <p class="text-slate-500 dark:text-slate-400 text-sm mb-6">Unggah file master spreadsheet eksternal dari customer/vendor untuk dikonfigurasi mapping-nya.</p>
            
            <form action="{{ route('management.excel-templates.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1">Nama Template</label>
                    <input type="text" name="template_name" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 dark:bg-slate-700 dark:text-white" placeholder="Contoh: Quotation Fabrication Honda Format A" required>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1">Module Domain</label>
                    <select name="template_type" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 dark:bg-slate-700 dark:text-white" required>
                        <option value="tooling_quotation">Tooling Quotation Engine</option>
                        <option value="quotation">Standard Quotation Engine</option>
                        <option value="purchase_order">Purchase Order Engine</option>
                        <option value="invoice">Invoice / Delivery Engine</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1">Revisi / Version</label>
                    <input type="text" name="revision" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 dark:bg-slate-700 dark:text-white" placeholder="Contoh: Rev 0 / Rev 1.2" value="0">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1">File Master Excel (.xlsx)</label>
                    <input type="file" name="file" accept=".xlsx" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-slate-700 dark:file:text-slate-200" required>
                    <p class="text-xs text-slate-500 mt-1">Format harus .xlsx. Keutuhan logo, border, merged cell, dan formula akan dipertahankan 100%.</p>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-700">
                    <a href="{{ route('management.excel-templates.index') }}" class="px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-200 text-sm font-medium rounded-lg transition-colors">
                        Batal
                    </a>
                    <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors">
                        Upload & Buka Visual Mapper
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>
@endsection
