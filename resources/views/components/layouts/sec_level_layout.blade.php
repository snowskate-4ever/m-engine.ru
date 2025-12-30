<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head', $data)
    </head>
    <body class="flex min-h-screen bg-white dark:bg-zinc-800">
        @livewire('components.left-sidebar')
        <div class="p-4 w-full">
            @include('partials.settings-heading', $data)
            @livewire($data['component'], isset($data['type_id']) ? ['type_id' => $data['type_id']] : [])
        </div>
        @fluxScripts
    </body>
</html>
