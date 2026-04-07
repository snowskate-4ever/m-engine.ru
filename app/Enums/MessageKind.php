<?php

declare(strict_types=1);

namespace App\Enums;

enum MessageKind: string
{
    case Text = 'text';
    case File = 'file';
    case System = 'system';
}
