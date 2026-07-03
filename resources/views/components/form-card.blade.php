@props([
    'title',
    'icon' => null
])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 p-5 rounded-none space-y-4']) }}>
    @if(isset($title) || isset($headerActions))
        <h3 class="text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-widest pb-2 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
            <span class="flex items-center gap-2">
                @if($icon)
                    <i class="fa-solid {{ $icon }} text-blue-500"></i>
                @endif
                {{ $title }}
            </span>
            @if(isset($headerActions))
                {{ $headerActions }}
            @endif
        </h3>
    @endif
    
    <div>
        {{ $slot }}
    </div>
</div>
