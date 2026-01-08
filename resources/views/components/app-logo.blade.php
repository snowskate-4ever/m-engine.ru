@php
    use App\Helpers\LogoHelper;
    $size = LogoHelper::getSize('sidebar') ?? 'size-8';
@endphp
<div class="flex items-center justify-center">
    <img 
        src="{{ LogoHelper::getPath() }}" 
        alt="{{ LogoHelper::getAlt() }}"
        class="{{ LogoHelper::getClass('sidebar') }} {{ $size }}"
    />
</div>
<div class="ms-1 grid flex-1 text-start text-sm">
    <span class="mb-0.5 truncate leading-tight font-semibold">M-Engine</span>
</div>
{!! LogoHelper::generateStyles('sidebar') !!}
