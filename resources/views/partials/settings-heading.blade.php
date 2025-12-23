<div class="relative mb-6 w-full">
    @if(isset($title))
    <flux:heading size="xl" level="1">{{ $title }}</flux:heading>
    @endif
    <div class="my-2">
        <!-- <el-dropdown class="inline-block">
            <button class="inline-flex w-full justify-center gap-x-1.5 rounded-md bg-white/10 px-3 py-2 text-sm font-semibold text-white inset-ring-1 inset-ring-white/5 hover:bg-white/20">
                Options
                <svg viewBox="0 0 20 20" fill="currentColor" data-slot="icon" aria-hidden="true" class="-mr-1 size-5 text-gray-400">
                <path d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" fill-rule="evenodd" />
                </svg>
            </button>

            <el-menu anchor="bottom end" popover class="w-56 origin-top-right rounded-md bg-gray-800 outline-1 -outline-offset-1 outline-white/10 transition transition-discrete [--anchor-gap:--spacing(2)] data-closed:scale-95 data-closed:transform data-closed:opacity-0 data-enter:duration-100 data-enter:ease-out data-leave:duration-75 data-leave:ease-in">
                <div class="py-1">
                    <a href="#" class="block px-4 py-2 text-sm text-gray-300 focus:bg-white/5 focus:text-white focus:outline-hidden">Account settings</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-300 focus:bg-white/5 focus:text-white focus:outline-hidden">Support</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-300 focus:bg-white/5 focus:text-white focus:outline-hidden">License</a>
                </div>
            </el-menu>
        </el-dropdown> -->
        <div x-data="{ open: false }" @click.outside="open = false" class="inline-block">
            <!-- Кнопка -->
            <button @click="open = !open" class="inline-flex w-full rounded-md justify-center gap-x-1.5 px-3 py-2 text-sm font-semibold text-white">
                <span>{{ __('ui.options') }}</span>
                <svg :class="{'rotate-180': open}" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
    <!-- @if(isset($buttons))
        @foreach($buttons as $button) 
            <button class="btn {{ $button }} py-1 px-3 text-xs max-h-max bg-teal-500 text-white rounded-full cursor-pointer font-medium leading-5 text-center shadow-xs transition-all duration-500 hover:bg-teal-700">
                    {{ __('ui.'.$button) }}
            </button>                    
        @endforeach
    @endif -->
    </div>
    <!--<flux:subheading size="lg" class="mb-6">Здесь могла быть ваша реклама)</flux:subheading>-->
    <flux:separator variant="subtle" />
</div>
<!-- <script src="https://cdn.jsdelivr.net/npm/@tailwindplus/elements@1" type="module"></script> -->