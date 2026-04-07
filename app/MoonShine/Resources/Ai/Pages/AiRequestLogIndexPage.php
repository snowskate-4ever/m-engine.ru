<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Ai\Pages;

use App\MoonShine\Handlers\ExportAiRequestLogsCsvHandler;
use App\MoonShine\Resources\Ai\AiRequestLogResource;
use MoonShine\Crud\Handlers\Handler;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Support\ListOf;

/**
 * @extends IndexPage<AiRequestLogResource>
 */
final class AiRequestLogIndexPage extends IndexPage
{
    /**
     * @return ListOf<Handler>
     */
    protected function handlers(): ListOf
    {
        return new ListOf(Handler::class, [
            ExportAiRequestLogsCsvHandler::make(__('moonshine.ai.export_csv')),
        ]);
    }
}
