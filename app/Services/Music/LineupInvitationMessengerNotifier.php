<?php

declare(strict_types=1);

namespace App\Services\Music;

use App\Models\User;
use App\Services\Messenger\MessengerService;
use Illuminate\Support\Facades\URL;

/**
 * Дублирует приглашение в состав коллектива в ленту мессенджера (ConversationType::Notice).
 */
final class LineupInvitationMessengerNotifier
{
    public function __construct(
        private readonly MessengerService $messenger,
    ) {}

    public function notifyInvited(User $invitee, string $peformerName, string $inviterName): void
    {
        $conversation = $this->messenger->getOrCreateNoticeFeed($invitee);
        $profilesUrl = URL::route('music.profiles', ['tab' => 'musician'], true).'#music-musician-lineup';
        $body = __('ui.music.lineup_notice_invited', [
            'inviter' => $inviterName,
            'performer' => $peformerName,
            'url' => $profilesUrl,
        ]);
        $this->messenger->postSystemMessage($conversation, $body);
    }
}
