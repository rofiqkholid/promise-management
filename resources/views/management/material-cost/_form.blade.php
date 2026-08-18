@csrf
<div class="space-y-4 text-xs">
    {{-- Customer Context --}}
    <div>
        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
            Customer Context
        </label>
        <select name="customer_id" id="field-customer_id"
            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xs focus:ring-2 focus:ring-blue-500 font-medium text-slate-800 dark:text-slate-200">
            <option value="">Global (General Standard)</option>
            @foreach($customers as $c)
                <option value="{{ $c->id }}" {{ (old('customer_id', $item->customer_id ?? '') == $c->id) ? 'selected' : '' }}>
                    {{ $c->name }} ({{ $c->code ?? '-' }})
                </option>
            @endforeach
        </select>
        <span class="text-[10px] text-slate-400 mt-1 block">Leave empty if this rate applies globally to all customers.</span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        {{-- Material Spec --}}
        <div>
            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                Material Spec <span class="text-rose-500">*</span>
            </label>
            <input type="text" name="material_spec" id="field-material_spec" list="list-specs"
                value="{{ old('material_spec', $item->material_spec ?? '') }}" required placeholder="e.g. SPCC, SPHC, SECC, JAC270D, SUS304"
                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xs focus:ring-2 focus:ring-blue-500 font-semibold text-slate-800 dark:text-slate-200">
            <datalist id="list-specs">
                <option value="SPCC-SD">
                <option value="SPHC-PO">
                <option value="SECC-P">
                <option value="JAC270D">
                <option value="SUS304">
            </datalist>
        </div>

        {{-- Material Type --}}
        <div>
            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                Material Type <span class="text-rose-500">*</span>
            </label>
            <select name="material_type" id="field-material_type" required
                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xs focus:ring-2 focus:ring-blue-500 font-medium text-slate-800 dark:text-slate-200">
                <option value="Sheet" {{ (old('material_type', $item->material_type ?? 'Sheet') == 'Sheet') ? 'selected' : '' }}>Sheet</option>
                <option value="Coil" {{ (old('material_type', $item->material_type ?? '') == 'Coil') ? 'selected' : '' }}>Coil</option>
            </select>
        </div>
    </div>

    {{-- Thickness --}}
    <div>
        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
            Thickness (mm) <span class="text-slate-400 font-normal">(Optional)</span>
        </label>
        <input type="number" step="0.01" name="thickness" id="field-thickness"
            value="{{ old('thickness', $item->thickness ?? '') }}" placeholder="e.g. 1.20, 2.00 (Leave empty if applicable to all thicknesses)"
            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xs focus:ring-2 focus:ring-blue-500 font-medium text-slate-800 dark:text-slate-200">
    </div>

    {{-- Price & Rate per Kg (IDR) --}}
    <div class="p-3 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xs space-y-3">
        <span class="block font-extrabold text-[11px] uppercase tracking-wider text-slate-600 dark:text-slate-400 flex items-center gap-1.5">
            <i class="fa-solid fa-money-bill-wave text-emerald-500"></i> Material Price & Scrap Rate per Kg (IDR)
        </span>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            {{-- Price per Kg --}}
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Price per Kg (IDR) <span class="text-rose-500">*</span>
                </label>
                <input type="number" step="0.01" name="price_per_kg" id="field-price_per_kg"
                    value="{{ old('price_per_kg', $item->price_per_kg ?? '') }}" required placeholder="e.g. 14500"
                    class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xs focus:ring-2 focus:ring-blue-500 font-bold text-slate-800 dark:text-slate-200">
            </div>

            {{-- Scrap Price per Kg --}}
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Scrap Price per Kg (IDR)
                </label>
                <input type="number" step="0.01" name="scrap_price_per_kg" id="field-scrap_price_per_kg"
                    value="{{ old('scrap_price_per_kg', $item->scrap_price_per_kg ?? 0) }}" placeholder="e.g. 4200"
                    class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xs focus:ring-2 focus:ring-blue-500 font-semibold text-slate-800 dark:text-slate-200">
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
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

        {{-- Valid From --}}
        <div>
            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                Valid From
            </label>
            <input type="date" name="valid_from" id="field-valid_from"
                value="{{ old('valid_from', isset($item->valid_from) ? $item->valid_from->format('Y-m-d') : '') }}"
                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xs focus:ring-2 focus:ring-blue-500 font-medium text-slate-800 dark:text-slate-200">
        </div>
    </div>
</div>
