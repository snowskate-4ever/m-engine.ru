<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LegalDocument;
use App\Models\User;

class LegalDocumentPolicy
{
    public function view(User $user, LegalDocument $document): bool
    {
        return $this->owns($user, $document);
    }

    public function update(User $user, LegalDocument $document): bool
    {
        return $this->owns($user, $document);
    }

    public function submit(User $user, LegalDocument $document): bool
    {
        return $this->owns($user, $document);
    }

    public function archive(User $user, LegalDocument $document): bool
    {
        return $this->owns($user, $document) || $this->isModerator($user);
    }

    public function moderate(User $user): bool
    {
        return $this->isModerator($user);
    }

    private function owns(User $user, LegalDocument $document): bool
    {
        $owner = $document->owner;
        if (! $owner) {
            return false;
        }

        if (isset($owner->owner_user_id)) {
            return (int) $owner->owner_user_id === (int) $user->id;
        }

        if (isset($owner->user_id)) {
            return (int) $owner->user_id === (int) $user->id;
        }

        return false;
    }

    private function isModerator(User $user): bool
    {
        $ids = (array) config('legal_documents.moderator_user_ids', []);
        $emails = (array) config('legal_documents.moderator_emails', []);

        return in_array((int) $user->id, array_map('intval', $ids), true)
            || in_array((string) $user->email, array_map('strval', $emails), true);
    }
}
