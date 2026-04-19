@php
    use App\Helpers\LogoHelper;
    $inviteToken = request()->query('invite');
    $canShowRegister = is_string($inviteToken) && app(\App\Services\Auth\RegistrationInviteService::class)->isActiveToken($inviteToken);
    $totalProfiles = array_sum(array_map('intval', $catalogCounts ?? []));
    $totalCategories = count($discoverCategories ?? []);
@endphp

<x-layouts.public-minimal
    :title="__('ui.music.landing.title')"
    :meta-description="__('ui.music.landing.meta_description')"
    :canonical-url="url()->route('music.landing')"
>
    <div data-music-landing class="relative overflow-hidden bg-zinc-950 text-zinc-100">
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 overflow-hidden">
            <div data-landing-parallax="0.14" class="absolute -left-20 top-12 h-72 w-72 rounded-full bg-fuchsia-500/20 blur-3xl"></div>
            <div data-landing-parallax="0.2" class="absolute right-[-120px] top-0 h-96 w-96 rounded-full bg-sky-500/20 blur-3xl"></div>
            <div data-landing-parallax="0.1" class="absolute bottom-[-140px] left-1/3 h-80 w-80 rounded-full bg-violet-500/20 blur-3xl"></div>
        </div>

        <header class="relative z-10 border-b border-zinc-800 bg-zinc-950/80 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2">
                    <img
                        src="{{ LogoHelper::getPath() }}"
                        alt="{{ LogoHelper::getAlt() }}"
                        class="{{ LogoHelper::getClass('sidebar') }} h-8 w-8"
                    />
                    <span class="text-sm font-semibold tracking-wide">M-Engine</span>
                </a>
                <nav class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                    <a href="{{ route('discover') }}" class="text-zinc-300 transition hover:text-zinc-100">
                        {{ __('ui.music.landing.nav_discover') }}
                    </a>
                    @guest
                        <a href="{{ route('login') }}" class="text-zinc-300 transition hover:text-zinc-100">
                            {{ __('ui.auth.login.button') }}
                        </a>
                        @if (Route::has('register') && $canShowRegister)
                            <a href="{{ route('register', ['invite' => $inviteToken]) }}" class="rounded-md bg-sky-500 px-3 py-1.5 text-zinc-950 transition hover:bg-sky-400">
                                {{ __('ui.auth.register.button') }}
                            </a>
                        @endif
                    @else
                        <a href="{{ route('dashboard') }}" class="rounded-md bg-sky-500 px-3 py-1.5 text-zinc-950 transition hover:bg-sky-400">
                            {{ __('ui.dashboard') }}
                        </a>
                    @endguest
                </nav>
            </div>
        </header>

        <main class="relative z-10 py-6 lg:py-10">
            <section class="mx-auto max-w-6xl px-4 pb-14 pt-16 sm:px-6 lg:px-8 lg:pt-24">
                <p data-landing-fade class="mx-auto inline-flex rounded-full border border-zinc-700 bg-zinc-900/80 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-zinc-300">
                    {{ __('ui.music.landing.hero_badge') }}
                </p>
                <div class="mt-6 max-w-2xl">
                    <h1 data-landing-stagger class="text-balance text-4xl font-semibold leading-tight sm:text-5xl">
                        {{ __('ui.music.landing.hero_title') }}
                    </h1>
                    <p data-landing-stagger class="mt-4 text-base text-zinc-300 sm:text-lg">
                        {{ __('ui.music.landing.hero_subtitle') }}
                    </p>
                </div>
                <div class="mt-8 grid gap-4 lg:grid-cols-12">
                    <aside data-landing-stagger class="space-y-4 lg:col-span-3">
                        <div class="rounded-2xl border border-zinc-800 bg-zinc-900/80 p-4">
                            <p class="text-xs uppercase tracking-wider text-zinc-400">Данные каталога</p>
                            <p class="mt-2 text-sm font-semibold text-zinc-100">Профилей в каталоге: {{ number_format($totalProfiles, 0, '.', ' ') }}</p>
                            <p class="mt-2 text-sm font-semibold text-zinc-100">Категорий: {{ $totalCategories }}</p>
                        </div>
                        <div class="rounded-2xl border border-zinc-800 bg-zinc-900/80 p-4">
                            <p class="text-xs uppercase tracking-wider text-zinc-400">Гостевой доступ</p>
                            <p class="mt-2 text-sm font-semibold text-zinc-100">Без регистрации доступны категории и профили</p>
                            <p class="mt-2 text-xs text-zinc-400">Используйте каталог и фильтры, чтобы оценить сервис.</p>
                        </div>
                    </aside>
                    <div data-landing-stagger class="relative overflow-hidden rounded-3xl border border-cyan-300/40 bg-gradient-to-r from-zinc-900/85 via-slate-900/80 to-sky-950/70 p-6 shadow-2xl shadow-black/30 backdrop-blur-xl lg:col-span-6 lg:p-8">
                        <div aria-hidden="true" class="pointer-events-none absolute -right-12 -top-10 h-40 w-40 rounded-full bg-cyan-300/25 blur-3xl"></div>
                        <div aria-hidden="true" class="pointer-events-none absolute -bottom-20 left-16 h-44 w-44 rounded-full bg-violet-500/25 blur-3xl"></div>
                        <div class="relative space-y-5">
                            <div class="inline-flex items-center gap-2 rounded-full border border-zinc-700 bg-zinc-900/85 px-3 py-1 text-xs text-zinc-300">
                                <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                                Гостевой режим просмотра
                            </div>
                            <h2 class="text-2xl font-semibold leading-tight sm:text-3xl">Музыка, люди и возможности в одной акцентной горизонтальной зоне.</h2>
                            <p class="max-w-xl text-sm text-zinc-300 sm:text-base">
                                Смотрите категории, находите музыкантов и проверяйте активные профили без создания аккаунта.
                            </p>
                            <div class="rounded-xl border border-zinc-700 bg-zinc-950/80 p-3">
                                <div class="flex items-center gap-3 text-sm text-zinc-400">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-zinc-800">&gt;&gt;</span>
                                    <span>Поиск по роли, городу, жанру и типу площадки...</span>
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <div class="rounded-lg border border-zinc-700 bg-zinc-950/75 p-2 text-center">
                                    <p class="text-xs text-zinc-400">Профили</p>
                                    <p class="mt-1 text-sm font-semibold text-zinc-100">{{ number_format($totalProfiles, 0, '.', ' ') }}</p>
                                </div>
                                <div class="rounded-lg border border-zinc-700 bg-zinc-950/75 p-2 text-center">
                                    <p class="text-xs text-zinc-400">Категории</p>
                                    <p class="mt-1 text-sm font-semibold text-zinc-100">{{ $totalCategories }}</p>
                                </div>
                                <div class="rounded-lg border border-zinc-700 bg-zinc-950/75 p-2 text-center">
                                    <p class="text-xs text-zinc-400">Тема</p>
                                    <p class="mt-1 text-sm font-semibold text-zinc-100 uppercase">Тёмная</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <a href="{{ route('discover') }}" class="rounded-lg bg-sky-500 px-5 py-3 text-sm font-semibold text-zinc-950 transition hover:-translate-y-0.5 hover:bg-sky-400">
                                    {{ __('ui.music.landing.hero_cta_primary') }}
                                </a>
                                @if (Route::has('register') && $canShowRegister)
                                    <a href="{{ route('register', ['invite' => $inviteToken]) }}" class="rounded-lg border border-zinc-600 bg-zinc-900/70 px-5 py-3 text-sm font-semibold text-zinc-100 transition hover:-translate-y-0.5 hover:border-sky-400">
                                        {{ __('ui.music.landing.hero_cta_secondary') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    <aside data-landing-stagger class="space-y-4 lg:col-span-3">
                        <div class="rounded-2xl border border-zinc-800 bg-zinc-900/80 p-4">
                            <p class="text-xs uppercase tracking-wider text-zinc-400">Каталог</p>
                            <p class="mt-2 text-sm font-semibold text-zinc-100">Категории каталога</p>
                            <ul class="mt-3 space-y-2 text-sm text-zinc-300">
                                @foreach (array_slice($discoverCategories, 0, 4) as $category)
                                    <li class="rounded-lg border border-zinc-800 bg-zinc-950/70 px-3 py-2">
                                        {{ __('ui.music.discover_category.' . $category) }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="rounded-2xl border border-zinc-800 bg-zinc-900/80 p-4">
                            <p class="text-xs uppercase tracking-wider text-zinc-400">Быстрый старт</p>
                            <ul class="mt-2 space-y-2 text-sm text-zinc-300">
                                <li>1. Откройте нужную категорию</li>
                                <li>2. Примените фильтры по задаче</li>
                                <li>3. Перейдите к подробному профилю</li>
                            </ul>
                        </div>
                        <div class="relative flex items-center justify-center">
                            <div class="relative h-44 w-44 sm:h-52 sm:w-52">
                                <div data-landing-logo-ring class="absolute inset-0 rounded-full border border-zinc-700/80"></div>
                                <div data-landing-logo-ring class="absolute inset-4 rounded-full border border-zinc-700/60"></div>
                                <div data-landing-logo class="absolute inset-0 flex items-center justify-center rounded-full bg-zinc-900/90 shadow-2xl shadow-black/35">
                                    <img
                                        src="{{ LogoHelper::getPath() }}"
                                        alt="{{ LogoHelper::getAlt() }}"
                                        class="{{ LogoHelper::getClass('center') }} h-full w-full object-cover"
                                    />
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </section>

            <section class="mx-auto max-w-6xl px-4 pb-12 sm:px-6 lg:px-8">
                <div data-landing-reveal class="rounded-2xl border border-zinc-800 bg-zinc-900/75 p-6">
                    <h2 class="text-2xl font-semibold">{{ __('ui.music.landing.catalog_title') }}</h2>
                    <p class="mt-2 max-w-2xl text-sm text-zinc-300">{{ __('ui.music.landing.catalog_subtitle') }}</p>
                    <div class="swiper mt-6 overflow-hidden" data-landing-catalog-swiper>
                        <div class="swiper-wrapper">
                            @foreach ($discoverCategories as $category)
                                <div class="swiper-slide h-auto">
                                    <a
                                        href="{{ route('discover.category', ['category' => $category]) }}"
                                        data-landing-card
                                        class="group relative block h-full overflow-hidden rounded-xl border border-zinc-800 bg-zinc-950/85 p-4 transition"
                                    >
                                        <p class="text-xs uppercase tracking-wider text-zinc-400">
                                            {{ __('ui.music.discover_type.' . $category) }}
                                        </p>
                                        <p class="mt-1 text-lg font-semibold">
                                            {{ __('ui.music.discover_category.' . $category) }}
                                        </p>
                                        <p class="mt-3 text-sm text-zinc-400">
                                            {{ trans_choice('ui.music.landing.catalog_count', (int) ($catalogCounts[$category] ?? 0), ['count' => $catalogCounts[$category] ?? 0]) }}
                                        </p>
                                        <span class="mt-4 inline-flex text-sm font-medium text-sky-300 transition group-hover:translate-x-1">
                                            {{ __('ui.music.landing.open_category') }} ->
                                        </span>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="mt-5 flex items-center justify-between gap-3">
                        <button type="button" data-landing-catalog-prev class="rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-200 transition hover:border-sky-400">←</button>
                        <div data-landing-catalog-pagination class="text-center"></div>
                        <button type="button" data-landing-catalog-next class="rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-200 transition hover:border-sky-400">→</button>
                    </div>
                </div>
            </section>

            <section class="mx-auto grid max-w-6xl gap-6 px-4 pb-12 sm:px-6 lg:grid-cols-2 lg:px-8">
                <article data-landing-reveal class="rounded-2xl border border-zinc-800 bg-zinc-900/75 p-6">
                    <h3 class="text-xl font-semibold">{{ __('ui.music.landing.guest_title') }}</h3>
                    <ul class="mt-4 space-y-3 text-sm text-zinc-300">
                        <li>{{ __('ui.music.landing.guest_item_directory') }}</li>
                        <li>{{ __('ui.music.landing.guest_item_profiles') }}</li>
                        <li>{{ __('ui.music.landing.guest_item_filters') }}</li>
                    </ul>
                    <a href="{{ route('discover') }}" class="mt-5 inline-flex rounded-lg border border-zinc-600 bg-zinc-950/80 px-4 py-2 text-sm font-semibold transition hover:border-sky-400">
                        {{ __('ui.music.landing.guest_cta') }}
                    </a>
                </article>
                <article data-landing-reveal class="rounded-2xl border border-zinc-800 bg-zinc-900/75 p-6">
                    <h3 class="text-xl font-semibold">{{ __('ui.music.landing.how_title') }}</h3>
                    <ol class="mt-4 space-y-4 text-sm text-zinc-300">
                        <li><strong class="text-zinc-100">1.</strong> {{ __('ui.music.landing.how_step_one') }}</li>
                        <li><strong class="text-zinc-100">2.</strong> {{ __('ui.music.landing.how_step_two') }}</li>
                        <li><strong class="text-zinc-100">3.</strong> {{ __('ui.music.landing.how_step_three') }}</li>
                    </ol>
                </article>
            </section>

            <section data-landing-reveal class="mx-auto max-w-6xl px-4 pb-16 sm:px-6 lg:px-8 lg:pb-24">
                <div class="rounded-2xl border border-zinc-700 bg-gradient-to-r from-zinc-900 via-zinc-900 to-sky-950/70 p-8">
                    <h3 class="text-2xl font-semibold">{{ __('ui.music.landing.final_title') }}</h3>
                    <p class="mt-2 max-w-3xl text-sm text-zinc-200">{{ __('ui.music.landing.final_subtitle') }}</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('discover') }}" class="rounded-lg bg-sky-500 px-5 py-3 text-sm font-semibold text-zinc-950 transition hover:bg-sky-400">
                            {{ __('ui.music.landing.final_cta_discover') }}
                        </a>
                        @guest
                            <a href="{{ route('login') }}" class="rounded-lg border border-zinc-600 bg-zinc-950/70 px-5 py-3 text-sm font-semibold transition hover:border-sky-400">
                                {{ __('ui.music.landing.final_cta_login') }}
                            </a>
                            @if (Route::has('register') && $canShowRegister)
                                <a href="{{ route('register', ['invite' => $inviteToken]) }}" class="rounded-lg border border-zinc-600 bg-zinc-950/70 px-5 py-3 text-sm font-semibold transition hover:border-sky-400">
                                    {{ __('ui.music.landing.final_cta_register') }}
                                </a>
                            @endif
                        @endguest
                    </div>
                </div>
            </section>
        </main>
    </div>

    {!! LogoHelper::generateStyles('sidebar') !!}
    {!! LogoHelper::generateStyles('center') !!}
</x-layouts.public-minimal>
