<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\MusicEcosystem\Pages;

use App\MoonShine\Handlers\RunMatchingAllNowHandler;
use App\MoonShine\Handlers\RunMatchingEntitiesNowHandler;
use App\MoonShine\Handlers\RunMatchingProfilesNowHandler;
use App\MoonShine\Resources\MusicEcosystem\MatchingControlSettingResource;
use MoonShine\Crud\Handlers\Handler;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Support\ListOf;

/**
 * @extends IndexPage<MatchingControlSettingResource>
 */
final class MatchingControlSettingIndexPage extends IndexPage
{
    /**
     * @return ListOf<Handler>
     */
    protected function handlers(): ListOf
    {
        return new ListOf(Handler::class, [
            RunMatchingAllNowHandler::make('Run Matching Now: All'),
            RunMatchingProfilesNowHandler::make('Run Matching Now: Profiles'),
            RunMatchingEntitiesNowHandler::make('Run Matching Now: Entities'),
        ]);
    }
}
