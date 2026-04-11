<?php

declare(strict_types=1);

namespace App\Models\Concerns;

trait HasPublicPageLayouts
{
    public function shouldShowPublicBlock(string $blockId): bool
    {
        $layout = $this->layout_published;
        if (! is_array($layout) || empty($layout['blocks']) || ! is_array($layout['blocks'])) {
            return true;
        }

        foreach ($layout['blocks'] as $block) {
            if (! is_array($block)) {
                continue;
            }
            if (($block['id'] ?? '') === $blockId) {
                return (bool) ($block['enabled'] ?? true);
            }
        }

        return false;
    }
}
