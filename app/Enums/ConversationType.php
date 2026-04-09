<?php

declare(strict_types=1);

namespace App\Enums;

enum ConversationType: string
{
    case Direct = 'direct';
    case Group = 'group';
    case Ai = 'ai';
    /** Лента сервисных сообщений для одного пользователя (заказы, оплаты). */
    case Notice = 'notice';
}
