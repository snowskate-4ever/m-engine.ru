<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Logo File Path
    |--------------------------------------------------------------------------
    |
    | Path to the logo SVG file relative to the public directory.
    |
    */
    'file' => env('LOGO_FILE', 'img/music-engine.svg'),

    /*
    |--------------------------------------------------------------------------
    | Logo Alt Text
    |--------------------------------------------------------------------------
    |
    | Alternative text for the logo image.
    |
    */
    'alt' => env('LOGO_ALT', null), // Will fallback to app.name

    /*
    |--------------------------------------------------------------------------
    | Logo Sizes
    |--------------------------------------------------------------------------
    |
    | Default sizes for logo in different contexts.
    | Sizes are defined as Tailwind CSS size classes or pixel values.
    |
    */
    'sizes' => [
        'icon' => env('LOGO_SIZE_ICON', 'size-9'),      // Auth pages, general icons
        'sidebar' => env('LOGO_SIZE_SIDEBAR', 'size-8'), // Sidebar logo
        'center' => null,                                 // Dynamic size for circular menu (calculated)
    ],

    /*
    |--------------------------------------------------------------------------
    | Logo CSS Classes
    |--------------------------------------------------------------------------
    |
    | CSS classes applied to logo in different contexts.
    |
    */
    'classes' => [
        'icon' => 'app-logo-icon',
        'sidebar' => 'app-logo-sidebar',
        'center' => 'logo-center',
    ],

    /*
    |--------------------------------------------------------------------------
    | Theme Filters
    |--------------------------------------------------------------------------
    |
    | CSS filter values for light and dark themes.
    | brightness(0) = black
    | brightness(0) invert(1) = white
    |
    */
    'filters' => [
        'light' => env('LOGO_FILTER_LIGHT', 'brightness(0)'),
        'dark' => env('LOGO_FILTER_DARK', 'brightness(0) invert(1)'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Theme Selectors
    |--------------------------------------------------------------------------
    |
    | CSS selectors for dark theme detection.
    |
    */
    'theme_selectors' => [
        'html.dark',
        '.dark',
        '[data-theme="dark"]',
    ],

    /*
    |--------------------------------------------------------------------------
    | Media Query
    |--------------------------------------------------------------------------
    |
    | Media query for system theme preference.
    |
    */
    'media_query' => '(prefers-color-scheme: dark)',

    /*
    |--------------------------------------------------------------------------
    | Generate CSS Styles
    |--------------------------------------------------------------------------
    |
    | Whether to generate inline CSS styles for logo filters.
    | Set to false if you want to include styles in your CSS file.
    |
    */
    'generate_styles' => env('LOGO_GENERATE_STYLES', true),
];

