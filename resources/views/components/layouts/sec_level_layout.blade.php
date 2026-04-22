<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head', $data)
    </head>
    @php
        $pageTitle = $data['title'] ?? null;
        // Как на /profiles: по умолчанию без lg:pr-20 у обёртки контента
        $contentRightInset = (bool) ($data['content_right_inset'] ?? false);
        $topBarButton = $data['top_bar_button'] ?? null;
        if ($topBarButton === null && ! empty($data['buttons'] ?? []) && ! isset($data['buttons']['settings'])) {
            foreach ($data['buttons'] as $items) {
                if (! is_array($items)) {
                    continue;
                }
                foreach ($items as $key => $href) {
                    if (! is_string($href) || $href === '') {
                        continue;
                    }
                    $topBarButton = [
                        'href' => str_starts_with($href, '/') ? $href : url($href),
                        'label' => '+',
                        'title' => is_string($key) ? (__('ui.'.$key) ?: $key) : '',
                    ];
                    break 2;
                }
            }
        }
    @endphp
    <body class="flex h-[100dvh] max-h-[100dvh] min-h-0 min-w-0 overflow-hidden bg-white dark:bg-zinc-800">
        @livewire('components.left-sidebar')
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
                    'title' => $pageTitle,
                    'titleButton' => $topBarButton,
                ])
            @endauth
            <div class="flex min-h-0 min-w-0 flex-1 flex-col ps-0 pe-4 pb-6 pt-4 {{ $contentRightInset ? 'lg:pr-20' : '' }}">
                @if (request()->routeIs([
                    'settings.profile.edit',
                    'settings.password.edit',
                    'settings.appearance.edit',
                    'settings.two-factor.show',
                ]))
                    <div class="mx-auto flex w-full min-w-0 max-w-5xl flex-1 flex-col space-y-6">
                        @include('partials.settings-section-nav')
                        <div class="flex min-h-0 min-w-0 flex-1 flex-col [&>*]:mx-0">
                            @livewire($data['component'], isset($data['type_id']) ? ['type_id' => $data['type_id']] : [])
                        </div>
                    </div>
                @else
                    <div class="flex min-h-0 min-w-0 flex-1 flex-col [&>*]:mx-0">
                        @livewire($data['component'], isset($data['type_id']) ? ['type_id' => $data['type_id']] : [])
                    </div>
                @endif
            </div>
            @include('partials.messenger-rail-dock')
        </main>
        @stack('scripts')
        @fluxScripts
    </body>
</html>
