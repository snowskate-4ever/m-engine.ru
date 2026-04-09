<?php

declare(strict_types=1);

namespace App\Enums;

enum ShopPaymentMethod: string
{
    /** Оплата не подключена (заглушка до агрегатора). */
    case None = 'none';
    /** Счёт / ручное подтверждение в админке. */
    case Manual = 'manual';
    /** Платёж через агрегатор (интеграция позже). */
    case Aggregator = 'aggregator';
}
