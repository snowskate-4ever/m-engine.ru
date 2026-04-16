<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\ModerationStatus;

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

    /**
     * Публичная «заглушка» по статусу модерации (отдельно от moderation_hidden_at).
     *
     * @return 'pending'|'rejected'|null null — полная страница разрешена
     */
    public function publicModerationOverlay(): ?string
    {
        $status = $this->moderation_status ?? ModerationStatus::Approved;
        if (! $status instanceof ModerationStatus) {
            $status = ModerationStatus::tryFrom((string) $status) ?? ModerationStatus::Approved;
        }

        return match ($status) {
            ModerationStatus::Pending => 'pending',
            ModerationStatus::Rejected => 'rejected',
            ModerationStatus::Approved => null,
        };
    }
}
