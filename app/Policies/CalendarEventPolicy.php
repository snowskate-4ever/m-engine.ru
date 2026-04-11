<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CalendarEvent;
use App\Models\User;

class CalendarEventPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CalendarEvent $calendarEvent): bool
    {
        if ($calendarEvent->user_id === $user->id) {
            return true;
        }

        return $calendarEvent->is_public === true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, CalendarEvent $calendarEvent): bool
    {
        return $calendarEvent->user_id === $user->id;
    }

    public function delete(User $user, CalendarEvent $calendarEvent): bool
    {
        return $this->update($user, $calendarEvent);
    }
}
