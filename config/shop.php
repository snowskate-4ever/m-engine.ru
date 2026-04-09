<?php

declare(strict_types=1);

return [
    /*
    | PSP / онлайн-оплата заказов магазина в v1 не подключена: статусы и суммы ведутся в БД и MoonShine;
    | точка расширения — отдельный сервис оплаты + webhook, без изменения снимка позиций заказа.
    */
    /*
    | Доля комиссии платформы (0.01 = 1%). Пер-магазин: nullable platform_fee_rate в shops.
    */
    'platform_fee_rate' => (float) env('SHOP_PLATFORM_FEE_RATE', 0),
];
