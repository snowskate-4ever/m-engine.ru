<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head', ['title' => $title ?? null])
    </head>
    @php
        $titleInTopBar = (bool) ($titleInTopBar ?? true);
        $renderTitleInTopBar = $titleInTopBar && auth()->check();
        $topBarButton = $topBarButton ?? null;
        $contentRightInset = (bool) ($contentRightInset ?? true);
    @endphp
    {{-- h-[100dvh] overflow-hidden: колонка меню и main скроллятся независимо (меню — список в aside, контент — main) --}}
    <body class="flex h-[100dvh] max-h-[100dvh] min-h-0 min-w-0 overflow-hidden bg-white dark:bg-zinc-800">
        @livewire('components.left-sidebar')
        {{-- flex-1 min-w-0: остаток ширины после сайдбара (w-full даёт переполнение и прячет правую колонку) --}}
        {{-- lg:pr-20 = p-4 справа (1rem) + место под рейку w-16 (4rem); рейка fixed и не смещается при скролле --}}
        <main
            id="app-second-level-main"
            class="relative z-0 flex min-h-0 min-w-0 flex-1 flex-col overflow-y-auto overscroll-contain [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden"
            x-data="messengerFloatPanel()"
            @toggle-messenger-float.window="toggle()"
            @messenger-float-open-chat.window="
                const targetId = Number($event.detail.id);
                if (open && Number(activeConversationId) === targetId) {
                    open = false;
                    return;
                }
                activeConversationId = targetId;
                open = true;
                $nextTick(() => {
                    resetPosition();
                    Livewire.dispatch('messenger-rail-select-chat', { conversationId: targetId });
                })
            "
            @messenger-float-open.window="
                open = true;
                $nextTick(() => resetPosition())
            "
            @keydown.escape.window="
                if (open) {
                    open = false;
                    activeConversationId = null;
                }
            "
        >
            @auth
                @livewire('components.app-top-bar', [
                    'title' => $renderTitleInTopBar ? ($title ?? null) : null,
                    'titleButton' => $renderTitleInTopBar ? $topBarButton : null,
                ])
            @endauth
            <div class="flex min-h-0 min-w-0 flex-1 flex-col ps-0 pe-4 pb-4 pt-4 {{ $contentRightInset ? 'lg:pr-20' : '' }}">
                @unless ($renderTitleInTopBar)
                    @include('partials.settings-heading', ['title' => $title])
                @endunless
                <div class="flex min-h-0 min-w-0 flex-1 flex-col [&>*]:mx-0">
                    {{ $slot }}
                </div>
            </div>
            @include('partials.messenger-rail-dock')
        </main>
        @stack('scripts')
        @fluxScripts
    </body>
</html>
