<?php

declare(strict_types=1);

namespace App\Enums;

enum AiRequestStatus: string
{
    case Success = 'success';
    case Error = 'error';
}
