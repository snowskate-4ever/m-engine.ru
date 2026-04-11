<?php

declare(strict_types=1);

namespace App\Models\Concerns;

trait ModeratablePublicProfile
{
    public function isModerationHidden(): bool
    {
        return $this->moderation_hidden_at !== null;
    }

    public function isModerationReviewRequested(): bool
    {
        return $this->moderation_review_requested_at !== null;
    }
}
