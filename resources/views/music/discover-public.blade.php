<x-layouts.public-minimal
    :title="__('ui.music.discover_title')"
    :meta-description="__('ui.music.discover_meta_description')"
    :canonical-url="url()->route('discover')"
>
    <header class="border-b border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mx-auto flex max-w-3xl items-center justify-between gap-4 px-4 py-3">
            <a
                href="{{ route('home') }}"
                class="text-sm font-semibold text-zinc-900 underline-offset-2 hover:underline dark:text-zinc-100"
            >
                {{ config('app.name') }}
            </a>
            <nav class="flex flex-wrap items-center justify-end gap-x-4 gap-y-2 text-sm font-medium">
                @auth
                    <a
                        href="{{ route('music.discover') }}"
                        class="text-zinc-600 underline-offset-2 hover:text-zinc-900 hover:underline dark:text-zinc-400 dark:hover:text-zinc-100"
                        wire:navigate
                    >
                        {{ __('ui.music.sidebar_discover') }}
                    </a>
                    <a
                        href="{{ route('dashboard') }}"
                        class="text-zinc-600 underline-offset-2 hover:text-zinc-900 hover:underline dark:text-zinc-400 dark:hover:text-zinc-100"
                        wire:navigate
                    >
                        {{ __('ui.dashboard') }}
                    </a>
                @else
                    <a
                        href="{{ route('login') }}"
                        class="text-zinc-600 underline-offset-2 hover:text-zinc-900 hover:underline dark:text-zinc-400 dark:hover:text-zinc-100"
                    >
                        {{ __('ui.auth.login.button') }}
                    </a>
                    @if (Route::has('register'))
                        <a
                            href="{{ route('register') }}"
                            class="text-zinc-600 underline-offset-2 hover:text-zinc-900 hover:underline dark:text-zinc-400 dark:hover:text-zinc-100"
                        >
                            {{ __('ui.auth.register.button') }}
                        </a>
                    @endif
                @endauth
            </nav>
        </div>
    </header>
    <div class="mx-auto max-w-3xl px-4 py-8">
        <livewire:music.music-directory-page :spa-navigate="false" />
    </div>
</x-layouts.public-minimal>
