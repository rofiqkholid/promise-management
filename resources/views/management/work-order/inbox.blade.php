@extends('layouts.app')

@section('title', 'Approval Inbox · Promise Management')

@section('content')
<div class="flex-1 overflow-y-auto p-4 pt-17.5 space-y-4 transition-colors duration-200"
     x-data="{ activeTab: 'recent' }">
    
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-3">
        <div>
            <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none mb-1">Approvals</div>
            <h1 class="text-lg font-extrabold tracking-tight text-slate-800 dark:text-white leading-none">
                SPK Approval Inbox
            </h1>
            <p class="text-xs text-slate-400 mt-1">Review and approve Work Order (SPK) documents assigned to you.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="p-3 bg-emerald-50 dark:bg-emerald-950/30 border-l-4 border-emerald-500 text-emerald-800 dark:text-emerald-400 text-xs">
            {{ session('success') }}
        </div>
    @endif
    
    @if(session('error'))
        <div class="p-3 bg-rose-50 dark:bg-rose-950/30 border-l-4 border-rose-500 text-rose-800 dark:text-rose-400 text-xs">
            {{ session('error') }}
        </div>
    @endif

    <!-- Tabs Header -->
    <div class="border-b border-slate-200 dark:border-slate-700/80 flex gap-6">
        <button @click="activeTab = 'recent'" 
                :class="activeTab === 'recent' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'"
                class="pb-3 px-1 border-b-2 text-xs uppercase tracking-wider transition-all focus:outline-none flex items-center gap-2">
            <i class="fa-solid fa-clock-rotate-left"></i> Recent (Pending)
            @if($recent->count() > 0)
                <span class="px-1.5 py-0.5 text-[9px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-400 rounded-full">{{ $recent->count() }}</span>
            @endif
        </button>
        <button @click="activeTab = 'approved'" 
                :class="activeTab === 'approved' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'"
                class="pb-3 px-1 border-b-2 text-xs uppercase tracking-wider transition-all focus:outline-none flex items-center gap-2">
            <i class="fa-solid fa-circle-check"></i> Approved By Me
        </button>
        <button @click="activeTab = 'rejected'" 
                :class="activeTab === 'rejected' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'"
                class="pb-3 px-1 border-b-2 text-xs uppercase tracking-wider transition-all focus:outline-none flex items-center gap-2">
            <i class="fa-solid fa-circle-xmark"></i> Rejected By Me
        </button>
        <button @click="activeTab = 'all'" 
                :class="activeTab === 'all' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'"
                class="pb-3 px-1 border-b-2 text-xs uppercase tracking-wider transition-all focus:outline-none flex items-center gap-2">
            <i class="fa-solid fa-folder-open"></i> All Involved
        </button>
    </div>
    
    <!-- Tab Contents -->
    <div class="space-y-4">
        
        <!-- Tab 1: Recent -->
        <div x-show="activeTab === 'recent'">
            <x-table id="recent-table">
                <thead>
                    <tr class="border-b-2 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/30 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                        <th class="px-3 py-2.5 w-8 text-center">#</th>
                        <th class="px-3 py-2.5">SPK No</th>
                        <th class="px-3 py-2.5">Revision</th>
                        <th class="px-3 py-2.5">Subject</th>
                        <th class="px-3 py-2.5">Owner Dept</th>
                        <th class="px-3 py-2.5 text-center">Priority</th>
                        <th class="px-3 py-2.5 text-center">Status</th>
                        <th class="px-3 py-2.5 text-right w-24">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recent as $index => $wo)
                        @php
                            $statusCls = 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border-amber-300 dark:border-amber-900/50';
                            $priorityCls = match($wo->priority) {
                                'URGENT'    => 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border-rose-300 dark:border-rose-900/50',
                                'STANDARD'  => 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border-amber-300 dark:border-amber-900/50',
                                default     => 'bg-slate-50 dark:bg-slate-900 text-slate-500 dark:text-slate-400 border-slate-200 dark:border-slate-700',
                            };
                        @endphp
                        <tr class="border-b border-slate-100 dark:border-slate-700/50 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="px-3 py-2.5 text-center text-slate-400 font-mono text-[10px]">{{ $index + 1 }}</td>
                            <td class="px-3 py-2.5 font-bold text-slate-800 dark:text-slate-100">{{ $wo->wo_number }}</td>
                            <td class="px-3 py-2.5 font-mono text-slate-600 dark:text-slate-300">Rev. {{ $wo->revision_no }}</td>
                            <td class="px-3 py-2.5 text-slate-600 dark:text-slate-300 font-medium">{{ $wo->subject }}</td>
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
                            <td class="px-3 py-2.5 text-right flex justify-end gap-1.5 align-middle">
                                <a href="{{ route('management.work-order.review', $wo->id) }}" title="Review &amp; Approve"
                                   class="w-6 h-6 flex items-center justify-center bg-blue-600 hover:bg-blue-750 text-white transition-colors">
                                    <i class="fa-solid fa-file-signature text-[10px]"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        </div>

        <!-- Tab 2: Approved -->
        <div x-show="activeTab === 'approved'">
            <x-table id="approved-table">
                <thead>
                    <tr class="border-b-2 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/30 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                        <th class="px-3 py-2.5 w-8 text-center">#</th>
                        <th class="px-3 py-2.5">SPK No</th>
                        <th class="px-3 py-2.5">Revision</th>
                        <th class="px-3 py-2.5">Subject</th>
                        <th class="px-3 py-2.5">Owner Dept</th>
                        <th class="px-3 py-2.5 text-center">Priority</th>
                        <th class="px-3 py-2.5 text-center">Status</th>
                        <th class="px-3 py-2.5 text-right w-24">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($approved as $index => $wo)
                        @php
                            $statusCls = $wo->status === 'Approved' 
                                ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border-emerald-300 dark:border-emerald-900/50'
                                : 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border-amber-300 dark:border-amber-900/50';
                            $priorityCls = match($wo->priority) {
                                'URGENT'    => 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border-rose-300 dark:border-rose-900/50',
                                'STANDARD'  => 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border-amber-300 dark:border-amber-900/50',
                                default     => 'bg-slate-50 dark:bg-slate-900 text-slate-500 dark:text-slate-400 border-slate-200 dark:border-slate-700',
                            };
                        @endphp
                        <tr class="border-b border-slate-100 dark:border-slate-700/50 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="px-3 py-2.5 text-center text-slate-400 font-mono text-[10px]">{{ $index + 1 }}</td>
                            <td class="px-3 py-2.5 font-bold text-slate-800 dark:text-slate-100">{{ $wo->wo_number }}</td>
                            <td class="px-3 py-2.5 font-mono text-slate-600 dark:text-slate-300">Rev. {{ $wo->revision_no }}</td>
                            <td class="px-3 py-2.5 text-slate-600 dark:text-slate-300 font-medium">{{ $wo->subject }}</td>
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
                            <td class="px-3 py-2.5 text-right flex justify-end gap-1.5 align-middle">
                                <a href="{{ route('management.work-order.review', $wo->id) }}" title="View &amp; Review"
                                   class="w-6 h-6 flex items-center justify-center bg-slate-100 dark:bg-slate-750 border border-slate-300 dark:border-slate-700 hover:border-blue-500 text-slate-600 dark:text-slate-300 transition-colors">
                                    <i class="fa-solid fa-eye text-[10px]"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        </div>

        <!-- Tab 3: Rejected -->
        <div x-show="activeTab === 'rejected'">
            <x-table id="rejected-table">
                <thead>
                    <tr class="border-b-2 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/30 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                        <th class="px-3 py-2.5 w-8 text-center">#</th>
                        <th class="px-3 py-2.5">SPK No</th>
                        <th class="px-3 py-2.5">Revision</th>
                        <th class="px-3 py-2.5">Subject</th>
                        <th class="px-3 py-2.5">Owner Dept</th>
                        <th class="px-3 py-2.5 text-center">Priority</th>
                        <th class="px-3 py-2.5 text-center">Status</th>
                        <th class="px-3 py-2.5 text-right w-24">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rejected as $index => $wo)
                        @php
                            $statusCls = 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border-rose-300 dark:border-rose-900/50';
                            $priorityCls = match($wo->priority) {
                                'URGENT'    => 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border-rose-300 dark:border-rose-900/50',
                                'STANDARD'  => 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border-amber-300 dark:border-amber-900/50',
                                default     => 'bg-slate-50 dark:bg-slate-900 text-slate-500 dark:text-slate-400 border-slate-200 dark:border-slate-700',
                            };
                        @endphp
                        <tr class="border-b border-slate-100 dark:border-slate-700/50 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="px-3 py-2.5 text-center text-slate-400 font-mono text-[10px]">{{ $index + 1 }}</td>
                            <td class="px-3 py-2.5 font-bold text-slate-800 dark:text-slate-100">{{ $wo->wo_number }}</td>
                            <td class="px-3 py-2.5 font-mono text-slate-600 dark:text-slate-300">Rev. {{ $wo->revision_no }}</td>
                            <td class="px-3 py-2.5 text-slate-600 dark:text-slate-300 font-medium">{{ $wo->subject }}</td>
                            <td class="px-3 py-2.5 text-slate-600 dark:text-slate-300">{{ $wo->ownerDepartment->name ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="inline-block px-2 py-0.5 text-[10px] font-bold border rounded-xs {{ $priorityCls }}">
                                    {{ $wo->priority }}
                                </span>
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="inline-block px-2 py-0.5 text-[10px] font-bold border rounded-xs {{ $statusCls }}">
                                    Rejected / Draft
                                </span>
                            </td>
                            <td class="px-3 py-2.5 text-right flex justify-end gap-1.5 align-middle">
                                <a href="{{ route('management.work-order.review', $wo->id) }}" title="View &amp; Review"
                                   class="w-6 h-6 flex items-center justify-center bg-slate-100 dark:bg-slate-750 border border-slate-300 dark:border-slate-700 hover:border-blue-500 text-slate-600 dark:text-slate-300 transition-colors">
                                    <i class="fa-solid fa-eye text-[10px]"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        </div>

        <!-- Tab 4: All Involved -->
        <div x-show="activeTab === 'all'">
            <x-table id="all-table">
                <thead>
                    <tr class="border-b-2 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/30 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                        <th class="px-3 py-2.5 w-8 text-center">#</th>
                        <th class="px-3 py-2.5">SPK No</th>
                        <th class="px-3 py-2.5">Revision</th>
                        <th class="px-3 py-2.5">Subject</th>
                        <th class="px-3 py-2.5">Owner Dept</th>
                        <th class="px-3 py-2.5 text-center">Priority</th>
                        <th class="px-3 py-2.5 text-center">Status</th>
                        <th class="px-3 py-2.5 text-right w-24">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($all as $index => $wo)
                        @php
                            $statusCls = match($wo->status) {
                                'Approved'          => 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border-emerald-300 dark:border-emerald-900/50',
                                'Pending Approval'  => 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border-amber-300 dark:border-amber-900/50',
                                'Rejected'          => 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border-rose-300 dark:border-rose-900/50',
                                default             => 'bg-slate-50 dark:bg-slate-900 text-slate-500 dark:text-slate-400 border-slate-200 dark:border-slate-700',
                            };
                            $priorityCls = match($wo->priority) {
                                'URGENT'    => 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border-rose-300 dark:border-rose-900/50',
                                'STANDARD'  => 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border-amber-300 dark:border-amber-900/50',
                                default     => 'bg-slate-50 dark:bg-slate-900 text-slate-500 dark:text-slate-400 border-slate-200 dark:border-slate-700',
                            };
                        @endphp
                        <tr class="border-b border-slate-100 dark:border-slate-700/50 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="px-3 py-2.5 text-center text-slate-400 font-mono text-[10px]">{{ $index + 1 }}</td>
                            <td class="px-3 py-2.5 font-bold text-slate-800 dark:text-slate-100">{{ $wo->wo_number }}</td>
                            <td class="px-3 py-2.5 font-mono text-slate-600 dark:text-slate-300">Rev. {{ $wo->revision_no }}</td>
                            <td class="px-3 py-2.5 text-slate-600 dark:text-slate-300 font-medium">{{ $wo->subject }}</td>
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
                            <td class="px-3 py-2.5 text-right flex justify-end gap-1.5 align-middle">
                                <a href="{{ route('management.work-order.review', $wo->id) }}" title="View &amp; Review"
                                   class="w-6 h-6 flex items-center justify-center bg-slate-100 dark:bg-slate-750 border border-slate-300 dark:border-slate-700 hover:border-blue-500 text-slate-600 dark:text-slate-300 transition-colors">
                                    <i class="fa-solid fa-eye text-[10px]"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        </div>

    </div>
</div>

@push('scripts')
<script>
    $(function() {
        const dtConfig = {
            order: [[0, 'asc']],
            language: {
                emptyTable: `
                    <div class="py-16 flex flex-col items-center justify-center text-center w-full">
                        <div>
                            <i class="fa-solid fa-envelope-open text-3xl text-slate-300 dark:text-slate-600 m-4"></i>
                        </div>
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-widest mb-2">No Records Found</h4>
                        <p class="text-xs text-gray-400 dark:text-gray-500 max-w-xs mx-auto font-medium leading-relaxed">No Work Order documents match this category status.</p>
                    </div>
                `
            }
        };

        defaultDataTable('#recent-table', dtConfig);
        defaultDataTable('#approved-table', dtConfig);
        defaultDataTable('#rejected-table', dtConfig);
        defaultDataTable('#all-table', dtConfig);
    });
</script>
@endpush
@endsection
