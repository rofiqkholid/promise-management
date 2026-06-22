@extends('layouts.app')

@section('title', 'Work Orders (SPK) · Promise Management')

@section('content')
<div class="flex-1 overflow-y-auto p-4 pt-17.5 space-y-4 transition-colors duration-200">
    
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-3">
        <div>
            <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none mb-1">Work Orders</div>
            <h1 class="text-lg font-extrabold tracking-tight text-slate-800 dark:text-white leading-none">
                Surat Perintah Kerja (SPK) List
            </h1>
        </div>
    </div>

    @if(session('success'))
        <div class="p-3 bg-emerald-50 dark:bg-emerald-950/30 border-l-4 border-emerald-500 text-emerald-800 dark:text-emerald-400 text-xs">
            {{ session('success') }}
        </div>
    @endif

    {{-- SPK List Card --}}
    <x-table id="work-orders-table">
        <thead>
            <tr class="border-b-2 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/30 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                <th class="px-3 py-2.5 w-8 text-center">#</th>
                <th class="px-3 py-2.5">SPK No</th>
                <th class="px-3 py-2.5">Revision</th>
                <th class="px-3 py-2.5">Subject</th>
                <th class="px-3 py-2.5">Inquiry No</th>
                <th class="px-3 py-2.5">Owner Dept</th>
                <th class="px-3 py-2.5 text-center">Priority</th>
                <th class="px-3 py-2.5 text-center">Status</th>
                <th class="px-3 py-2.5 text-right w-40">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($workOrders as $index => $wo)
                @php
                    $statusCls = match($wo->status) {
                        'Draft'             => 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-900/30',
                        'Pending Approval'  => 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border-amber-300 dark:border-amber-900/50',
                        'Approved'          => 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-900/30',
                        'Released'          => 'bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-400 border-sky-200 dark:border-sky-900/30',
                        default             => 'bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700',
                    };
                    $priorityCls = match($wo->priority) {
                        'URGENT'    => 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border-rose-300 dark:border-rose-900/50',
                        'STANDARD'  => 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border-amber-300 dark:border-amber-900/50',
                        default     => 'bg-slate-50 dark:bg-slate-900 text-slate-500 dark:text-slate-400 border-slate-200 dark:border-slate-700',
                    };
                @endphp
                <tr class="border-b border-slate-100 dark:border-slate-700/50 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                    <td class="px-3 py-2.5 text-center text-slate-400 font-mono text-[10px]">{{ $index + 1 }}</td>
                    <td class="px-3 py-2.5 font-bold text-slate-800 dark:text-slate-100">{{ $wo->work_order_no }}</td>
                    <td class="px-3 py-2.5 font-mono text-slate-600 dark:text-slate-300">Rev. {{ $wo->revision_no }}</td>
                    <td class="px-3 py-2.5 text-slate-600 dark:text-slate-300 font-medium">{{ $wo->subject }}</td>
                    <td class="px-3 py-2.5">
                        <a href="{{ route('management.inquiry.show', $wo->inquiry_id) }}" class="text-blue-600 hover:underline font-semibold">
                            {{ $wo->inquiry->inquiry_no }}
                        </a>
                    </td>
                    <td class="px-3 py-2.5 text-slate-600 dark:text-slate-300">{{ $wo->ownerDepartment->name ?? '—' }}</td>
                    <td class="px-3 py-2.5 text-center">
                        <span class="inline-block px-2 py-0.5 text-[10px] font-bold border rounded-xs {{ $priorityCls }}">
                            {{ $wo->priority }}
                        </span>
                    </td>
                    <td class="px-3 py-2.5 text-center">
                        <span class="inline-block px-2 py-0.5 text-[10px] font-bold border rounded-xs {{ $statusCls }}">
                            {{ $wo->status }}
                        </span>
                    </td>
                    <td class="px-3 py-2.5 text-right flex justify-end gap-1.5">
                        <a href="{{ route('management.work-order.show', $wo->work_order_id) }}"
                           class="inline-flex items-center gap-1 px-2 py-1 bg-slate-100 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 hover:border-blue-300 text-slate-600 dark:text-slate-300 hover:text-blue-700 dark:hover:text-blue-400 font-semibold text-[10px] rounded-xs transition-colors">
                            <i class="fa-solid fa-eye text-[9px]"></i> View
                        </a>
                        <a href="{{ route('management.work-order.show', $wo->work_order_id) }}" target="_blank" onclick="const w = window.open(this.href, '_blank'); w.onload = function() { setTimeout(() => { w.print(); }, 500); }; return false;"
                           class="inline-flex items-center gap-1 px-2 py-1 bg-blue-600 hover:bg-blue-700 border border-blue-700 text-white font-semibold text-[10px] rounded-xs transition-colors">
                            <i class="fa-solid fa-print text-[9px]"></i> Print
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="py-16 text-center">
                        <i class="fa-solid fa-file-signature text-4xl text-slate-300 dark:text-slate-600 block mb-3"></i>
                        <p class="text-sm font-semibold text-slate-400">No Work Orders created yet.</p>
                        <p class="text-xs text-slate-400 mt-1">Go to an Inquiry Detail page and select products to generate an SPK.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>
</div>

@push('scripts')
<script>
    $(function() {
        defaultDataTable('#work-orders-table', {
            order: [[0, 'asc']]
        });
    });
</script>
@endpush
@endsection

