@extends('layouts.app')

@section('title', 'SPK Approval Matrix · Promise Management')

@section('content')
<x-sweetalert />
<div class="flex-1 overflow-y-auto p-4 pt-17.5 space-y-5 transition-colors duration-200">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-lg font-bold tracking-tight text-slate-800 dark:text-white">SPK Approval Matrix</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">Configure who approves SPK documents and in what order.</p>
        </div>

        <button onclick="document.getElementById('modal-add').classList.remove('hidden')"
                class="inline-flex items-center gap-2 px-3.5 h-9 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-sm transition-colors cursor-pointer">
            <i class="fa-solid fa-plus text-xs"></i> Add Approval Level
        </button>
    </div>


    @if($errors->any())
        <div class="p-3.5 bg-rose-50 dark:bg-rose-950/30 border-l-4 border-rose-500 text-rose-700 dark:text-rose-400 text-xs rounded-r-xs">
            <div class="font-bold mb-1"><i class="fa-solid fa-triangle-exclamation mr-1.5"></i> Please correct the following errors:</div>
            <ul class="list-disc pl-4 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- How it works banner --}}
    <div class="p-3.5 bg-blue-50/70 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-900/50 rounded-sm flex gap-3">
        <i class="fa-solid fa-circle-info text-blue-500 mt-0.5 flex-shrink-0 text-sm"></i>
        <div class="text-xs text-slate-600 dark:text-slate-400 space-y-1">
            <p class="font-bold text-slate-700 dark:text-slate-300">How Approval Matrix Works</p>
            <p>Rules are applied <strong>sequentially</strong> by level (Level 1 → Level 2 → …). Level 1 is activated first when an SPK is submitted. Each subsequent level is only activated after the previous level is approved.</p>
            <p>If <strong>Specific User</strong> is set, only that exact user can approve. If left blank, any user belonging to the assigned department can approve.</p>
        </div>
    </div>

    <x-table id="approval-config-table" class="w-full text-xs text-left border-collapse">
        <thead>
            <tr class="border-b-2 border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900 text-[10px] font-extrabold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                <th class="px-3 py-2.5 w-16 text-center border-r border-slate-200 dark:border-slate-700">Level</th>
                <th class="px-4 py-2.5 border-r border-slate-200 dark:border-slate-700">Position Label</th>
                <th class="px-4 py-2.5 border-r border-slate-200 dark:border-slate-700">Action Header</th>
                <th class="px-4 py-2.5 border-r border-slate-200 dark:border-slate-700">Department</th>
                <th class="px-4 py-2.5 border-r border-slate-200 dark:border-slate-700">Specific Approver</th>
                <th class="px-3 py-2.5 w-20 text-center border-r border-slate-200 dark:border-slate-700">Order</th>
                <th class="px-3 py-2.5 w-20 text-center border-r border-slate-200 dark:border-slate-700">Status</th>
                <th class="px-3 py-2.5 w-20 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
        </tbody>
    </x-table>

</div>

{{-- ── ADD RULE MODAL ──────────────────────────────────────────── --}}
<div id="modal-add" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white dark:bg-slate-800 rounded-sm shadow-2xl w-full max-w-md border border-slate-200 dark:border-slate-700">
        <div class="p-5 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center">
            <h3 class="text-sm font-bold text-slate-800 dark:text-white">Add Approval Rule</h3>
            <button onclick="document.getElementById('modal-add').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg leading-none cursor-pointer">&times;</button>
        </div>
        <form action="{{ route('management.approval-config.store') }}" method="POST" class="p-5 space-y-4">
            @csrf
            <input type="hidden" name="document_type" value="SPK">
            @include('management.approval-config._form', ['rule' => null, 'departments' => $departments, 'users' => $users])
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-add').classList.add('hidden')"
                        class="px-4 py-2 text-xs font-bold border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-sm transition-colors">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-sm shadow-xs transition-colors cursor-pointer">
                    Save Rule
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── EDIT RULE MODAL ─────────────────────────────────────────── --}}
<div id="modal-edit" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white dark:bg-slate-800 rounded-sm shadow-2xl w-full max-w-md border border-slate-200 dark:border-slate-700">
        <div class="p-5 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center">
            <h3 class="text-sm font-bold text-slate-800 dark:text-white">Edit Approval Rule</h3>
            <button onclick="document.getElementById('modal-edit').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg leading-none cursor-pointer">&times;</button>
        </div>
        <form id="edit-form" action="" method="POST" class="p-5 space-y-4">
            @csrf
            <input type="hidden" name="document_type" value="SPK">
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Approval Level</label>
                <input type="number" name="approval_level" id="edit_approval_level" min="1" required
                       class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-3 py-2 rounded-sm focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Position Label <span class="text-rose-400">*</span></label>
                <input type="text" name="position_label" id="edit_position_label" required placeholder="e.g. Marketing GM"
                       class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-3 py-2 rounded-sm focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Action Sign-off Header <span class="text-rose-400">*</span></label>
                <select name="action_label" id="edit_action_label" required
                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-3 py-2 rounded-sm focus:outline-none focus:border-blue-500 cursor-pointer">
                    <option value="Checked">Checked</option>
                    <option value="Approved">Approved</option>
                    <option value="Reviewed">Reviewed</option>
                    <option value="Received">Received</option>
                </select>
                <p class="text-[10px] text-slate-400 mt-1">Header printed at the top of the signature column on the document.</p>
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Responsible Department <span class="text-rose-400">*</span></label>
                <select name="department_id" id="edit_department_id" required
                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-3 py-2 rounded-sm focus:outline-none focus:border-blue-500 cursor-pointer select2-department">
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->code }})</option>
                    @endforeach
                </select>
            </div>
            <div x-data="approverSelect('edit_approver_user_ids', {{ json_encode($users->map(fn($u) => ['id' => $u->id, 'label' => $u->name . ' (' . $u->nik . ')'])) }}, [])" x-on:set-approvers.window="if ($event.detail.target === 'edit_approver_user_ids') setSelected($event.detail.ids)" class="relative">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                    Specific Approver(s)
                    <span class="text-slate-300 text-[9px] normal-case font-normal ml-1">(searchable — select multiple)</span>
                </label>

                {{-- Hidden native select for form submission --}}
                <select name="approver_user_ids[]" id="edit_approver_user_ids" multiple class="hidden">
                    @foreach($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>

                {{-- Search input --}}
                <div class="relative">
                    <input type="text" x-model="search" @focus="open = true" @click.outside="open = false" @keydown.escape="open = false"
                           placeholder="Search approver..."
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-3 py-2 pr-8 rounded-sm focus:outline-none focus:border-blue-500">
                    <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                        <i class="fa-solid fa-chevron-down text-[9px]"></i>
                    </span>

                    {{-- Dropdown list --}}
                    <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                         class="absolute z-40 w-full mt-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-sm shadow-lg max-h-44 overflow-y-auto">
                        <template x-for="item in filtered" :key="item.id">
                            <div @click="toggle(item)"
                                 :class="selectedIds.includes(item.id) ? 'bg-blue-50 dark:bg-blue-950/30 text-blue-700 dark:text-blue-400' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/50'"
                                 class="flex items-center justify-between px-3 py-2 text-xs cursor-pointer">
                                <span x-text="item.label"></span>
                                <i x-show="selectedIds.includes(item.id)" class="fa-solid fa-check text-[9px] text-blue-500 flex-shrink-0 ml-2"></i>
                            </div>
                        </template>
                        <div x-show="filtered.length === 0" class="px-3 py-3 text-xs text-slate-400 italic text-center">No results found</div>
                    </div>
                </div>

                {{-- Selected tags --}}
                <div class="mt-1.5 min-h-[32px] flex flex-wrap gap-1.5 p-2 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-sm">
                    <template x-if="selectedIds.length === 0">
                        <span class="text-[10px] text-slate-400 italic self-center">No approver selected — any user in department may approve</span>
                    </template>
                    <template x-for="item in selectedItems" :key="item.id">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-100 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 text-[10px] font-semibold rounded-full border border-blue-200 dark:border-blue-800">
                            <span x-text="item.label"></span>
                            <button type="button" @click="remove(item.id)" class="hover:text-blue-900 dark:hover:text-white leading-none cursor-pointer">&times;</button>
                        </span>
                    </template>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Sort Order</label>
                    <input type="number" name="sort_order" id="edit_sort_order" min="0"
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-3 py-2 rounded-sm focus:outline-none focus:border-blue-500">
                </div>
                <div class="flex items-end pb-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" id="edit_is_active" value="1" class="rounded-sm text-blue-600">
                        <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">Active</span>
                    </label>
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-edit').classList.add('hidden')"
                        class="px-4 py-2 text-xs font-bold border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-sm transition-colors">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-sm shadow-xs transition-colors cursor-pointer">
                    Update Rule
                </button>
            </div>
        </form>
    </div>
</div>

<script>
window.approvalRulesData = {};

window.openEditModalFromId = function (id) {
    const rule = window.approvalRulesData[id];
    if (rule) openEditModal(rule);
};

function openEditModal(rule) {
    document.getElementById('edit-form').action = '{{ url('management/approval-config') }}/' + rule.rule_id + '/update';
    document.getElementById('edit_approval_level').value = rule.approval_level;
    document.getElementById('edit_position_label').value = rule.position_label;
    document.getElementById('edit_action_label').value = rule.action_label ?? 'Checked';
    document.getElementById('edit_sort_order').value = rule.sort_order;
    document.getElementById('edit_is_active').checked = rule.is_active;

    // Set department via Select2
    $('#edit_department_id').val(rule.department_id).trigger('change');

    // Dispatch custom event to Alpine.js approverSelect component for edit modal
    let userIds = (rule.approver_user_ids || []).map(Number);
    window.dispatchEvent(new CustomEvent('set-approvers', { detail: { target: 'edit_approver_user_ids', ids: userIds } }));

    document.getElementById('modal-edit').classList.remove('hidden');
}
</script>


@push('scripts')
<script>
$(document).ready(function () {
    // Department selects (single, searchable via Select2)
    $('.select2-department').select2({
        placeholder: 'Select department...',
        allowClear: true,
        width: '100%',
    });

    @if(session('success'))
        showToast("{{ session('success') }}", 'success');
    @endif
    @if(session('error'))
        showToast("{{ session('error') }}", 'error');
    @endif

    defaultDataTable('#approval-config-table', {
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("management.approval-config.index") }}'
        },
        columns: [
            {
                data: 'approval_level',
                name: 'approval_level',
                className: 'text-center',
                render: function(data) {
                    let cls = data === 1 ? 'bg-blue-100 text-blue-700 dark:bg-blue-950/40 dark:text-blue-400' :
                              (data === 2 ? 'bg-purple-100 text-purple-700 dark:bg-purple-950/40 dark:text-purple-400' :
                              'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300');
                    return `<span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-black ${cls}">${data}</span>`;
                }
            },
            {
                data: 'position_label',
                name: 'position_label',
                className: 'font-semibold text-slate-800 dark:text-slate-100',
                render: function(data) {
                    return data;
                }
            },
            {
                data: 'action_label',
                name: 'action_label',
                render: function(data) {
                    return `<span class="px-2 py-0.5 text-[10px] font-bold bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-sm border border-slate-300 dark:border-slate-600">${data}</span>`;
                }
            },
            {
                data: 'department_name',
                name: 'department_name',
                render: function(data, type, row) {
                    return `<span class="font-medium">${data || '—'}</span>
                            <span class="text-slate-400 text-[10px] ml-1">(${row.department_code || ''})</span>`;
                }
            },
            {
                data: 'approver_users',
                name: 'approver_users',
                orderable: false,
                render: function(data) {
                    if (data && data.length > 0) {
                        let html = '<div class="flex flex-col gap-1">';
                        data.forEach(function(u) {
                            let firstChar = u.name ? u.name.charAt(0).toUpperCase() : '?';
                            html += `<div class="flex items-center gap-2">
                                        <div class="w-5 h-5 rounded-full bg-blue-500 text-white flex items-center justify-center text-[8px] font-bold flex-shrink-0">
                                            ${firstChar}
                                        </div>
                                        <span class="text-xs">${u.name}</span>
                                    </div>`;
                        });
                        html += '</div>';
                        return html;
                    }
                    return `<span class="text-[10px] italic text-slate-400">Any user in department</span>`;
                }
            },
            {
                data: 'sort_order',
                name: 'sort_order',
                className: 'text-center text-slate-500 font-mono'
            },
            {
                data: 'is_active',
                name: 'is_active',
                className: 'text-center',
                render: function(data) {
                    if (data) {
                        return `<span class="inline-block px-2 py-0.5 text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900 rounded-sm">Active</span>`;
                    }
                    return `<span class="inline-block px-2 py-0.5 text-[10px] font-bold bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-600 rounded-sm">Inactive</span>`;
                }
            },
            {
                data: 'id',
                name: 'id',
                orderable: false,
                searchable: false,
                className: 'text-right',
                render: function(data, type, row) {
                    window.approvalRulesData[row.id] = row.raw_rule;
                    return `<div class="flex justify-end gap-1.5 align-middle">
                                <button onclick="window.openEditModalFromId(${row.id})" title="Edit"
                                        class="w-6 h-6 flex items-center justify-center bg-slate-100 dark:bg-slate-700 border border-slate-300 dark:border-slate-600 hover:border-blue-400 hover:text-blue-700 text-slate-600 dark:text-slate-300 transition-colors cursor-pointer">
                                    <i class="fa-solid fa-pen text-[10px]"></i>
                                </button>
                                <form action="${row.destroy_url}" method="POST" class="inline">
                                    <input type="hidden" name="_token" value="${$('meta[name="csrf-token"]').attr('content')}">
                                    <button type="button" onclick="confirmDeleteRule(this.form)" title="Delete"
                                            class="w-6 h-6 flex items-center justify-center bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900 hover:border-rose-500 text-rose-600 dark:text-rose-450 transition-colors cursor-pointer">
                                        <i class="fa-solid fa-trash-can text-[10px]"></i>
                                    </button>
                                </form>
                            </div>`;
                }
            }
        ],
        order: [[0, 'asc'], [5, 'asc']]
    });
});

// Alpine.js component: custom multi-select with searchable dropdown + tag list
function approverSelect(nativeId, allItems, initialIds) {
    return {
        allItems,
        selectedIds: initialIds.map(Number),
        search: '',
        open: false,
        get filtered() {
            const q = this.search.toLowerCase();
            return this.allItems.filter(i => i.label.toLowerCase().includes(q));
        },
        get selectedItems() {
            return this.allItems.filter(i => this.selectedIds.includes(i.id));
        },
        toggle(item) {
            const idx = this.selectedIds.indexOf(item.id);
            if (idx === -1) {
                this.selectedIds.push(item.id);
            } else {
                this.selectedIds.splice(idx, 1);
            }
            this.syncNativeSelect();
        },
        remove(id) {
            this.selectedIds = this.selectedIds.filter(x => x !== id);
            this.syncNativeSelect();
        },
        setSelected(ids) {
            this.selectedIds = ids.map(Number);
            this.syncNativeSelect();
        },
        syncNativeSelect() {
            const sel = document.getElementById(nativeId);
            if (!sel) return;
            Array.from(sel.options).forEach(opt => {
                opt.selected = this.selectedIds.includes(Number(opt.value));
            });
        }
    };
}

function confirmDeleteRule(form) {
    window.confirmDialog({
        title: 'Delete Rule?',
        text: 'Are you sure you want to delete this approval rule?',
        icon: 'warning',
        confirmButtonColor: '#dc2626', // Rose 600
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'No',
        onConfirm: () => form.submit()
    });
}
</script>
@endpush
@endsection
