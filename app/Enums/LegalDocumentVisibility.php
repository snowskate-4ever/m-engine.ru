<?php

declare(strict_types=1);

namespace App\Enums;

enum LegalDocumentVisibility: string
{
    case OwnerOnly = 'owner_only';
    case Public = 'public';
}
