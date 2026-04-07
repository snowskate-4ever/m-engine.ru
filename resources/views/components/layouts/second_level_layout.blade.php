<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head', ['title' => $title ?? null])
    </head>
    <body class="flex min-h-screen bg-white dark:bg-zinc-800">
        @livewire('components.left-sidebar')
        <main class="p-4 w-full min-h-screen relative z-0">
            @include('partials.settings-heading', ['title' => $title])
            {{  $slot }}
        </main>
        @fluxScripts
    </body>
</html>
