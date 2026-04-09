@php
    $dropdownTitle = __('ui.notifications.dropdown_title');
    $dropdownEmpty = __('ui.notifications.dropdown_empty');
    $viewAllLabel = __('ui.notifications.view_all');
    $markAllLabel = __('ui.notifications.mark_all_read');
    $topBarTitle = __('ui.notifications.top_bar_title');
    $accountMenuLabel = __('ui.top_bar.account_menu');
    $settingsLabel = __('Settings');
    $messengerNotifLabel = __('ui.top_bar.messenger_notification_settings');
    $logoutLabel = __('ui.auth.logout.log_out');
@endphp
<div
    id="app-second-level-top-bar"
    class="sticky top-0 z-50 flex h-14 w-full min-w-0 shrink-0 items-center justify-end border-b border-zinc-200 bg-zinc-50 px-3 dark:border-zinc-700 dark:bg-zinc-900 lg:me-[63px] lg:w-auto"
    wire:poll.keep-alive.120s="refreshPreview"
    x-data="{ notificationsOpen: false, accountOpen: false }"
    @keydown.escape.window="notificationsOpen = false; accountOpen = false"
>
    <div class="flex w-full min-w-0 items-center justify-end gap-2">
        {{-- Уведомления (слева от меню аккаунта) --}}
        <div class="relative z-50" @click.outside="notificationsOpen = false">
            <flux:button
                size="sm"
                variant="subtle"
                class="relative justify-center px-3"
                icon="bell"
                type="button"
                title="{{ $topBarTitle }}"
                x-bind:aria-expanded="notificationsOpen ? 'true' : 'false'"
                aria-haspopup="menu"
                @click="notificationsOpen = ! notificationsOpen; accountOpen = false"
            >
                @if ($unreadCount > 0)
                    <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-blue-500 px-0.5 text-[10px] font-bold text-white">
                        {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                    </span>
                @endif
            </flux:button>

            <div
                x-show="notificationsOpen"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                x-cloak
                class="absolute end-0 top-full z-[100] mt-1 flex w-[min(calc(100vw-2rem),22rem)] max-h-[min(24rem,70vh)] flex-col overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-600 dark:bg-zinc-800"
                role="menu"
                @click.stop
            >
                <div class="flex items-center justify-between gap-2 border-b border-zinc-200 px-3 py-2 dark:border-zinc-700">
                    <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ $dropdownTitle }}</span>
                    @if ($unreadCount > 0)
                        <flux:button size="xs" variant="ghost" wire:click="markAllRead" class="shrink-0" type="button">
                            {{ $markAllLabel }}
                        </flux:button>
                    @endif
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto py-1 pe-1 ps-1">
                    @forelse ($previewItems as $item)
                        @php
                            $isUnread = empty($item['read_at']);
                            $href = $item['action_url'] ?? null;
                        @endphp
                        <div
                            wire:key="preview-notif-{{ $item['id'] }}"
                            class="rounded-lg px-2 py-1.5 {{ $isUnread ? 'bg-blue-50/80 dark:bg-blue-950/30' : '' }}"
                        >
                            @if (filled($href))
                                <button
                                    type="button"
                                    class="block w-full rounded-md px-1 py-0.5 text-start -mx-1 hover:bg-zinc-100/80 dark:hover:bg-zinc-700/80"
                                    wire:click="markOneReadAndGo('{{ $item['id'] }}', @js($href))"
                                    @click="notificationsOpen = false"
                                >
                                    <span class="block text-xs font-medium text-zinc-900 dark:text-zinc-100">{{ $item['title'] ?? '' }}</span>
                                    <span class="mt-0.5 block text-xs text-zinc-600 dark:text-zinc-400">{{ $item['body'] ?? '' }}</span>
                                </button>
                            @else
                                <div class="text-start">
                                    <span class="block text-xs font-medium text-zinc-900 dark:text-zinc-100">{{ $item['title'] ?? '' }}</span>
                                    <span class="mt-0.5 block text-xs text-zinc-600 dark:text-zinc-400">{{ $item['body'] ?? '' }}</span>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="px-3 py-6 text-center text-xs text-zinc-500 dark:text-zinc-400">
                            {{ $dropdownEmpty }}
                        </div>
                    @endforelse
                </div>
                <div class="border-t border-zinc-200 dark:border-zinc-700">
                    <a
                        href="{{ route('notifications.index') }}"
                        wire:navigate
                        class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-zinc-800 hover:bg-zinc-50 dark:text-zinc-100 dark:hover:bg-zinc-700/50"
                        @click="notificationsOpen = false"
                    >
                        <flux:icon.inbox class="size-5 shrink-0 text-zinc-400 dark:text-zinc-500" />
                        {{ $viewAllLabel }}
                    </a>
                </div>
            </div>
        </div>

        {{-- Аккаунт --}}
        <div class="relative z-50" @click.outside="accountOpen = false">
            <button
                type="button"
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-zinc-200 text-sm font-medium text-zinc-800 shadow-sm ring-1 ring-zinc-300/80 hover:bg-zinc-300/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:bg-zinc-700 dark:text-zinc-100 dark:ring-zinc-600 dark:hover:bg-zinc-600"
                title="{{ $accountMenuLabel }}"
                x-bind:aria-expanded="accountOpen ? 'true' : 'false'"
                aria-haspopup="menu"
                @click="accountOpen = ! accountOpen; notificationsOpen = false"
            >
                {{ auth()->user()->initials() }}
            </button>
            <div
                x-show="accountOpen"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                x-cloak
                class="absolute end-0 top-full z-[100] mt-1 min-w-[16rem] overflow-hidden rounded-lg border border-zinc-200 bg-white py-1 shadow-lg dark:border-zinc-600 dark:bg-zinc-800"
                role="menu"
                @click.stop
            >
                <div class="border-b border-zinc-200 px-3 py-2 dark:border-zinc-700">
                    <div class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ auth()->user()->name }}</div>
                    <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ auth()->user()->email }}</div>
                </div>
                <a
                    href="{{ route('settings.profile.edit') }}"
                    wire:navigate
                    class="block px-3 py-2 text-sm text-zinc-800 hover:bg-zinc-50 dark:text-zinc-100 dark:hover:bg-zinc-700/50"
                    @click="accountOpen = false"
                >
                    {{ $settingsLabel }}
                </a>
                <a
                    href="{{ route('messenger.settings.notifications') }}"
                    wire:navigate
                    class="block px-3 py-2 text-sm text-zinc-800 hover:bg-zinc-50 dark:text-zinc-100 dark:hover:bg-zinc-700/50"
                    @click="accountOpen = false"
                >
                    {{ $messengerNotifLabel }}
                </a>
                <div class="border-t border-zinc-200 dark:border-zinc-700">
                    <form method="POST" action="{{ route('logout') }}" class="block">
                        @csrf
                        <button
                            type="submit"
                            class="w-full px-3 py-2 text-start text-sm text-zinc-800 hover:bg-zinc-50 dark:text-zinc-100 dark:hover:bg-zinc-700/50"
                        >
                            {{ $logoutLabel }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @script
    <script>
        const uid = @json(auth()->id());
        if (window.Echo && uid) {
            window.Echo.private('user.' + uid).listen('.user.notification.created', (e) => {
                const n = e.notification;
                if (n) {
                    $wire.prependFromBroadcast(n);
                }
            });
            window.Echo.private('user.' + uid).listen('.user.notifications.synced', (e) => {
                $wire.applySyncFromBroadcast(
                    e.unread_count,
                    e.notification_id ?? null,
                    e.read_at ?? null,
                    !! e.refresh_preview
                );
            });
        }
    </script>
    @endscript
</div>
