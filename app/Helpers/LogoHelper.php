<?php

namespace App\Helpers;

class LogoHelper
{
    /**
     * Get logo file path
     */
    public static function getPath(): string
    {
        $file = config('logo.file', 'img/music-engine.svg');
        return asset($file);
    }

    /**
     * Get logo alt text
     */
    public static function getAlt(): string
    {
        return config('logo.alt') ?: config('app.name', 'Laravel');
    }

    /**
     * Get logo size class for context
     */
    public static function getSize(string $context = 'icon'): ?string
    {
        return config("logo.sizes.{$context}");
    }

    /**
     * Get logo CSS class for context
     */
    public static function getClass(string $context = 'icon'): string
    {
        return config("logo.classes.{$context}", 'app-logo-icon');
    }

    /**
     * Generate CSS styles for logo
     */
    public static function generateStyles(string $context = 'icon'): string
    {
        if (!config('logo.generate_styles', true)) {
            return '';
        }

        $class = self::getClass($context);
        $lightFilter = config('logo.filters.light', 'brightness(0)');
        $darkFilter = config('logo.filters.dark', 'brightness(0) invert(1)');
        $themeSelectors = config('logo.theme_selectors', []);
        $mediaQuery = config('logo.media_query', '(prefers-color-scheme: dark)');

        $styles = "<style>\n";
        $styles .= "    .{$class} {\n";
        $styles .= "        filter: {$lightFilter};\n";
        $styles .= "        display: block;\n";
        $styles .= "    }\n";

        // Dark theme selectors
        foreach ($themeSelectors as $selector) {
            $styles .= "    {$selector} .{$class} {\n";
            $styles .= "        filter: {$darkFilter};\n";
            $styles .= "    }\n";
        }

        // Media query for system theme
        $styles .= "    @media {$mediaQuery} {\n";
        $styles .= "        .{$class}:not(.light-theme) {\n";
        $styles .= "            filter: {$darkFilter};\n";
        $styles .= "        }\n";
        $styles .= "    }\n";
        $styles .= "</style>";

        return $styles;
    }
}

