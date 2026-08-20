@extends('layouts.app')

@section('title', 'System Fields Dictionary - Promise Management')

@section('content')
<div class="flex-1 overflow-y-auto p-4 pt-17.5 space-y-4 transition-colors duration-200" x-data="systemFieldsIndex()">
    
    <!-- Title & Action Bar -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-lg font-bold tracking-tight text-slate-800 dark:text-white">Master System Fields Dictionary</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">Define database column variables used for template mapping across modules</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="showCreateModal = true"
               class="inline-flex items-center gap-2 px-3.5 h-9 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold rounded-sm transition-colors text-xs cursor-pointer shadow-xs">
                <i class="fa-solid fa-plus text-xs"></i>
                Add System Field
            </button>
        </div>
    </div>

    <!-- SweetAlert & Session Toast -->
    <x-sweetalert />
    @if(session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                showToast("{{ session('success') }}", 'success');
            });
        </script>
    @endif

    <!-- Main Table Component with Integrated Popover Filter Toggle -->
    <x-table id="system-fields-table" class="w-full text-xs text-left border-collapse">
        <x-slot:filters>
            <div class="space-y-3">
                {{-- Filter Group Category --}}
                <div>
                    <label for="filter-group" class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Group Category</label>
                    <select id="filter-group" class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm text-xs font-semibold text-slate-700 dark:text-slate-200 focus:outline-none focus:border-blue-500">
                        <option value="">- All Groups -</option>
                        @foreach($groups ?? [] as $grp)
                            <option value="{{ $grp }}">{{ ucfirst(str_replace('_', ' ', $grp)) }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Target Table --}}
                <div>
                    <label for="filter-target-table" class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Target DB Table</label>
                    <select id="filter-target-table" class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm text-xs font-semibold text-slate-700 dark:text-slate-200 focus:outline-none focus:border-blue-500">
                        <option value="">- All Target Tables -</option>
                        @foreach($targetTables ?? [] as $tbl)
                            <option value="{{ $tbl }}">{{ $tbl }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Data Type --}}
                <div>
                    <label for="filter-datatype" class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Data Type</label>
                    <select id="filter-datatype" class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm text-xs font-semibold text-slate-700 dark:text-slate-200 focus:outline-none focus:border-blue-500">
                        <option value="">- All Data Types -</option>
                        <option value="string">STRING</option>
                        <option value="decimal">DECIMAL</option>
                        <option value="numeric">NUMERIC</option>
                        <option value="date">DATE</option>
                        <option value="boolean">BOOLEAN</option>
                    </select>
                </div>

                {{-- Filter Is Required --}}
                <div>
                    <label for="filter-required" class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Requirement</label>
                    <select id="filter-required" class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm text-xs font-semibold text-slate-700 dark:text-slate-200 focus:outline-none focus:border-blue-500">
                        <option value="">- All -</option>
                        <option value="Required">Required Only</option>
                        <option value="Optional">Optional Only</option>
                    </select>
                </div>
            </div>
        </x-slot:filters>

        <thead>
            <tr class="border-b-2 border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900 text-[10px] font-extrabold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                <th class="px-3 py-2.5 text-center border-r border-slate-200 dark:border-slate-700 w-12">#</th>
                <th class="px-3 py-2.5 border-r border-slate-200 dark:border-slate-700">Field Key</th>
                <th class="px-3 py-2.5 border-r border-slate-200 dark:border-slate-700">Label Name</th>
                <th class="px-3 py-2.5 border-r border-slate-200 dark:border-slate-700">Group</th>
                <th class="px-3 py-2.5 border-r border-slate-200 dark:border-slate-700">Target Table</th>
                <th class="px-3 py-2.5 border-r border-slate-200 dark:border-slate-700">Target Column</th>
                <th class="px-3 py-2.5 border-r border-slate-200 dark:border-slate-700">Data Type</th>
                <th class="px-3 py-2.5 text-center">Required</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
            @foreach($fields as $field)
            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors">
                <td class="px-3 py-2.5 text-center font-mono text-[10px] text-slate-400 border-r border-slate-200 dark:border-slate-700">{{ $loop->iteration }}</td>
                <td class="px-3 py-2.5 font-mono text-xs text-blue-600 dark:text-blue-400 font-bold border-r border-slate-200 dark:border-slate-700">{{ $field->field_key }}</td>
                <td class="px-3 py-2.5 font-semibold text-slate-800 dark:text-white border-r border-slate-200 dark:border-slate-700">{{ $field->label }}</td>
                <td class="px-3 py-2.5 border-r border-slate-200 dark:border-slate-700">
                    <span class="px-2 py-0.5 text-[10px] font-bold bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-sm">
                        {{ $field->group }}
                    </span>
                </td>
                <td class="px-3 py-2.5 font-mono text-xs text-slate-600 dark:text-slate-300 border-r border-slate-200 dark:border-slate-700">{{ $field->target_table ?? '-' }}</td>
                <td class="px-3 py-2.5 font-mono text-xs text-slate-600 dark:text-slate-300 border-r border-slate-200 dark:border-slate-700">{{ $field->target_column ?? '-' }}</td>
                <td class="px-3 py-2.5 text-xs uppercase font-mono text-slate-500 dark:text-slate-400 border-r border-slate-200 dark:border-slate-700">{{ $field->data_type }}</td>
                <td class="px-3 py-2.5 text-center">
                    @if($field->is_required)
                        <span class="hidden">Required</span>
                        <i class="fa-solid fa-circle-check text-emerald-500" title="Required Field"></i>
                    @else
                        <span class="hidden">Optional</span>
                        <i class="fa-solid fa-circle-xmark text-slate-300 dark:text-slate-600" title="Optional Field"></i>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </x-table>

    <!-- Modal: Add System Field -->
    <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
        <div class="bg-white dark:bg-slate-800 w-full max-w-md border border-slate-200 dark:border-slate-700 rounded-sm shadow-2xl p-6 relative">
            <button @click="showCreateModal = false" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 dark:hover:text-white cursor-pointer">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>

            <h2 class="text-base font-bold text-slate-800 dark:text-white mb-1 flex items-center gap-2">
                <i class="fa-solid fa-plus-circle text-blue-600"></i> Add Master System Field
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Define a new database column variable for mapping.</p>

            <form action="{{ route('management.system-fields.store') }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Field Key <span class="text-rose-500">*</span></label>
                    <input type="text" name="field_key" required placeholder="e.g. die_height"
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm px-3 py-2 text-xs font-semibold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Label Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="label" required placeholder="e.g. Die Height (mm)"
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm px-3 py-2 text-xs font-semibold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Group Category <span class="text-rose-500">*</span></label>
                    <input type="text" name="group" required placeholder="e.g. header / tooling_detail / material"
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm px-3 py-2 text-xs font-semibold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Target DB Table</label>
                    <input type="text" name="target_table" placeholder="e.g. mng_tooling_quotations"
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm px-3 py-2 text-xs font-semibold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Target DB Column</label>
                    <input type="text" name="target_column" placeholder="e.g. die_height"
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm px-3 py-2 text-xs font-semibold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Data Type <span class="text-rose-500">*</span></label>
                    <select name="data_type" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm px-3 py-2 text-xs font-semibold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="string">String</option>
                        <option value="decimal">Decimal</option>
                        <option value="numeric">Numeric (Integer)</option>
                        <option value="date">Date</option>
                        <option value="boolean">Boolean</option>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-200 dark:border-slate-700">
                    <button type="button" @click="showCreateModal = false" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold hover:bg-slate-50 rounded-sm cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-sm cursor-pointer">
                        Save System Field
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function systemFieldsIndex() {
        return {
            showCreateModal: false,

            init() {
                this.$nextTick(() => {
                    if (typeof window.defaultDataTable === 'function') {
                        const table = window.defaultDataTable('#system-fields-table', {
                            order: [[0, 'asc']]
                        });

                        $(document).on('change', '#filter-group', function () {
                            const val = $(this).val();
                            table.column(3).search(val ? '^' + val + '$' : '', true, false).draw();
                        });

                        $(document).on('change', '#filter-target-table', function () {
                            const val = $(this).val();
                            table.column(4).search(val ? '^' + val + '$' : '', true, false).draw();
                        });

                        $(document).on('change', '#filter-datatype', function () {
                            const val = $(this).val();
                            table.column(6).search(val ? '^' + val + '$' : '', true, false).draw();
                        });

                        $(document).on('change', '#filter-required', function () {
                            const val = $(this).val();
                            table.column(7).search(val ? val : '', true, false).draw();
                        });

                        $(document).on('click', '#dt-filter-popover-system-fields-table-reset', function() {
                            $('#filter-group').val('');
                            $('#filter-target-table').val('');
                            $('#filter-datatype').val('');
                            $('#filter-required').val('');
                            table.search('').columns().search('').draw();
                        });
                    }
                });
            }
        };
    }
</script>
@endsection
