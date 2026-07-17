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
    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Action Sign-off Header <span class="text-rose-400">*</span></label>
    <select name="action_label" required
            class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-3 py-2 rounded-xs focus:outline-none focus:border-blue-500 cursor-pointer">
        <option value="Checked" {{ isset($rule) && ($rule->action_label ?? 'Checked') == 'Checked' ? 'selected' : '' }}>Checked</option>
        <option value="Approved" {{ isset($rule) && ($rule->action_label ?? '') == 'Approved' ? 'selected' : '' }}>Approved</option>
        <option value="Reviewed" {{ isset($rule) && ($rule->action_label ?? '') == 'Reviewed' ? 'selected' : '' }}>Reviewed</option>
        <option value="Received" {{ isset($rule) && ($rule->action_label ?? '') == 'Received' ? 'selected' : '' }}>Received</option>
    </select>
    <p class="text-[10px] text-slate-400 mt-1">Header printed at the top of the signature column on the document.</p>
</div>

<div>
    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Responsible Department <span class="text-rose-400">*</span></label>
    <select name="department_id" id="add_department_id" required
            class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-3 py-2 rounded-xs focus:outline-none focus:border-blue-500 cursor-pointer select2-department">
        @foreach($departments as $d)
            <option value="{{ $d->id }}" {{ isset($rule) && $rule->department_id == $d->id ? 'selected' : '' }}>
                {{ $d->name }} ({{ $d->code }})
            </option>
        @endforeach
    </select>
</div>

<div x-data="approverSelect('add_approver_user_ids', {{ json_encode($users->map(fn($u) => ['id' => $u->id, 'label' => $u->name . ' (' . $u->nik . ')'])) }}, {{ json_encode(isset($rule) && is_array($rule->approver_user_ids) ? $rule->approver_user_ids : []) }})" class="relative">
    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">
        Specific Approver(s)
        <span class="text-slate-300 text-[9px] normal-case font-normal ml-1">(searchable — select multiple)</span>
    </label>

    {{-- Hidden native select for form submission --}}
    <select name="approver_user_ids[]" id="add_approver_user_ids" multiple class="hidden">
        @foreach($users as $u)
            <option value="{{ $u->id }}" :selected="selectedIds.includes({{ $u->id }})">{{ $u->name }}</option>
        @endforeach
    </select>

    {{-- Search input --}}
    <div class="relative">
        <input type="text" x-model="search" @focus="open = true" @click.outside="open = false" @keydown.escape="open = false"
               placeholder="Search approver..."
               class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-3 py-2 pr-8 rounded-xs focus:outline-none focus:border-blue-500">
        <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
            <i class="fa-solid fa-chevron-down text-[9px]"></i>
        </span>

        {{-- Dropdown list --}}
        <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="absolute z-40 w-full mt-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xs shadow-lg max-h-44 overflow-y-auto">
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
    <div class="mt-1.5 min-h-[32px] flex flex-wrap gap-1.5 p-2 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xs">
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
