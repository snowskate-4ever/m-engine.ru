<?php

declare(strict_types=1);

namespace App\Enums;

enum LegalDocumentType: string
{
    case EntityDetails = 'entity_details';
    case PublicOffer = 'public_offer';
    case PrivacyPolicy = 'privacy_policy';
    case ReturnPolicy = 'return_policy';
    case DeliveryPolicy = 'delivery_policy';
    case Other = 'other';
}
