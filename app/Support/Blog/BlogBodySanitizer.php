<?php

declare(strict_types=1);

namespace App\Support\Blog;

final class BlogBodySanitizer
{
    private const ALLOWED_TAGS = '<p><br><br/><strong><b><em><i><u><a><ul><ol><li><blockquote><h2><h3><pre><code>';

    public static function sanitize(string $html): string
    {
        $clean = strip_tags($html, self::ALLOWED_TAGS);

        return preg_replace('/\son\w+\s*=\s*([\'\"]).*?\1/iu', '', $clean) ?? $clean;
    }
}
