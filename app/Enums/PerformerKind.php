<?php

namespace App\Enums;

enum PerformerKind: string
{
    case Band = 'band';
    case SoloProject = 'solo_project';
    case Other = 'other';
}
