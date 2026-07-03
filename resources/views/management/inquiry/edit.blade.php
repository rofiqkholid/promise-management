@extends('layouts.app')

@section('title', 'Edit Project Inquiry - Promise Management')

@section('content')
<div class="flex-1 overflow-y-auto p-4 pt-17.5 space-y-4 transition-colors duration-200">
    
    <!-- Breadcrumb & Back -->
    <div>
        <a href="{{ route('management.inquiry.show', $inquiry->hashed_id) }}" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline mb-2">
            <i class="fa-solid fa-arrow-left"></i> Back to Detail
        </a>
        <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">Edit Inquiry: {{ $inquiry->inquiry_no }}</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">Update project inquiry metadata</p>
    </div>

    <!-- Error Alert -->
    @if(session('error'))
        <div class="p-4 bg-rose-50 dark:bg-rose-950/30 border-l-4 border-rose-500 text-rose-800 dark:text-rose-400 text-sm">
            {{ session('error') }}
        </div>
    @endif

    <!-- Form Card -->
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 p-6 transition-colors duration-200 max-w-2xl">
        <form method="POST" action="{{ route('management.inquiry.update', $inquiry->hashed_id) }}" class="space-y-4">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label for="customer_name" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Customer Name <span class="text-rose-500">*</span></label>
                    <input type="text" id="customer_name" name="customer_name" value="{{ old('customer_name', $inquiry->customer_name) }}" required 
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-none px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 transition-colors @error('customer_name') border-rose-500 @enderror">
                    @error('customer_name')
                        <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="inquiry_date" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Inquiry Date <span class="text-rose-500">*</span></label>
                <input type="date" id="inquiry_date" name="inquiry_date" value="{{ old('inquiry_date', $inquiry->inquiry_date->format('Y-m-d')) }}" required 
                       class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-none px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 transition-colors @error('inquiry_date') border-rose-500 @enderror">
                @error('inquiry_date')
                    <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="remarks" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Remarks</label>
                <textarea id="remarks" name="remarks" rows="4" 
                          class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-none px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 transition-colors">{{ old('remarks', $inquiry->remarks) }}</textarea>
            </div>

            <div class="pt-2 flex justify-end gap-3 border-t border-slate-200 dark:border-slate-700">
                <a href="{{ route('management.inquiry.show', $inquiry->hashed_id) }}" 
                   class="bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-200 font-medium py-2 px-4 rounded-none text-sm transition-colors">
                    Cancel
                </a>
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-medium py-2 px-6 rounded-none text-sm shadow-sm transition-colors flex items-center gap-1.5">
                    <i class="fa-solid fa-floppy-disk text-xs"></i> Update
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
