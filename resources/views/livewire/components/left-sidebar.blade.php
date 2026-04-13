<div class="sidebar-wrapper shrink-0 h-full min-h-0 w-[264px] min-w-[264px] max-lg:w-0 max-lg:min-w-0 overflow-visible" x-data="{ minimizedMenu: $persist(false), sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
    {{-- Mobile overlay --}}
    <div class="sidebar-overlay" :class="{ '_is-visible': sidebarOpen }" @click="sidebarOpen = false" x-show="sidebarOpen" x-transition.opacity></div>

    {{-- Mobile open button --}}
    <button type="button" class="sidebar-mobile-toggle" @click="sidebarOpen = true" aria-label="{{ __('ui.menu.open') }}">
        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>

    {{-- Sidebar + collapse button (collapse вне aside, чтобы не обрезался overflow) --}}
    <div class="layout-menu-outer">
        <aside class="layout-menu relative h-full min-h-0 overflow-hidden" :class="{ '_is-minimized': minimizedMenu, '_is-opened': sidebarOpen }">
            {{-- Mobile close --}}
            <button type="button" class="lg:hidden absolute top-3 right-3 p-1.5 rounded-md text-zinc-500 hover:bg-zinc-200 dark:hover:bg-zinc-700" @click="sidebarOpen = false" aria-label="{{ __('ui.menu.close') }}">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            {{-- Logo --}}
        <a href="{{ route('home') }}" class="menu-brand flex items-center gap-2 py-2 pr-8 lg:pr-0 rtl:space-x-reverse" wire:navigate>
            <x-app-logo />
        </a>

        {{-- Navigation --}}
        <nav class="menu flex flex-1 min-h-0 flex-col overflow-hidden">
            <ul class="menu-list flex-1 overflow-y-auto [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden">
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

                <li class="menu-divider"><span>{{ __('ui.sections.music') }}</span></li>

                <li class="menu-item {{ request()->routeIs('music.discover') ? 'is_active' : '' }}">
                    <a href="{{ route('music.discover') }}" class="menu-link" wire:navigate>
                        <span class="menu-icon"><svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg></span>
                        <span class="menu-text">{{ __('ui.music.sidebar_discover') }}</span>
                    </a>
                </li>

                <li class="menu-item {{ request()->routeIs('music.profiles') ? 'is_active' : '' }}">
                    <a href="{{ route('music.profiles') }}" class="menu-link" wire:navigate>
                        <span class="menu-icon"><svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></span>
                        <span class="menu-text">{{ __('ui.music.sidebar_profiles') }}</span>
                    </a>
                </li>

                <li class="menu-item {{ request()->routeIs('music.search-requests.*') ? 'is_active' : '' }}">
                    <a href="{{ route('music.search-requests.index') }}" class="menu-link" wire:navigate>
                        <span class="menu-icon"><svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>
                        <span class="menu-text">{{ __('ui.music.sidebar_search_requests') }}</span>
                    </a>
                </li>

                <li class="menu-item {{ request()->routeIs('music.performers.*') ? 'is_active' : '' }}">
                    <a href="{{ route('music.performers.index') }}" class="menu-link" wire:navigate>
                        <span class="menu-icon"><svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg></span>
                        <span class="menu-text">{{ __('ui.music.sidebar_performers') }}</span>
                    </a>
                </li>

                <li class="menu-item {{ request()->routeIs('music.studios.*') ? 'is_active' : '' }}">
                    <a href="{{ route('music.studios.index') }}" class="menu-link" wire:navigate>
                        <span class="menu-icon"><svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg></span>
                        <span class="menu-text">{{ __('ui.music.sidebar_studios') }}</span>
                    </a>
                </li>

                <li class="menu-item {{ request()->routeIs('music.rehearsals.*') ? 'is_active' : '' }}">
                    <a href="{{ route('music.rehearsals.index') }}" class="menu-link" wire:navigate>
                        <span class="menu-icon"><svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z"/></svg></span>
                        <span class="menu-text">{{ __('ui.music.sidebar_rehearsals') }}</span>
                    </a>
                </li>

                <li class="menu-item {{ request()->routeIs('music.concert-venues.*') ? 'is_active' : '' }}">
                    <a href="{{ route('music.concert-venues.index') }}" class="menu-link" wire:navigate>
                        <span class="menu-icon"><svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg></span>
                        <span class="menu-text">{{ __('ui.music.sidebar_concert_venues') }}</span>
                    </a>
                </li>

                <li class="menu-item {{ request()->routeIs('music.schools.*') ? 'is_active' : '' }}">
                    <a href="{{ route('music.schools.index') }}" class="menu-link" wire:navigate>
                        <span class="menu-icon"><svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg></span>
                        <span class="menu-text">{{ __('ui.music.sidebar_schools') }}</span>
                    </a>
                </li>

                <li class="menu-item {{ request()->routeIs('music.labels.*') ? 'is_active' : '' }}">
                    <a href="{{ route('music.labels.index') }}" class="menu-link" wire:navigate>
                        <span class="menu-icon"><svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg></span>
                        <span class="menu-text">{{ __('ui.music.sidebar_labels') }}</span>
                    </a>
                </li>

                <li class="menu-item {{ request()->routeIs('music.producer-centers.*') ? 'is_active' : '' }}">
                    <a href="{{ route('music.producer-centers.index') }}" class="menu-link" wire:navigate>
                        <span class="menu-icon"><svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.582.217m0 0h-6.24m6.24 0v-.832c0-.287-.055-.57-.163-.841m.163.841l-.41 1.024a3 3 0 01-2.296 1.825m0 0l-1.022.328a2 2 0 01-1.847-1.086l-.919-1.538a2 2 0 01.308-2.273m2.45.455l2.32-1.64M12 6.75V4.5m0 2.25a1.5 1.5 0 110-3 1.5 1.5 0 010 3z"/></svg></span>
                        <span class="menu-text">{{ __('ui.music.sidebar_producer_centers') }}</span>
                    </a>
                </li>

                <li class="menu-item {{ request()->routeIs('music.shops.*') ? 'is_active' : '' }}">
                    <a href="{{ route('music.shops.index') }}" class="menu-link" wire:navigate>
                        <span class="menu-icon"><svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg></span>
                        <span class="menu-text">{{ __('ui.music.sidebar_shops') }}</span>
                    </a>
                </li>

                <li class="menu-item {{ request()->routeIs('music.shop.cart') ? 'is_active' : '' }}">
                    <a href="{{ route('music.shop.cart') }}" class="menu-link" wire:navigate>
                        <span class="menu-icon"><svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg></span>
                        <span class="menu-text">{{ __('ui.music.sidebar_cart') }}</span>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('music.shop.orders') ? 'is_active' : '' }}">
                    <a href="{{ route('music.shop.orders') }}" class="menu-link" wire:navigate>
                        <span class="menu-icon"><svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg></span>
                        <span class="menu-text">{{ __('ui.music.sidebar_my_orders') }}</span>
                    </a>
                </li>

                <li class="menu-divider"><span>{{ __('ui.sections.planning') }}</span></li>

                <li class="menu-item {{ request()->routeIs('calendar') ? 'is_active' : '' }}">
                    <a href="{{ route('calendar') }}" class="menu-link" wire:navigate>
                        <span class="menu-icon"><svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></span>
                        <span class="menu-text">{{ __('ui.calendar.title') }}</span>
                    </a>
                </li>

                <li class="menu-item {{ request()->routeIs('kanban') && ! request()->routeIs('kanban.logs') ? 'is_active' : '' }}">
                    <a href="{{ route('kanban') }}" class="menu-link" wire:navigate>
                        <span class="menu-icon"><svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg></span>
                        <span class="menu-text">{{ __('ui.kanban.title') }}</span>
                    </a>
                </li>

                <li class="menu-item {{ request()->routeIs('kanban.logs') ? 'is_active' : '' }}">
                    <a href="{{ route('kanban.logs') }}" class="menu-link" wire:navigate>
                        <span class="menu-icon"><svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
                        <span class="menu-text">{{ __('ui.kanban.logs') }}</span>
                    </a>
                </li>

                <li class="menu-divider"><span>{{ __('ui.sections.notifications') }}</span></li>

                <li class="menu-item {{ request()->routeIs('notifications.*') ? 'is_active' : '' }}">
                    <a href="{{ route('notifications.index') }}" class="menu-link" wire:navigate>
                        <span class="menu-icon"><svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg></span>
                        <span class="menu-text">{{ __('ui.notifications.sidebar_nav') }}</span>
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
