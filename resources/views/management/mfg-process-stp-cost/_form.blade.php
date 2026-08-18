@csrf
<div class="space-y-4 text-xs">
    {{-- Section 1: Machine Identity --}}
    <div class="p-3 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xs space-y-3">
        <span class="block font-extrabold text-[11px] uppercase tracking-wider text-slate-600 dark:text-slate-400 flex items-center gap-1.5">
            <i class="fa-solid fa-gears text-blue-500"></i> Machine Identity
        </span>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            {{-- Machine Type --}}
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Machine Type <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="machine_type" id="field-machine_type" list="list-machine-types"
                    value="{{ old('machine_type', $item->machine_type ?? 'Tandem') }}" required placeholder="e.g. Tandem, Transfer, Progressive"
                    class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xs focus:ring-2 focus:ring-blue-500 font-medium text-slate-800 dark:text-slate-200">
                <datalist id="list-machine-types">
                    <option value="Tandem">
                    <option value="Transfer">
                    <option value="Progressive">
                    <option value="Manual">
                </datalist>
            </div>

            {{-- Tonnage --}}
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Tonnage (Tons) <span class="text-rose-500">*</span>
                </label>
                <input type="number" name="tonnage" id="field-tonnage"
                    value="{{ old('tonnage', $item->tonnage ?? '') }}" required placeholder="e.g. 110, 200, 500"
                    class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xs focus:ring-2 focus:ring-blue-500 font-semibold text-slate-800 dark:text-slate-200">
            </div>

            {{-- Machine Category --}}
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Machine Category
                </label>
                <select name="machine_category" id="field-machine_category"
                    class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xs focus:ring-2 focus:ring-blue-500 font-medium text-slate-800 dark:text-slate-200">
                    <option value="">- Select Category -</option>
                    <option value="Small" {{ (old('machine_category', $item->machine_category ?? '') == 'Small') ? 'selected' : '' }}>Small</option>
                    <option value="Medium" {{ (old('machine_category', $item->machine_category ?? '') == 'Medium') ? 'selected' : '' }}>Medium</option>
                    <option value="Large" {{ (old('machine_category', $item->machine_category ?? '') == 'Large') ? 'selected' : '' }}>Large</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Section 2: Output & Setup Configuration --}}
    <div class="p-3 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xs space-y-3">
        <span class="block font-extrabold text-[11px] uppercase tracking-wider text-slate-600 dark:text-slate-400 flex items-center gap-1.5">
            <i class="fa-solid fa-sliders text-indigo-500"></i> Output & Setup Configuration
        </span>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            {{-- Output Type --}}
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Output Type <span class="text-rose-500">*</span>
                </label>
                <select name="output_type" id="field-output_type" required
                    class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xs focus:ring-2 focus:ring-blue-500 font-medium text-slate-800 dark:text-slate-200">
                    <option value="Part" {{ (old('output_type', $item->output_type ?? 'Part') == 'Part') ? 'selected' : '' }}>Part</option>
                    <option value="Cavity" {{ (old('output_type', $item->output_type ?? '') == 'Cavity') ? 'selected' : '' }}>Cavity</option>
                    <option value="Process" {{ (old('output_type', $item->output_type ?? '') == 'Process') ? 'selected' : '' }}>Process</option>
                </select>
            </div>

            {{-- Output Qty --}}
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Output Qty <span class="text-rose-500">*</span>
                </label>
                <input type="number" name="output_qty" id="field-output_qty"
                    value="{{ old('output_qty', $item->output_qty ?? 1) }}" required min="1"
                    class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xs focus:ring-2 focus:ring-blue-500 font-semibold text-slate-800 dark:text-slate-200">
            </div>

            {{-- Stroke --}}
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Stroke <span class="text-rose-500">*</span>
                </label>
                <input type="number" step="0.01" name="stroke" id="field-stroke"
                    value="{{ old('stroke', $item->stroke ?? 1.00) }}" required min="0.01"
                    class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xs focus:ring-2 focus:ring-blue-500 font-semibold text-slate-800 dark:text-slate-200">
            </div>
        </div>
    </div>

    {{-- Section 3: Process Complexity & Complexity Alias --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                Process Complexity <span class="text-rose-500">*</span>
            </label>
            <input type="text" name="process_complexity" id="field-process_complexity" list="list-complexities"
                value="{{ old('process_complexity', $item->process_complexity ?? 'Inner') }}" required placeholder="e.g. Inner, Deep Draw, Outer Panel"
                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xs focus:ring-2 focus:ring-blue-500 font-medium text-slate-800 dark:text-slate-200">
            <datalist id="list-complexities">
                <option value="Inner">
                <option value="Deep Draw">
                <option value="Outer Panel">
            </datalist>
        </div>

        <div>
            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                Complexity Alias (Part Rank) <span class="text-slate-400 font-normal">(Optional)</span>
            </label>
            <input type="text" name="complexity_alias" id="field-complexity_alias"
                value="{{ old('complexity_alias', $item->complexity_alias ?? '') }}" placeholder="e.g. A, B, C, D"
                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xs focus:ring-2 focus:ring-blue-500 font-medium text-slate-800 dark:text-slate-200">
            <span class="text-[10px] text-slate-400 mt-1 block">Pemetaan ke Part Rank EBD (A, B, C, D).</span>
        </div>
    </div>

    {{-- Section 4: Cost Rates --}}
    <div class="p-3 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xs space-y-3">
        <span class="block font-extrabold text-[11px] uppercase tracking-wider text-slate-600 dark:text-slate-400 flex items-center gap-1.5">
            <i class="fa-solid fa-coins text-emerald-500"></i> Stamping Cost Rates
        </span>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            {{-- Min Cost Rate --}}
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Min Cost Rate <span class="text-slate-400 font-normal">(Optional)</span>
                </label>
                <input type="number" step="0.01" name="min_cost_rate" id="field-min_cost_rate"
                    value="{{ old('min_cost_rate', $item->min_cost_rate ?? '') }}" placeholder="0.00"
                    class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xs focus:ring-2 focus:ring-blue-500 font-semibold text-slate-800 dark:text-slate-200">
            </div>

            {{-- Std Cost Rate --}}
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Std Cost Rate <span class="text-rose-500">*</span>
                </label>
                <input type="number" step="0.01" name="std_cost_rate" id="field-std_cost_rate" required
                    value="{{ old('std_cost_rate', $item->std_cost_rate ?? '') }}" placeholder="0.00"
                    class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xs focus:ring-2 focus:ring-blue-500 font-semibold text-slate-800 dark:text-slate-200">
            </div>
        </div>
    </div>

    {{-- Section 5: Rate Source --}}
    <div>
        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
            Rate Source <span class="text-rose-500">*</span>
        </label>
        <select name="rate_source" id="field-rate_source" required
            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xs focus:ring-2 focus:ring-blue-500 font-medium text-slate-800 dark:text-slate-200">
            <option value="Sales" {{ (old('rate_source', $item->rate_source ?? 'Sales') == 'Sales') ? 'selected' : '' }}>Sales</option>
            <option value="Engineering" {{ (old('rate_source', $item->rate_source ?? '') == 'Engineering') ? 'selected' : '' }}>Engineering</option>
        </select>
    </div>
</div>
