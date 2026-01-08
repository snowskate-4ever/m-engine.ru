<img 
    src="{{ asset('img/music-engine.svg') }}" 
    alt="{{ config('app.name', 'Laravel') }}"
    class="app-logo-icon"
    {{ $attributes }}
/>
<style>
    .app-logo-icon {
        filter: brightness(0);
        display: block;
    }
    html.dark .app-logo-icon,
    .dark .app-logo-icon,
    [data-theme="dark"] .app-logo-icon {
        filter: brightness(0) invert(1);
    }
    @media (prefers-color-scheme: dark) {
        .app-logo-icon:not(.light-theme) {
            filter: brightness(0) invert(1);
        }
    }
</style>
