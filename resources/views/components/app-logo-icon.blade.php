@php
    use App\Helpers\LogoHelper;
@endphp
<img 
    src="{{ LogoHelper::getPath() }}" 
    alt="{{ LogoHelper::getAlt() }}"
    class="{{ LogoHelper::getClass('icon') }}"
    {{ $attributes }}
/>
{!! LogoHelper::generateStyles('icon') !!}
