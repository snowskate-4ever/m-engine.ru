<div class="flex items-center justify-center">
    <img 
        src="{{ asset('img/music-engine.svg') }}" 
        alt="{{ config('app.name', 'Laravel') }}"
        class="app-logo-sidebar size-8"
    />
</div>
<div class="ms-1 grid flex-1 text-start text-sm">
    <span class="mb-0.5 truncate leading-tight font-semibold">M-Engine</span>
</div>
<style>
    .app-logo-sidebar {
        filter: brightness(0);
        display: block;
    }
    html.dark .app-logo-sidebar,
    .dark .app-logo-sidebar,
    [data-theme="dark"] .app-logo-sidebar {
        filter: brightness(0) invert(1);
    }
    @media (prefers-color-scheme: dark) {
        .app-logo-sidebar:not(.light-theme) {
            filter: brightness(0) invert(1);
        }
    }
</style>
