@props([
    'label',
    'name',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'disabled' => false,
    'placeholder' => null
])

<div>
    <label class="block text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5" for="{{ $name }}">
        {{ $label }} {!! $required ? '<span class="text-rose-500">*</span>' : '' !!}
        @if($disabled)
            <i class="fa-solid fa-lock text-[9px] text-slate-400 ml-1" title="System Locked"></i>
        @endif
    </label>
    <input type="{{ $type }}" 
           name="{{ $name }}" 
           id="{{ $name }}"
           value="{{ old($name, $value) }}"
           {{ $required ? 'required' : '' }}
           {{ $disabled ? 'disabled' : '' }}
           @if($placeholder) placeholder="{{ $placeholder }}" @endif
           {{ $attributes->merge([
               'class' => 'w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm px-3 py-2 text-xs text-slate-900 dark:text-slate-100 focus:bg-white focus:border-blue-500 focus:outline-none disabled:bg-slate-200 dark:disabled:bg-slate-800 disabled:text-slate-500 disabled:cursor-not-allowed transition-colors duration-150'
           ]) }}>
    @error($name)
        <span class="text-rose-500 text-[10px] mt-1 block font-medium">{{ $message }}</span>
    @enderror
</div>
