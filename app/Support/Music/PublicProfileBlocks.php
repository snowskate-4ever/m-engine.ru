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
            ['id' => 'bio', 'label_key' => 'ui.music.blocks.bio'],
            ['id' => 'instruments', 'label_key' => 'ui.music.blocks.instruments'],
            ['id' => 'genres', 'label_key' => 'ui.music.blocks.genres'],
            ['id' => 'performers', 'label_key' => 'ui.music.blocks.performers'],
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
