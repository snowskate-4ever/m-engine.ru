@auth
    <aside
        id="app-messenger-right-rail"
        class="fixed top-0 right-0 z-20 hidden h-[100dvh] max-h-[100dvh] w-16 min-h-0 min-w-16 max-w-16 flex flex-col overflow-hidden border-s border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 lg:flex"
    >
        @livewire('messenger.messenger-right-rail')
    </aside>

    @unless (request()->routeIs(['messenger.index', 'messenger.show']))
        <div
            x-cloak
            x-show="open"
            class="fixed z-30 flex flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-2xl dark:border-zinc-600 dark:bg-zinc-900"
            :style="`left:${left}px;top:${top}px;width:${w}px;height:${h}px`"
        >
            <div
                class="absolute -top-1 right-3 left-3 z-40 h-3 cursor-n-resize"
                @mousedown="startResize('n', $event)"
            ></div>
            <div
                class="absolute -bottom-1 right-3 left-3 z-40 h-3 cursor-s-resize"
                @mousedown="startResize('s', $event)"
            ></div>
            <div
                class="absolute top-3 -left-1 bottom-3 z-40 w-3 cursor-w-resize"
                @mousedown="startResize('w', $event)"
            ></div>
            <div
                class="absolute top-3 -right-1 bottom-3 z-40 w-3 cursor-e-resize"
                @mousedown="startResize('e', $event)"
            ></div>
            <div
                class="absolute -top-1 -left-1 z-40 h-4 w-4 cursor-nwse-resize"
                @mousedown="startResize('nw', $event)"
            ></div>
            <div
                class="absolute -top-1 -right-1 z-40 h-4 w-4 cursor-nesw-resize"
                @mousedown="startResize('ne', $event)"
            ></div>
            <div
                class="absolute -bottom-1 -left-1 z-40 h-4 w-4 cursor-nesw-resize"
                @mousedown="startResize('sw', $event)"
            ></div>
            <div
                class="absolute -bottom-1 -right-1 z-40 h-4 w-4 cursor-nwse-resize"
                @mousedown="startResize('se', $event)"
            ></div>

            <div
                class="flex shrink-0 cursor-move items-center justify-between gap-2 border-b border-zinc-200 bg-zinc-50 px-2 py-1.5 select-none dark:border-zinc-700 dark:bg-zinc-800/80"
                @mousedown="startDrag($event)"
            >
                <span class="truncate text-sm font-semibold text-zinc-800 dark:text-zinc-100">
                    {{ __('ui.messenger.title') }}
                </span>
                <div class="flex shrink-0 items-center gap-1" data-no-drag>
                    <flux:link href="{{ route('messenger.index') }}" wire:navigate class="text-xs">
                        {{ __('ui.messenger.open_full') }}
                    </flux:link>
                    <flux:button
                        type="button"
                        size="xs"
                        variant="ghost"
                        class="h-7 w-7 shrink-0 p-0"
                        title="{{ __('ui.close') }}"
                        @click="open = false"
                    >
                        <flux:icon.x-mark class="size-4" />
                    </flux:button>
                </div>
            </div>
            <div class="min-h-0 flex-1 overflow-hidden">
                @livewire('messenger.messenger-workspace', ['embedMode' => true], 'messenger-float-workspace')
            </div>
        </div>
    @endunless
@endauth
