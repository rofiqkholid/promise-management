@csrf
<div class="space-y-4 text-xs">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        {{-- Category --}}
        <div>
            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                Category <span class="text-rose-500">*</span>
            </label>
            <select name="category" id="field-category" required
                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xs focus:ring-2 focus:ring-blue-500 font-medium text-slate-800 dark:text-slate-200">
                <option value="Product" {{ (old('category', $item->category ?? '') == 'Product') ? 'selected' : '' }}>Product</option>
                <option value="Tooling" {{ (old('category', $item->category ?? '') == 'Tooling') ? 'selected' : '' }}>Tooling</option>
            </select>
        </div>

        {{-- Group Mfg Process --}}
        <div>
            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                Group Mfg Process <span class="text-rose-500">*</span>
            </label>
            <input type="text" name="process_group" id="field-process_group" list="list-groups"
                value="{{ old('process_group', $item->process_group ?? '') }}" required placeholder="e.g. Stamping, Non Stamping, Others"
                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xs focus:ring-2 focus:ring-blue-500 font-medium text-slate-800 dark:text-slate-200">
            <datalist id="list-groups">
                <option value="Stamping">
                <option value="Non Stamping">
                <option value="Others">
                <option value="Die Making">
                <option value="Maintenance">
            </datalist>
        </div>
    </div>

    {{-- Mfg Process Name --}}
    <div>
        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
            Mfg Process Name <span class="text-rose-500">*</span>
        </label>
        <input type="text" name="process_name" id="field-process_name"
            value="{{ old('process_name', $item->process_name ?? '') }}" required placeholder="e.g. RSW, PSW, Stamping Rate, ED Painting"
            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xs focus:ring-2 focus:ring-blue-500 font-medium text-slate-800 dark:text-slate-200">
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        {{-- Control Point --}}
        <div>
            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                Control Point
            </label>
            <input type="text" name="control_point" id="field-control_point" list="list-control-points"
                value="{{ old('control_point', $item->control_point ?? '') }}" placeholder="e.g. Qty Spot, Cycle time, Area, Length"
                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xs focus:ring-2 focus:ring-blue-500 font-medium text-slate-800 dark:text-slate-200">
            <datalist id="list-control-points">
                <option value="Tonnage - Stroke - Part Criteria">
                <option value="Qty Spot">
                <option value="Cycle time">
                <option value="Length">
                <option value="Area">
            </datalist>
        </div>

        {{-- UOM --}}
        <div>
            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                UOM (Unit of Measure)
            </label>
            <input type="text" name="uom" id="field-uom" list="list-uom"
                value="{{ old('uom', $item->uom ?? '') }}" placeholder="e.g. Stroke, Spot, second, mm, mm2"
                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xs focus:ring-2 focus:ring-blue-500 font-medium text-slate-800 dark:text-slate-200"
                oninput="autoFillRateUnit(this.value)">
            <datalist id="list-uom">
                <option value="Stroke">
                <option value="Spot">
                <option value="second">
                <option value="mm">
                <option value="mm2">
                <option value="Hour">
            </datalist>
        </div>
    </div>

    {{-- Rate Unit --}}
    <div>
        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
            Rate Unit (Idr / Units)
        </label>
        <input type="text" name="rate_unit" id="field-rate_unit"
            value="{{ old('rate_unit', $item->rate_unit ?? '') }}" placeholder="e.g. Idr / spot, Idr / stroke, Idr / second"
            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xs focus:ring-2 focus:ring-blue-500 font-medium text-slate-800 dark:text-slate-200">
    </div>

    {{-- SAI Cost Rate (Eng COGM) --}}
    <div class="p-3 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xs space-y-3">
        <span class="block font-extrabold text-[11px] uppercase tracking-wider text-slate-600 dark:text-slate-400">
            SAI Cost Rate (Eng COGM)
        </span>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            {{-- Min Cost Rate --}}
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Min Cost Rate <span class="text-slate-400 font-normal">(Optional)</span>
                </label>
                <div class="relative">
                    <input type="number" step="0.01" name="min_cost_rate" id="field-min_cost_rate"
                        value="{{ old('min_cost_rate', $item->min_cost_rate ?? '') }}" placeholder="0.00"
                        class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xs focus:ring-2 focus:ring-blue-500 font-semibold text-slate-800 dark:text-slate-200">
                </div>
            </div>

            {{-- Std Cost Rate --}}
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Std Cost Rate <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <input type="number" step="0.01" name="std_cost_rate" id="field-std_cost_rate" required
                        value="{{ old('std_cost_rate', $item->std_cost_rate ?? '') }}" placeholder="0.00"
                        class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xs focus:ring-2 focus:ring-blue-500 font-semibold text-slate-800 dark:text-slate-200">
                </div>
            </div>
        </div>
    </div>

    {{-- Rate Source --}}
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

<script>
    function autoFillRateUnit(uomValue) {
        const rateUnitInput = document.getElementById('field-rate_unit');
        if (rateUnitInput && uomValue.trim() !== '') {
            rateUnitInput.value = 'Idr / ' + uomValue.trim().toLowerCase();
        }
    }
</script>
