<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head', $data)
    </head>
    <body class="flex min-h-screen min-w-0 bg-white dark:bg-zinc-800">
        @livewire('components.left-sidebar')
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
            @include('partials.settings-heading', $data)
            <div class="flex min-h-0 min-w-0 flex-1 flex-col">
                @livewire($data['component'], isset($data['type_id']) ? ['type_id' => $data['type_id']] : [])
            </div>
            @include('partials.messenger-rail-dock')
        </main>
        @fluxScripts
    </body>
</html>
