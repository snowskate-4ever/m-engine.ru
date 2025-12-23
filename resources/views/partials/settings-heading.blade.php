<div class="relative mb-6 w-full">
    @if(isset($title))
    <flux:heading size="xl" level="1">{{ $title }}</flux:heading>
    @endif
    <div class="my-2">
        <div x-data="{ open: false }" @click.outside="open = false" class="inline-block">
            <!-- Кнопка -->
            <button @click="open = !open" class="inline-flex w-full rounded-md justify-center gap-x-1.5 px-3 py-2 text-sm font-semibold text-white">
                <span>{{ __('ui.options') }}</span>
                <svg :class="{'rotate-180': open}" class="w-4 h-4 mt-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            
            <!-- Меню -->
            <div x-show="open" 
                x-transition:enter="transition ease-out duration-100"
                class="absolute w-56 origin-top-right rounded-md shadow-xs border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-700 focus:outline-hidden outline-1 -outline-offset-1 outline-white/10 transition transition-discrete [--anchor-gap:--spacing(2)] data-closed:scale-95 data-closed:transform data-closed:opacity-0 data-enter:duration-100 data-enter:ease-out data-leave:duration-75 data-leave:ease-in">
            @if(isset($buttons))
                @foreach($buttons as $button) 
                <a href="#" class="block px-4 py-2 text-sm text-gray-300 focus:bg-white/5 focus:text-white focus:outline-hidden">{{ __('ui.'.$button) }}</a>
                 @endforeach
            @endif
            </div>
        </div>
        @if(request()->routeIs('settings.*'))
        <div x-data="{ open: false }" @click.outside="open = false" class="inline-block">
            <!-- Кнопка -->
            <button @click="open = !open" class="inline-flex w-full rounded-md justify-center gap-x-1.5 px-3 py-2 text-sm font-semibold text-white">
                <span>{{ __('ui.settings') }}</span>
                <svg :class="{'rotate-180': open}" class="w-4 h-4 mt-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            
            <!-- Меню -->
            <div x-show="open" 
                x-transition:enter="transition ease-out duration-100"
                class="absolute z-2 w-56 origin-top-right rounded-md shadow-xs border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-700 focus:outline-hidden outline-1 -outline-offset-1 outline-white/10 transition transition-discrete [--anchor-gap:--spacing(2)] data-closed:scale-95 data-closed:transform data-closed:opacity-0 data-enter:duration-100 data-enter:ease-out data-leave:duration-75 data-leave:ease-in">
                <a :href="route('settings.profile.edit')" class="block px-4 py-2 text-sm text-gray-300 focus:bg-white/5 focus:text-white focus:outline-hidden">{{ __('ui.profile') }}</a>
                <a :href="route('settings.password.edit')" class="block px-4 py-2 text-sm text-gray-300 focus:bg-white/5 focus:text-white focus:outline-hidden">{{ __('ui.password') }}</a>
                <a :href="route('settings.two-factor.show')" class="block px-4 py-2 text-sm text-gray-300 focus:bg-white/5 focus:text-white focus:outline-hidden">{{ __('ui.two-factor-auth') }}</a>
                <a :href="route('settings.appearance.edit')" class="block px-4 py-2 text-sm text-gray-300 focus:bg-white/5 focus:text-white focus:outline-hidden">{{ __('ui.appearance') }}</a>
            </div>
        </div>
        @endif
    </div>
    <flux:separator variant="subtle" />
</div>