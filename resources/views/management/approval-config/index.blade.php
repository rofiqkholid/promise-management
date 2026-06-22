@extends('layouts.app')

@section('title', 'SPK Approval Matrix · Promise Management')

@section('content')
<div class="flex-1 overflow-y-auto p-4 pt-17.5 space-y-5 transition-colors duration-200">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
            <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none mb-1">Settings</div>
            <h1 class="text-lg font-extrabold tracking-tight text-slate-800 dark:text-white leading-none">
                SPK Approval Matrix
            </h1>
            <p class="text-xs text-slate-400 mt-0.5">Configure who approves SPK documents and in what order.</p>
        </div>

        <button onclick="document.getElementById('modal-add').classList.remove('hidden')"
                class="inline-flex items-center gap-2 px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xs shadow-xs transition-colors cursor-pointer">
            <i class="fa-solid fa-plus text-[10px]"></i> Add Approval Level
        </button>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="p-3 bg-emerald-50 dark:bg-emerald-950/30 border-l-4 border-emerald-500 text-emerald-800 dark:text-emerald-400 text-xs rounded-r-xs">
            <i class="fa-solid fa-check-circle mr-1.5"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="p-3 bg-rose-50 dark:bg-rose-950/30 border-l-4 border-rose-500 text-rose-700 dark:text-rose-400 text-xs rounded-r-xs">
            <i class="fa-solid fa-triangle-exclamation mr-1.5"></i> {{ session('error') }}
        </div>
    @endif

    {{-- How it works banner --}}
    <div class="p-4 bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-900/50 rounded-xs flex gap-3">
        <i class="fa-solid fa-circle-info text-blue-500 mt-0.5 flex-shrink-0"></i>
        <div class="text-[11px] text-slate-600 dark:text-slate-400 space-y-1">
            <p class="font-bold text-slate-700 dark:text-slate-300">How Approval Matrix Works</p>
            <p>Rules are applied <strong>sequentially</strong> by level (Level 1 → Level 2 → …). Level 1 is activated first when an SPK is submitted. Each subsequent level is only activated after the previous level is approved.</p>
            <p>If <strong>Specific User</strong> is set, only that exact user can approve. If left blank, any user belonging to the assigned department can approve.</p>
        </div>
    </div>

    {{-- Rules Table --}}
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 rounded-xs shadow-xs">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40 flex justify-between items-center rounded-t-xs">
            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Approval Rules — SPK Document</span>
            <span class="text-[10px] text-slate-400">{{ $rules->count() }} rule(s) configured</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left border-collapse">
                <thead>
                    <tr class="border-b-2 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/30 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="px-4 py-2.5 w-16 text-center">Level</th>
                        <th class="px-4 py-2.5">Position Label</th>
                        <th class="px-4 py-2.5">Department</th>
                        <th class="px-4 py-2.5">Specific Approver</th>
                        <th class="px-4 py-2.5 w-20 text-center">Order</th>
                        <th class="px-4 py-2.5 w-20 text-center">Status</th>
                        <th class="px-4 py-2.5 w-28 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rules as $rule)
                        <tr class="border-b border-slate-100 dark:border-slate-700/50 hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-black
                                    {{ $rule->approval_level === 1 ? 'bg-blue-100 text-blue-700 dark:bg-blue-950/40 dark:text-blue-400' :
                                       ($rule->approval_level === 2 ? 'bg-purple-100 text-purple-700 dark:bg-purple-950/40 dark:text-purple-400' :
                                       'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300') }}">
                                    {{ $rule->approval_level }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-100">
                                {{ $rule->position_label }}
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                <span class="font-medium">{{ $rule->department->name ?? '—' }}</span>
                                <span class="text-slate-400 text-[10px] ml-1">({{ $rule->department->code ?? '' }})</span>
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                @if($rule->approverUser)
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-blue-500 text-white flex items-center justify-center text-[9px] font-bold flex-shrink-0">
                                            {{ strtoupper(substr($rule->approverUser->name, 0, 1)) }}
                                        </div>
                                        <span class="text-xs">{{ $rule->approverUser->name }}</span>
                                    </div>
                                @else
                                    <span class="text-[10px] italic text-slate-400">Any user in department</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-slate-500 font-mono">{{ $rule->sort_order }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($rule->is_active)
                                    <span class="inline-block px-2 py-0.5 text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900 rounded-xs">Active</span>
                                @else
                                    <span class="inline-block px-2 py-0.5 text-[10px] font-bold bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-600 rounded-xs">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-1.5">
                                    <button onclick="openEditModal({{ $rule }})"
                                            class="inline-flex items-center gap-1 px-2 py-1 text-[10px] font-bold bg-slate-100 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 hover:border-blue-400 text-slate-600 dark:text-slate-300 hover:text-blue-700 rounded-xs transition-colors">
                                        <i class="fa-solid fa-pen text-[9px]"></i> Edit
                                    </button>
                                    <form action="{{ route('management.approval-config.destroy', $rule->rule_id) }}" method="POST"
                                          onsubmit="return confirm('Delete this approval rule?')">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex items-center gap-1 px-2 py-1 text-[10px] font-bold bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900 hover:border-rose-500 text-rose-600 dark:text-rose-400 rounded-xs transition-colors">
                                            <i class="fa-solid fa-trash-can text-[9px]"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-16 text-center">
                                <i class="fa-solid fa-shield-halved text-4xl text-slate-300 dark:text-slate-600 block mb-3"></i>
                                <p class="text-sm font-semibold text-slate-400">No approval rules configured yet.</p>
                                <p class="text-xs text-slate-400 mt-1">Click "Add Approval Level" to define who can approve SPK documents.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- ── ADD RULE MODAL ──────────────────────────────────────────── --}}
<div id="modal-add" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white dark:bg-slate-800 rounded-xs shadow-2xl w-full max-w-md border border-slate-200 dark:border-slate-700">
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
                        class="px-4 py-2 text-xs font-bold border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-xs transition-colors">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xs shadow-xs transition-colors cursor-pointer">
                    Save Rule
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── EDIT RULE MODAL ─────────────────────────────────────────── --}}
<div id="modal-edit" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white dark:bg-slate-800 rounded-xs shadow-2xl w-full max-w-md border border-slate-200 dark:border-slate-700">
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
                       class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-3 py-2 rounded-xs focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Position Label <span class="text-rose-400">*</span></label>
                <input type="text" name="position_label" id="edit_position_label" required placeholder="e.g. Marketing GM"
                       class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-3 py-2 rounded-xs focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Responsible Department <span class="text-rose-400">*</span></label>
                <select name="department_id" id="edit_department_id" required
                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-3 py-2 rounded-xs focus:outline-none focus:border-blue-500 cursor-pointer">
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->code }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Specific Approver <span class="text-slate-300 text-[9px] normal-case">(leave blank = any user in dept)</span></label>
                <select name="approver_user_id" id="edit_approver_user_id"
                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-3 py-2 rounded-xs focus:outline-none focus:border-blue-500 cursor-pointer">
                    <option value="">— Any user in department —</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->nik }})</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Sort Order</label>
                    <input type="number" name="sort_order" id="edit_sort_order" min="0"
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-3 py-2 rounded-xs focus:outline-none focus:border-blue-500">
                </div>
                <div class="flex items-end pb-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" id="edit_is_active" value="1" class="rounded-xs text-blue-600">
                        <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">Active</span>
                    </label>
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-edit').classList.add('hidden')"
                        class="px-4 py-2 text-xs font-bold border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-xs transition-colors">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xs shadow-xs transition-colors cursor-pointer">
                    Update Rule
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(rule) {
    document.getElementById('edit-form').action = '/management/approval-config/' + rule.rule_id + '/update';
    document.getElementById('edit_approval_level').value = rule.approval_level;
    document.getElementById('edit_position_label').value = rule.position_label;
    document.getElementById('edit_department_id').value = rule.department_id;
    document.getElementById('edit_approver_user_id').value = rule.approver_user_id ?? '';
    document.getElementById('edit_sort_order').value = rule.sort_order;
    document.getElementById('edit_is_active').checked = rule.is_active;
    document.getElementById('modal-edit').classList.remove('hidden');
}
</script>
@endsection
