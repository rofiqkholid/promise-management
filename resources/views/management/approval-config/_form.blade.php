{{-- Reusable form fields for add/edit approval rule --}}
<div>
    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Approval Level <span class="text-rose-400">*</span></label>
    <input type="number" name="approval_level" value="{{ $rule->approval_level ?? 1 }}" min="1" required
           placeholder="1"
           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-3 py-2 rounded-xs focus:outline-none focus:border-blue-500">
    <p class="text-[10px] text-slate-400 mt-1">Lower number = approves first. Level 1 is activated when SPK is submitted.</p>
</div>

<div>
    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Position Label <span class="text-rose-400">*</span></label>
    <input type="text" name="position_label" value="{{ $rule->position_label ?? '' }}" required
           placeholder="e.g. Marketing GM, Purchasing Manager"
           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-3 py-2 rounded-xs focus:outline-none focus:border-blue-500">
    <p class="text-[10px] text-slate-400 mt-1">This label appears on the printed SPK signature column.</p>
</div>

<div>
    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Responsible Department <span class="text-rose-400">*</span></label>
    <select name="department_id" required
            class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-3 py-2 rounded-xs focus:outline-none focus:border-blue-500 cursor-pointer">
        @foreach($departments as $d)
            <option value="{{ $d->id }}" {{ isset($rule) && $rule->department_id == $d->id ? 'selected' : '' }}>
                {{ $d->name }} ({{ $d->code }})
            </option>
        @endforeach
    </select>
</div>

<div>
    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">
        Specific Approver
        <span class="text-slate-300 text-[9px] normal-case font-normal ml-1">(optional — leave blank = any user in dept)</span>
    </label>
    <select name="approver_user_id"
            class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-3 py-2 rounded-xs focus:outline-none focus:border-blue-500 cursor-pointer">
        <option value="">— Any user in department —</option>
        @foreach($users as $u)
            <option value="{{ $u->id }}" {{ isset($rule) && $rule->approver_user_id == $u->id ? 'selected' : '' }}>
                {{ $u->name }} ({{ $u->nik }})
            </option>
        @endforeach
    </select>
</div>

<div class="grid grid-cols-2 gap-3">
    <div>
        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Sort Order</label>
        <input type="number" name="sort_order" value="{{ $rule->sort_order ?? 0 }}" min="0"
               class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-3 py-2 rounded-xs focus:outline-none focus:border-blue-500">
    </div>
    <div class="flex items-end pb-1">
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="is_active" value="1"
                   {{ !isset($rule) || $rule->is_active ? 'checked' : '' }}
                   class="rounded-xs text-blue-600 focus:ring-blue-500">
            <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">Active</span>
        </label>
    </div>
</div>
