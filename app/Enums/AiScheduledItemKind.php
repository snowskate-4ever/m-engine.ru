<?php

declare(strict_types=1);

namespace App\Enums;

enum AiScheduledItemKind: string
{
    case ModelBilling = 'model_billing';
    case SubscriptionRenewal = 'subscription_renewal';
    case EventBooking = 'event_booking';
    case TaskReminder = 'task_reminder';
    case Custom = 'custom';
}
