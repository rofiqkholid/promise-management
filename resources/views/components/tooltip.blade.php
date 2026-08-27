@props([
    'text' => '',
    'placement' => 'top', // top, bottom, left, right
    'theme' => null,      // primary, danger, or default
    'as' => 'div',
])

<{{ $as }}
    {{ $attributes->merge([
        'data-tooltip' => $text,
        'data-tooltip-placement' => $placement,
        'data-tooltip-theme' => $theme,
        'class' => 'inline-block',
    ]) }}
>
    {{ $slot }}
</{{ $as }}>
