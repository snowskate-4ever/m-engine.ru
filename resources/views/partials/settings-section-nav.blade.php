{{-- Переключение разделов: та же ширина и карточный стиль, что и блок контента на search-requests --}}
@php
    /** @var callable(string): bool $isActive */
    $isActive = static fn (string $name): bool => request()->routeIs($name);
@endphp

<nav
    aria-label="{{ __('ui.account_settings.nav_aria') }}"
    class="w-full shrink-0 rounded-xl border border-zinc-200 bg-zinc-50 p-4 shadow-xs dark:border-zinc-700 dark:bg-zinc-900/80 md:p-5"
>
    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
        {{ __('ui.account_settings.section_nav_hint') }}
    </p>
    <div class="flex flex-wrap gap-2">
        <a
            href="{{ route('settings.profile.edit') }}"
            wire:navigate
            data-test="settings-nav-profile"
            @class([
                'inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium shadow-sm ring-2 transition-colors',
                'bg-white text-zinc-900 ring-blue-600 dark:bg-zinc-950 dark:text-white dark:ring-blue-500' => $isActive('settings.profile.edit'),
                'bg-white/80 text-zinc-800 ring-transparent hover:bg-white dark:bg-zinc-800/80 dark:text-zinc-100 dark:hover:bg-zinc-800' => ! $isActive('settings.profile.edit'),
            ])
        >{{ __('ui.account_settings.nav_profile') }}</a>

        <a
            href="{{ route('settings.password.edit') }}"
            wire:navigate
            data-test="settings-nav-password"
            @class([
                'inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium shadow-sm ring-2 transition-colors',
                'bg-white text-zinc-900 ring-blue-600 dark:bg-zinc-950 dark:text-white dark:ring-blue-500' => $isActive('settings.password.edit'),
                'bg-white/80 text-zinc-800 ring-transparent hover:bg-white dark:bg-zinc-800/80 dark:text-zinc-100 dark:hover:bg-zinc-800' => ! $isActive('settings.password.edit'),
            ])
        >{{ __('ui.account_settings.nav_password') }}</a>

        @if (\Laravel\Fortify\Features::canManageTwoFactorAuthentication())
            <a
                href="{{ route('settings.two-factor.show') }}"
                wire:navigate
                data-test="settings-nav-two-factor"
                @class([
                    'inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium shadow-sm ring-2 transition-colors',
                    'bg-white text-zinc-900 ring-blue-600 dark:bg-zinc-950 dark:text-white dark:ring-blue-500' => $isActive('settings.two-factor.show'),
                    'bg-white/80 text-zinc-800 ring-transparent hover:bg-white dark:bg-zinc-800/80 dark:text-zinc-100 dark:hover:bg-zinc-800' => ! $isActive('settings.two-factor.show'),
                ])
            >{{ __('ui.account_settings.nav_two_factor') }}</a>
        @endif

        <a
            href="{{ route('settings.appearance.edit') }}"
            wire:navigate
            data-test="settings-nav-appearance"
            @class([
                'inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium shadow-sm ring-2 transition-colors',
                'bg-white text-zinc-900 ring-blue-600 dark:bg-zinc-950 dark:text-white dark:ring-blue-500' => $isActive('settings.appearance.edit'),
                'bg-white/80 text-zinc-800 ring-transparent hover:bg-white dark:bg-zinc-800/80 dark:text-zinc-100 dark:hover:bg-zinc-800' => ! $isActive('settings.appearance.edit'),
            ])
        >{{ __('ui.account_settings.nav_appearance') }}</a>
    </div>
</nav>
