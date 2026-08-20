@csrf
<div class="space-y-4 text-xs">
    {{-- Customer Context & Rate Source --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                Customer Context
            </label>
            <select name="customer_id" id="field-customer_id"
                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm focus:ring-2 focus:ring-blue-500 font-medium text-slate-800 dark:text-slate-200">
                <option value="">Global (General Standard Eng / Sales)</option>
                @foreach($customers as $c)
                    <option value="{{ $c->id }}" {{ (old('customer_id', $item->customer_id ?? '') == $c->id) ? 'selected' : '' }}>
                        {{ $c->name }} ({{ $c->code ?? '-' }})
                    </option>
                @endforeach
            </select>
            <span class="text-[10px] text-slate-400 mt-0.5 block">Leave empty if global baseline.</span>
        </div>

        <div>
            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                Rate Source <span class="text-rose-500">*</span>
            </label>
            <select name="rate_source" id="field-rate_source" required
                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm focus:ring-2 focus:ring-blue-500 font-medium text-slate-800 dark:text-slate-200">
                <option value="Sales" {{ (old('rate_source', $item->rate_source ?? 'Sales') == 'Sales') ? 'selected' : '' }}>Sales</option>
                <option value="Engineering" {{ (old('rate_source', $item->rate_source ?? '') == 'Engineering') ? 'selected' : '' }}>Engineering</option>
            </select>
        </div>
    </div>

    {{-- Section 1: Product Policies (Parts) --}}
    <div class="p-3 bg-blue-50/40 dark:bg-slate-900/60 border border-blue-200/60 dark:border-blue-900/40 rounded-sm space-y-3">
        <span class="block font-extrabold text-[11px] uppercase tracking-wider text-blue-700 dark:text-blue-400 flex items-center gap-1.5">
            <i class="fa-solid fa-cube text-blue-600 dark:text-blue-400"></i> Product Cost Policy (Parts)
        </span>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            {{-- Admin Matrl --}}
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Admin Material Rate (%) <span class="text-rose-500">*</span>
                </label>
                <input type="number" step="0.01" name="admin_matrl_pct" id="field-admin_matrl_pct"
                    value="{{ old('admin_matrl_pct', $item->admin_matrl_pct ?? 2.00) }}" required placeholder="e.g. 2.00"
                    class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-sm focus:ring-2 focus:ring-blue-500 font-semibold text-slate-800 dark:text-slate-200">
                <span class="text-[10px] text-slate-400 mt-0.5 block">Default Eng: 2%</span>
            </div>

            {{-- Admin Mfg --}}
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Admin Mfg Rate (%) <span class="text-rose-500">*</span>
                </label>
                <input type="number" step="0.01" name="admin_mfg_pct" id="field-admin_mfg_pct"
                    value="{{ old('admin_mfg_pct', $item->admin_mfg_pct ?? 4.00) }}" required placeholder="e.g. 4.00"
                    class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-sm focus:ring-2 focus:ring-blue-500 font-semibold text-slate-800 dark:text-slate-200">
                <span class="text-[10px] text-slate-400 mt-0.5 block">Default Eng: 4%</span>
            </div>

            {{-- Product Overhead + Profit --}}
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Product O/H + Profit (%) <span class="text-rose-500">*</span>
                </label>
                <input type="number" step="0.01" name="oh_profit_pct" id="field-oh_profit_pct"
                    value="{{ old('oh_profit_pct', $item->oh_profit_pct ?? 0.00) }}" required placeholder="e.g. 0.00 (Eng) or 10.00 (Sales)"
                    class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-sm focus:ring-2 focus:ring-blue-500 font-semibold text-slate-800 dark:text-slate-200">
                <span class="text-[10px] text-slate-400 mt-0.5 block">Eng: 0%, Sales: Follow Strategy</span>
            </div>

            {{-- Product Min Std Margin --}}
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Product Target Std Margin (%) <span class="text-rose-500">*</span>
                </label>
                <input type="number" step="0.01" name="min_std_margin_pct" id="field-min_std_margin_pct"
                    value="{{ old('min_std_margin_pct', $item->min_std_margin_pct ?? 12.00) }}" required placeholder="e.g. 12.00"
                    class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-sm focus:ring-2 focus:ring-blue-500 font-bold text-slate-800 dark:text-slate-200">
                <span class="text-[10px] text-slate-400 mt-0.5 block">Standard Policy: Min. 12%</span>
            </div>
        </div>
    </div>

    {{-- Section 2: Tooling Policies (Dies / Jigs / Fixtures) --}}
    <div class="p-3 bg-indigo-50/40 dark:bg-slate-900/60 border border-indigo-200/60 dark:border-indigo-900/40 rounded-sm space-y-3">
        <span class="block font-extrabold text-[11px] uppercase tracking-wider text-indigo-700 dark:text-indigo-400 flex items-center gap-1.5">
            <i class="fa-solid fa-wrench text-indigo-600 dark:text-indigo-400"></i> Tooling Cost Policy (Dies / Mold)
        </span>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            {{-- Tooling Overhead + Profit --}}
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Tooling O/H + Profit (%) <span class="text-rose-500">*</span>
                </label>
                <input type="number" step="0.01" name="tooling_oh_profit_pct" id="field-tooling_oh_profit_pct"
                    value="{{ old('tooling_oh_profit_pct', $item->tooling_oh_profit_pct ?? 20.00) }}" required placeholder="e.g. 0.00 (Eng) or 20.00 (Sales)"
                    class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-sm focus:ring-2 focus:ring-blue-500 font-semibold text-slate-800 dark:text-slate-200">
                <span class="text-[10px] text-slate-400 mt-0.5 block">Default Eng: 0%, Sales: 20%</span>
            </div>

            {{-- Tooling Min Std Margin --}}
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Tooling Target Std Margin (%) <span class="text-rose-500">*</span>
                </label>
                <input type="number" step="0.01" name="tooling_min_std_margin_pct" id="field-tooling_min_std_margin_pct"
                    value="{{ old('tooling_min_std_margin_pct', $item->tooling_min_std_margin_pct ?? 20.00) }}" required placeholder="e.g. 20.00"
                    class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-sm focus:ring-2 focus:ring-blue-500 font-bold text-slate-800 dark:text-slate-200">
                <span class="text-[10px] text-slate-400 mt-0.5 block">Standard Tooling: Min. 20%</span>
            </div>
        </div>
    </div>

    {{-- Notes --}}
    <div>
        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
            Notes / Policy Description
        </label>
        <input type="text" name="notes" id="field-notes"
            value="{{ old('notes', $item->notes ?? '') }}" placeholder="e.g. Agreed commercial terms"
            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm focus:ring-2 focus:ring-blue-500 font-medium text-slate-800 dark:text-slate-200">
    </div>
</div>
