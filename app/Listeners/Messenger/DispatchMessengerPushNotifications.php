<?php

declare(strict_types=1);

namespace App\Listeners\Messenger;

use App\Events\Messenger\MessageSent;
use App\Jobs\SendMessengerPushNotificationsJob;

class DispatchMessengerPushNotifications
{
    public function handle(MessageSent $event): void
    {
        SendMessengerPushNotificationsJob::dispatch($event->message->id);
    }
}
