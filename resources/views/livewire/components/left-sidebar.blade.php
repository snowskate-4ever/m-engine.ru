<div class="sidebar-wrapper shrink-0 w-[264px] min-w-[264px] max-lg:w-0 max-lg:min-w-0 overflow-visible" x-data="{ minimizedMenu: $persist(false), sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
    {{-- Mobile overlay --}}
    <div class="sidebar-overlay" :class="{ '_is-visible': sidebarOpen }" @click="sidebarOpen = false" x-show="sidebarOpen" x-transition.opacity></div>

    {{-- Mobile open button --}}
    <button type="button" class="sidebar-mobile-toggle" @click="sidebarOpen = true" aria-label="{{ __('ui.menu.open') }}">
        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>

    {{-- Sidebar + collapse button (collapse вне aside, чтобы не обрезался overflow) --}}
    <div class="layout-menu-outer">
        <aside class="layout-menu relative" :class="{ '_is-minimized': minimizedMenu, '_is-opened': sidebarOpen }">
            {{-- Mobile close --}}
            <button type="button" class="lg:hidden absolute top-3 right-3 p-1.5 rounded-md text-zinc-500 hover:bg-zinc-200 dark:hover:bg-zinc-700" @click="sidebarOpen = false" aria-label="{{ __('ui.menu.close') }}">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            {{-- Logo --}}
        <a href="{{ route('home') }}" class="menu-brand flex items-center gap-2 py-2 pr-8 lg:pr-0 rtl:space-x-reverse" wire:navigate>
            <x-app-logo />
        </a>

        {{-- Navigation --}}
        <nav class="menu flex-1 flex flex-col min-h-0">
            <ul class="menu-list flex-1 overflow-y-auto">
                <li class="menu-item {{ request()->routeIs('dashboard') ? 'is_active' : '' }}">
                    <a href="{{ route('dashboard') }}" class="menu-link" wire:navigate>
                        <span class="menu-icon"><svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m3 12 2.45-2.45M3 12h18M12 3v18M21 12l-2.45 2.45M21 12H3M12 21V3"/></svg></span>
                        <span class="menu-text">{{ __('ui.dashboard') }}</span>
                    </a>
                </li>

                <li class="menu-divider"><span>{{ __('ui.sections.resources') }}</span></li>

                <li class="menu-item" x-data="{ open: {{ request()->routeIs('resources*') ? 'true' : 'false' }} }">
                    <button type="button" class="menu-button w-full" @click="open = ! open" :class="{ 'menu-item--opened': open }">
                        <span class="menu-icon"><svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg></span>
                        <span class="menu-text">{{ __('ui.sections.resources') }}</span>
                        <span class="menu-arrow"><svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/></svg></span>
                    </button>
                    <ul class="menu-submenu" x-show="open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                        @foreach($resourceTypes as $type)
                            @php $isCurrent = request()->routeIs('resources.by_type') && request()->route('type_id') == $type->id; @endphp
                            <li class="menu-item {{ $isCurrent ? 'is_active' : '' }}">
                                <a href="{{ route('resources.by_type', ['type_id' => $type->id]) }}" class="menu-link" wire:navigate>
                                    <span class="menu-icon"><svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg></span>
                                    <span class="menu-text">{{ __('moonshine.types.values.' . $type->name) ?: $type->name }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </li>

                <li class="menu-divider"><span>{{ __('ui.sections.events') }}</span></li>

                <li class="menu-item {{ request()->routeIs('events') ? 'is_active' : '' }}">
                    <a href="{{ route('events') }}" class="menu-link" wire:navigate>
                        <span class="menu-icon"><svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></span>
                        <span class="menu-text">{{ __('ui.leftside.events') }}</span>
                    </a>
                </li>

                <li class="menu-divider"><span>{{ __('ui.sections.messenger') }}</span></li>

                <li class="menu-item {{ request()->routeIs('messenger.*') ? 'is_active' : '' }}">
                    <a href="{{ route('messenger.index') }}" class="menu-link" wire:navigate>
                        <span class="menu-icon"><svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg></span>
                        <span class="menu-text">{{ __('ui.messenger.title') }}</span>
                    </a>
                </li>
            </ul>

            {{-- User block (footer) --}}
            <div class="menu-footer">
                <div class="flex items-center gap-3 p-2 rounded-lg bg-zinc-100 dark:bg-zinc-800/80 mb-2">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-200 text-sm font-medium">
                        {{ auth()->user()->initials() }}
                    </span>
                    <div class="min-w-0 flex-1 hidden lg:block" :class="{ 'lg:hidden': minimizedMenu }">
                        <div class="truncate text-sm font-medium text-zinc-900 dark:text-white">{{ auth()->user()->name }}</div>
                        <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ auth()->user()->email }}</div>
                    </div>
                </div>
                <ul class="menu-list">
                    <li class="menu-item">
                        <a href="{{ route('settings.profile.edit') }}" class="menu-link" wire:navigate>
                            <span class="menu-icon"><svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg></span>
                            <span class="menu-text">{{ __('Settings') }}</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <form method="POST" action="{{ route('logout') }}" class="contents">
                            @csrf
                            <button type="submit" class="menu-link w-full">
                                <span class="menu-icon"><svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg></span>
                                <span class="menu-text">{{ __('ui.auth.logout.log_out') }}</span>
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </aside>
        {{-- Collapse (desktop): вне aside, чтобы не обрезался overflow-x и был поверх контента --}}
        <div class="layout-collapse hidden lg:block">
            <button type="button" class="layout-collapse-btn" @click="minimizedMenu = ! minimizedMenu" title="{{ __('ui.collapse_menu') }}">
                <svg class="size-4" x-show="!minimizedMenu" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/></svg>
                <svg class="size-4" x-show="minimizedMenu" x-cloak fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/></svg>
            </button>
        </div>
    </div>
    </div>
