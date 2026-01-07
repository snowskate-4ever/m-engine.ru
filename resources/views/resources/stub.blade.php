<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ __('moonshine.types.values.' . $type->name) ?: $type->name }} - Laravel</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC] antialiased">
        <div class="min-h-screen flex flex-col items-center justify-center p-6">
            <div class="w-full max-w-md text-center">
                <h1 class="text-3xl font-semibold mb-4 text-[#1b1b18] dark:text-[#EDEDEC]">
                    {{ __('moonshine.types.values.' . $type->name) ?: $type->name }}
                </h1>
                
                <div class="bg-white dark:bg-[#161615] rounded-lg shadow-lg border border-[#e3e3e0] dark:border-[#3E3E3A] p-8 mb-6">
                    <p class="text-[#706f6c] dark:text-[#A1A09A] mb-6">
                        {{ __('ui.auth.required') ?? 'Для просмотра этого раздела необходимо авторизоваться.' }}
                    </p>
                    
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        @if (Route::has('login'))
                            <a
                                href="{{ route('login') }}"
                                class="inline-block px-6 py-2.5 bg-[#1b1b18] dark:bg-[#EDEDEC] text-white dark:text-[#1b1b18] rounded-sm text-sm font-medium hover:opacity-90 transition-opacity"
                            >
                                {{ __('ui.auth.login') ?? 'Войти' }}
                            </a>
                        @endif

                        @if (Route::has('register'))
                            <a
                                href="{{ route('register') }}"
                                class="inline-block px-6 py-2.5 border border-[#19140035] dark:border-[#3E3E3A] text-[#1b1b18] dark:text-[#EDEDEC] rounded-sm text-sm font-medium hover:bg-zinc-800/5 dark:hover:bg-white/[7%] transition-colors"
                            >
                                {{ __('ui.auth.register') ?? 'Регистрация' }}
                            </a>
                        @endif
                    </div>
                </div>

                <a
                    href="{{ route('home') }}"
                    class="inline-block text-sm text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC] transition-colors"
                >
                    ← {{ __('ui.back_to_home') ?? 'Вернуться на главную' }}
                </a>
            </div>
        </div>
    </body>
</html>

