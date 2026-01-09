<div class="z-1 flex flex-col gap-4 [:where(&)]:w-64 p-3 data-flux-sidebar-collapsed-desktop:w-14 data-flux-sidebar-collapsed-desktop:px-2 data-flux-sidebar-collapsed-desktop:cursor-e-resize rtl:data-flux-sidebar-collapsed-desktop:cursor-w-resize max-lg:data-flux-sidebar-cloak:hidden data-flux-sidebar-on-mobile:data-flux-sidebar-collapsed-mobile:-translate-x-full data-flux-sidebar-on-mobile:data-flux-sidebar-collapsed-mobile:rtl:translate-x-full z-20! data-flux-sidebar-on-mobile:start-0! data-flux-sidebar-on-mobile:fixed! data-flux-sidebar-on-mobile:top-0! data-flux-sidebar-on-mobile:min-h-dvh! data-flux-sidebar-on-mobile:max-h-dvh! max-h-dvh overflow-y-auto overscroll-contain border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 transition-transform">
    <div sticky class="left_sidebar h-full bg-zinc-50 dark:bg-zinc-900">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

        <a href="{{ route('home') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
            <x-app-logo />
        </a>

        <flux:navlist variant="outline">
            <flux:navlist.group :heading="__('Platform')" class="grid">
                <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>{{ __('ui.dashboard') }}</flux:navlist.item>
            </flux:navlist.group>
        </flux:navlist>

        <flux:navlist variant="outline">
            <flux:navlist.group class="grid">
                <flux:dropdown position="bottom" align="start" class="w-full">
                    <button
                        type="button"
                        @if(request()->routeIs('resources*')) data-current @endif
                        class="h-10 lg:h-8 relative flex items-center gap-3 rounded-lg py-0 text-start w-full px-3 my-px text-zinc-500 dark:text-white/80 data-[current]:text-[var(--color-accent-content)] hover:data-[current]:text-[var(--color-accent-content)] data-[current]:bg-white dark:data-[current]:bg-white/[7%] data-[current]:border data-[current]:border-zinc-200 dark:data-[current]:border-transparent hover:text-zinc-800 dark:hover:text-white dark:hover:bg-white/[7%] hover:bg-zinc-800/5 border border-transparent cursor-pointer"
                    >
                        <div class="relative">
                            <flux:icon icon="folder" variant="outline" class="size-4!" />
                        </div>
                        <div class="flex-1 text-sm font-medium leading-none whitespace-nowrap">{{ __('ui.sections.resources') }}</div>
                        <flux:icon icon="chevron-down" variant="outline" class="size-4!" />
                    </button>

                    <flux:menu class="w-[200px]">
                        <flux:menu.radio.group>
                            @foreach($resourceTypes as $type)
                                @php
                                    $isCurrent = request()->routeIs('resources.by_type') && request()->route('type_id') == $type->id;
                                @endphp
                                <flux:menu.item 
                                    :href="route('resources.by_type', ['type_id' => $type->id])" 
                                    icon="tag" 
                                    :current="$isCurrent" 
                                    wire:navigate
                                >
                                    {{ __('moonshine.types.values.' . $type->name) ?: $type->name }}
                                </flux:menu.item>
                            @endforeach
                        </flux:menu.radio.group>
                    </flux:menu>
                </flux:dropdown>
            </flux:navlist.group>
            <flux:navlist.group :heading="__('ui.sections.events')" class="grid">
                <flux:navlist.item icon="home" :href="route('events')" :current="request()->routeIs('events')" wire:navigate>{{ __('ui.leftside.events') }}</flux:navlist.item>
            </flux:navlist.group>
        </flux:navlist>

        <flux:spacer />

        <!-- Desktop User Menu -->
        <flux:dropdown class="left_sidebar_account_drop hidden lg:block" position="bottom" align="start">
            <flux:profile
                :name="auth()->user()->name"
                :initials="auth()->user()->initials()"
                icon:trailing="chevrons-up-down"
                data-test="sidebar-menu-button"
            />

            <flux:menu class="w-[220px]">
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                <span
                                    class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                >
                                    {{ auth()->user()->initials() }}
                                </span>
                            </span>

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('settings.profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full" data-test="logout-button">
                        {{ __('ui.auth.logout.log_out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </div>

    <!-- Mobile User Menu -->
    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <flux:dropdown position="top" align="end">
            <flux:profile
                :initials="auth()->user()->initials()"
                icon-trailing="chevron-down"
            />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                <span
                                    class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                >
                                    {{ auth()->user()->initials() }}
                                </span>
                            </span>

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('settings.profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full" data-test="logout-button">
                        {{ __('ui.auth.logout.log_out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>
</div>
