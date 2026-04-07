<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head', ['title' => $title ?? null])
    </head>
    <body class="flex min-h-screen min-w-0 bg-white dark:bg-zinc-800">
        @livewire('components.left-sidebar')
        {{-- flex-1 min-w-0: остаток ширины после сайдбара (w-full даёт переполнение и прячет правую колонку) --}}
        {{-- lg:pr-20 = p-4 справа (1rem) + место под рейку w-16 (4rem); рейка fixed и не смещается при скролле --}}
        <main
            id="app-second-level-main"
            class="relative z-0 flex min-h-screen min-w-0 flex-1 flex-col p-4 lg:pr-20"
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
        @fluxScripts
    </body>
</html>
