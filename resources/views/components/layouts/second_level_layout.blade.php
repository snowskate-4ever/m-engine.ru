<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head', ['title' => $title ?? null])
    </head>
    {{-- h-[100dvh] overflow-hidden: колонка меню и main скроллятся независимо (меню — список в aside, контент — main) --}}
    <body class="flex h-[100dvh] max-h-[100dvh] min-h-0 min-w-0 overflow-hidden bg-white dark:bg-zinc-800">
        @livewire('components.left-sidebar')
        {{-- flex-1 min-w-0: остаток ширины после сайдбара (w-full даёт переполнение и прячет правую колонку) --}}
        {{-- lg:pr-20 = p-4 справа (1rem) + место под рейку w-16 (4rem); рейка fixed и не смещается при скролле --}}
        <main
            id="app-second-level-main"
            class="relative z-0 flex min-h-0 min-w-0 flex-1 flex-col overflow-y-auto overscroll-contain p-4 lg:pr-20"
            x-data="messengerFloatPanel()"
            @toggle-messenger-float.window="toggle()"
            @messenger-float-open-chat.window="
                open = true;
                $nextTick(() => {
                    resetPosition();
                    Livewire.dispatch('messenger-rail-select-chat', { conversationId: $event.detail.id });
                })
            "
            @keydown.escape.window="if (open) { open = false }"
        >
            @include('partials.settings-heading', ['title' => $title])
            <div class="flex min-h-0 min-w-0 flex-1 flex-col">
                {{ $slot }}
            </div>
            @include('partials.messenger-rail-dock')
        </main>
        @stack('scripts')
        @fluxScripts
    </body>
</html>
