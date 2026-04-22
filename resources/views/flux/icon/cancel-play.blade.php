{{-- Custom cancel icon (play-like triangle), registered as Flux icon. --}}
@props([
    'variant' => 'outline',
])

@php
    $classes = Flux::classes('shrink-0')->add(
        match ($variant) {
            'outline' => '[:where(&)]:size-8',
            'mini' => '[:where(&)]:size-7',
            'micro' => '[:where(&)]:size-6',
            default => '[:where(&)]:size-8',
        },
    );
@endphp

<svg
    {{ $attributes->class($classes) }}
    data-flux-icon
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2.4"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
>
    <circle cx="12" cy="12" r="8.5" />
    <path d="M9 9l6 6" />
    <path d="M15 9l-6 6" />
</svg>
