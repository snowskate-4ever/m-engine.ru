<?php

declare(strict_types=1);

namespace App\Support\Music;

final class PublicProfileBlocks
{
    /**
     * @return list<array{id: string, label_key: string}>
     */
    public static function musicianCatalog(): array
    {
        return [
            ['id' => 'header', 'label_key' => 'ui.music.blocks.header'],
            ['id' => 'description', 'label_key' => 'ui.music.blocks.description'],
            ['id' => 'instruments', 'label_key' => 'ui.music.blocks.instruments'],
            ['id' => 'genres', 'label_key' => 'ui.music.blocks.genres'],
            ['id' => 'cities', 'label_key' => 'ui.music.blocks.cities'],
            ['id' => 'experience', 'label_key' => 'ui.music.blocks.experience'],
            ['id' => 'performers', 'label_key' => 'ui.music.blocks.performers'],
            ['id' => 'links', 'label_key' => 'ui.music.blocks.links'],
            ['id' => 'addresses', 'label_key' => 'ui.music.blocks.addresses'],
        ];
    }

    /**
     * @return list<array{id: string, label_key: string}>
     */
    public static function teacherCatalog(): array
    {
        return [
            ['id' => 'header', 'label_key' => 'ui.music.blocks.header'],
            ['id' => 'description', 'label_key' => 'ui.music.blocks.description'],
            ['id' => 'legal', 'label_key' => 'ui.music.blocks.legal'],
            ['id' => 'cities', 'label_key' => 'ui.music.blocks.cities'],
            ['id' => 'links', 'label_key' => 'ui.music.blocks.links'],
            ['id' => 'addresses', 'label_key' => 'ui.music.blocks.addresses'],
        ];
    }

    /**
     * @return list<array{id: string, label_key: string}>
     */
    public static function performerCatalog(): array
    {
        return [
            ['id' => 'header', 'label_key' => 'ui.music.blocks.header'],
            ['id' => 'description', 'label_key' => 'ui.music.blocks.description'],
            ['id' => 'members', 'label_key' => 'ui.music.blocks.members'],
            ['id' => 'links', 'label_key' => 'ui.music.blocks.links'],
            ['id' => 'addresses', 'label_key' => 'ui.music.blocks.addresses'],
        ];
    }

    /**
     * Студии, репточки, школы — один набор блоков.
     *
     * @return list<array{id: string, label_key: string}>
     */
    public static function venueCatalog(): array
    {
        return [
            ['id' => 'header', 'label_key' => 'ui.music.blocks.header'],
            ['id' => 'description', 'label_key' => 'ui.music.blocks.description'],
            ['id' => 'legal', 'label_key' => 'ui.music.blocks.legal'],
            ['id' => 'links', 'label_key' => 'ui.music.blocks.links'],
            ['id' => 'addresses', 'label_key' => 'ui.music.blocks.addresses'],
        ];
    }

    /**
     * Магазин: те же блоки, что у площадки, плюс витрина позиций.
     *
     * @return list<array{id: string, label_key: string}>
     */
    public static function shopCatalog(): array
    {
        return [
            ['id' => 'header', 'label_key' => 'ui.music.blocks.header'],
            ['id' => 'description', 'label_key' => 'ui.music.blocks.description'],
            ['id' => 'listings', 'label_key' => 'ui.music.blocks.listings'],
            ['id' => 'legal', 'label_key' => 'ui.music.blocks.legal'],
            ['id' => 'links', 'label_key' => 'ui.music.blocks.links'],
            ['id' => 'addresses', 'label_key' => 'ui.music.blocks.addresses'],
        ];
    }

    /**
     * @param  list<array{id: string, enabled: bool, order: int}>  $blocks
     * @return array{version: int, blocks: list<array{id: string, enabled: bool, order: int}>}
     */
    public static function wrapVersion1(array $blocks): array
    {
        return [
            'version' => 1,
            'blocks' => array_values($blocks),
        ];
    }

    /**
     * @param  list<array{id: string, label_key: string}>  $catalog
     * @return list<array{id: string, enabled: bool, order: int}>
     */
    public static function defaultFromCatalog(array $catalog): array
    {
        $blocks = [];
        foreach (array_values($catalog) as $order => $row) {
            $blocks[] = [
                'id' => $row['id'],
                'enabled' => true,
                'order' => $order,
            ];
        }

        return $blocks;
    }
}
