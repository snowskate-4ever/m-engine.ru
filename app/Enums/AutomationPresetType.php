<?php

declare(strict_types=1);

namespace App\Enums;

enum AutomationPresetType: string
{
    case MyAdsBoard = 'my_ads_board';
    case AdResponseToCard = 'ad_response_to_card';
    case BookingClosesAd = 'booking_closes_ad';
    case EventToBoard = 'event_to_board';
    case EventReminder = 'event_reminder';
    case EventStatusChanged = 'event_status_changed';
    case EventLineupChanged = 'event_lineup_changed';
}
