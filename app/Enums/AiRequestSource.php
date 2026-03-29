<?php

declare(strict_types=1);

namespace App\Enums;

enum AiRequestSource: string
{
    case Server = 'server';
    case Byok = 'byok';
}
