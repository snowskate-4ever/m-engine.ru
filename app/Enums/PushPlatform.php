<?php

declare(strict_types=1);

namespace App\Enums;

enum PushPlatform: string
{
    case Ios = 'ios';
    case Android = 'android';
}
