<?php

namespace App\Enums;

enum PerformerMembershipStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Left = 'left';
}
