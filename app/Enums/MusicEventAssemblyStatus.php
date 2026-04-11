<?php

declare(strict_types=1);

namespace App\Enums;

enum MusicEventAssemblyStatus: string
{
    case Incomplete = 'incomplete';
    case Ready = 'ready';
}
