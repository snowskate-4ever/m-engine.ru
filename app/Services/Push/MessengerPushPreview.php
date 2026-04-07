<?php

declare(strict_types=1);

namespace App\Services\Push;

use App\Enums\MessageKind;
use App\Models\Message;
use Illuminate\Support\Str;

final class MessengerPushPreview
{
    /**
     * @return array{title: string, body: string}
     */
    public static function forMessage(Message $message): array
    {
        $message->loadMissing(['user', 'attachments']);

        $title = $message->user !== null
            ? (string) $message->user->name
            : 'Messenger';

        if ($message->is_forward) {
            return [
                'title' => $title,
                'body' => 'Forwarded message',
            ];
        }

        if ($message->kind === MessageKind::File) {
            $name = $message->attachments->first()?->original_name;

            return [
                'title' => $title,
                'body' => ($name !== null && $name !== '')
                    ? 'File: '.$name
                    : 'File',
            ];
        }

        $body = (string) ($message->body ?? '');
        if ($body === '') {
            return [
                'title' => $title,
                'body' => 'New message',
            ];
        }

        return [
            'title' => $title,
            'body' => Str::limit($body, 120),
        ];
    }
}
