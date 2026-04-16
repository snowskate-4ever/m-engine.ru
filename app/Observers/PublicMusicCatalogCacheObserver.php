<?php

declare(strict_types=1);

namespace App\Observers;

use App\Services\Music\MusicPublicSearchService;
use Illuminate\Database\Eloquent\Model;

/**
 * Сбрасывает кэш счётчиков публичного каталога при изменении публикации/slug/удалении.
 */
final class PublicMusicCatalogCacheObserver
{
    /** @var list<string> */
    private const WATCH = ['public_page_enabled', 'slug', 'deleted_at', 'moderation_status', 'moderation_hidden_at'];

    public function saved(Model $model): void
    {
        if ($model->wasRecentlyCreated) {
            MusicPublicSearchService::forgetPublicCatalogCountsCache();

            return;
        }

        foreach (self::WATCH as $key) {
            if ($model->wasChanged($key)) {
                MusicPublicSearchService::forgetPublicCatalogCountsCache();

                return;
            }
        }
    }

    public function deleted(Model $model): void
    {
        MusicPublicSearchService::forgetPublicCatalogCountsCache();
    }

    public function restored(Model $model): void
    {
        MusicPublicSearchService::forgetPublicCatalogCountsCache();
    }
}
