{{-- Tabler Icons — device-floppy (MIT). Registered as Flux icon for save buttons. --}}
@props([
    'variant' => 'outline',
])

@php
    $classes = Flux::classes('shrink-0')->add(
        match ($variant) {
            'outline' => '[:where(&)]:size-7',
            'mini' => '[:where(&)]:size-6',
            'micro' => '[:where(&)]:size-5',
            default => '[:where(&)]:size-7',
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
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
>
    <path d="M6 4h10l4 4v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2" />
    <path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0-4 0" />
    <path d="M14 4v4H8V4" />
</svg>
