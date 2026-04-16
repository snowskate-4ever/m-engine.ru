<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Тематика уведомления для разрешения каналов через NotificationGateway.
 */
enum NotificationTopic: string
{
    /** Приглашение в состав (in-app + опционально почта). */
    case LineupInvitation = 'lineup_invitation';

    /** События мэтчинга / заявок (только in-app). */
    case MatchingLifecycle = 'matching_lifecycle';
}
