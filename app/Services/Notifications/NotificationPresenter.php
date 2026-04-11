<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Notifications\Music\PerformerLineupInvitationNotification;
use App\Notifications\Music\MatchingLifecycleNotification;
use Illuminate\Notifications\DatabaseNotification;

final class NotificationPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(DatabaseNotification $notification): array
    {
        $type = (string) $notification->type;
        $data = (array) $notification->data;

        return [
            'id' => (string) $notification->id,
            'type' => $type,
            'data' => $data,
            'read_at' => optional($notification->read_at)?->toISOString(),
            'created_at' => optional($notification->created_at)?->toISOString(),
            ...$this->present($type, $data),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{title: string, body: string, action_url: string|null}
     */
    private function present(string $type, array $data): array
    {
        if ($type === PerformerLineupInvitationNotification::class) {
            $profilesUrl = route('music.profiles', ['tab' => 'musician'], true);

            return [
                'title' => __('ui.notifications.music_lineup_invitation_title', [
                    'performer' => (string) ($data['peformer_name'] ?? ''),
                ]),
                'body' => __('ui.notifications.music_lineup_invitation_body', [
                    'inviter' => (string) ($data['inviter_name'] ?? ''),
                    'performer' => (string) ($data['peformer_name'] ?? ''),
                ]),
                'action_url' => $profilesUrl.'#music-musician-lineup',
            ];
        }

        if ($type === MatchingLifecycleNotification::class) {
            $payload = is_array($data['payload'] ?? null) ? $data['payload'] : [];
            $titleKey = is_string($data['title_key'] ?? null) ? $data['title_key'] : 'ui.notifications.generic_title';
            $bodyKey = is_string($data['body_key'] ?? null) ? $data['body_key'] : 'ui.notifications.generic_body';

            return [
                'title' => __($titleKey, $payload),
                'body' => __($bodyKey, $payload),
                'action_url' => route('events', [], true),
            ];
        }

        return [
            'title' => __('ui.notifications.generic_title'),
            'body' => __('ui.notifications.generic_body'),
            'action_url' => null,
        ];
    }
}
