@props([
    'id' => 'modal-' . uniqid(),
    'maxWidth' => 'max-w-lg',
    'title' => null,
    'subtitle' => null,
    'showClose' => true,
    'closeButtonId' => null,
])

@php
    $maxWidthClass = match ($maxWidth) {
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        '3xl' => 'max-w-3xl',
        '4xl' => 'max-w-4xl',
        '5xl' => 'max-w-5xl',
        'full' => 'max-w-full mx-4',
        default => $maxWidth,
    };
@endphp

<div id="{{ $id }}"
     {{ $attributes->merge(['class' => 'fixed inset-0 z-50 hidden items-center justify-center bg-black/50']) }}>
    
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-sm shadow-2xl w-full {{ $maxWidthClass }} mx-4 my-6 flex flex-col max-h-[90vh] overflow-hidden animate-fade-in">
        
        {{-- ===== 1. MODAL HEADER ===== --}}
        @if (isset($header))
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-700 flex-shrink-0 bg-white dark:bg-slate-800">
                {{ $header }}
            </div>
        @elseif ($title)
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-700 flex-shrink-0 bg-white dark:bg-slate-800">
                <div>
                    <h2 class="text-sm font-bold text-slate-800 dark:text-white">{{ $title }}</h2>
                    @if ($subtitle)
                        <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">{{ $subtitle }}</p>
                    @endif
                </div>
                @if ($showClose)
                    <button type="button"
                            @if($closeButtonId) id="{{ $closeButtonId }}" @endif
                            class="btn-close-modal text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors cursor-pointer w-6 h-6 flex items-center justify-center rounded-sm">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                @endif
            </div>
        @endif

        {{-- ===== 2. MODAL CONTENT / BODY (SCROLLABLE) ===== --}}
        <div class="flex-1 overflow-y-auto overscroll-contain p-5 space-y-4">
            {{ $slot }}
        </div>

        {{-- ===== 3. MODAL FOOTER ===== --}}
        @if (isset($footer))
            <div class="px-5 py-3.5 bg-slate-50 dark:bg-slate-900/30 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-2 flex-shrink-0 rounded-b-xs">
                {{ $footer }}
            </div>
        @endif

    </div>
</div>
