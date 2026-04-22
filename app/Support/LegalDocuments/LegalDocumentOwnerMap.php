<?php

declare(strict_types=1);

namespace App\Support\LegalDocuments;

use App\Models\ProducerCenter;
use App\Models\RecordLabel;
use App\Models\Rehersal;
use App\Models\School;
use App\Models\Shop;
use App\Models\Studio;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Model;

final class LegalDocumentOwnerMap
{
    /**
     * @return array<string, class-string<Model>>
     */
    public static function aliases(): array
    {
        return [
            'shop' => Shop::class,
            'school' => School::class,
            'studio' => Studio::class,
            'rehearsal' => Rehersal::class,
            'record_label' => RecordLabel::class,
            'producer_center' => ProducerCenter::class,
            'teacher' => Teacher::class,
        ];
    }

    /**
     * @return class-string<Model>|null
     */
    public static function classByAlias(string $alias): ?string
    {
        $map = self::aliases();

        return $map[$alias] ?? null;
    }
}
