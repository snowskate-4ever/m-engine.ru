<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\User;
use App\Services\Messenger\MessengerService;

final class SystemNotificationMirrorService
{
    public function __construct(
        private readonly MessengerService $messengerService,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function mirror(User $user, array $payload): void
    {
        $title = $this->resolveString($payload['title'] ?? null)
            ?? $this->resolveString($payload['title_key'] ?? null)
            ?? (string) __('ui.notifications.generic_title');

        $body = $this->resolveString($payload['body'] ?? null)
            ?? $this->resolveString($payload['body_key'] ?? null)
            ?? '';

        if ($title === '' && $body === '' && isset($payload['peformer_name'], $payload['inviter_name'])) {
            $title = (string) __('ui.notifications.music_lineup_invitation_title', [
                'performer' => (string) $payload['peformer_name'],
            ]);
            $body = (string) __('ui.notifications.music_lineup_invitation_body', [
                'inviter' => (string) $payload['inviter_name'],
                'performer' => (string) $payload['peformer_name'],
            ]);
        }

        $message = trim($title.($body !== '' ? ': '.$body : ''));
        if ($message === '') {
            return;
        }

        $conversation = $this->messengerService->getOrCreateNoticeFeed($user);
        $this->messengerService->postSystemMessage($conversation, $message, false);
    }

    private function resolveString(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_array($value)) {
            if (isset($value[0]) && is_string($value[0])) {
                $replace = $value[1] ?? [];

                return (string) (is_array($replace) ? __($value[0], $replace) : __($value[0]));
            }
            if (isset($value['key']) && is_string($value['key'])) {
                $replace = $value['replace'] ?? [];

                return (string) (is_array($replace) ? __($value['key'], $replace) : __($value['key']));
            }
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return null;
    }
}
