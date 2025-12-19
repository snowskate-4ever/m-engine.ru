<div class="relative mb-6 w-full">
    <flux:heading size="xl" level="1">{{ $title }}</flux:heading>
    <div class="my-2">
    @if(isset($buttons))
        @foreach($buttons as $button) 
            <button class="btn {{ $button }} py-1 px-3 text-xs max-h-max bg-teal-500 text-white rounded-full cursor-pointer font-medium leading-5 text-center shadow-xs transition-all duration-500 hover:bg-teal-700">
                    {{ __('ui.'.$button) }}
            </button>                    
        @endforeach
    @endif
    </div>
    <!--<flux:subheading size="lg" class="mb-6">Здесь могла быть ваша реклама)</flux:subheading>-->
    <flux:separator variant="subtle" />
</div>
