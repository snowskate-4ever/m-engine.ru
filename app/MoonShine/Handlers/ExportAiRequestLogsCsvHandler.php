<?php

declare(strict_types=1);

namespace App\MoonShine\Handlers;

use App\Services\Ai\AiRequestLogCsvExportService;
use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\Crud\Handlers\Handler;
use MoonShine\UI\Components\ActionButton;
use Symfony\Component\HttpFoundation\Response;

final class ExportAiRequestLogsCsvHandler extends Handler
{
    public function handle(): Response
    {
        $user = auth()->user();
        abort_unless(
            $user !== null && method_exists($user, 'hasRole') && $user->hasRole('admin'),
            403,
        );

        return app(AiRequestLogCsvExportService::class)->streamResponse();
    }

    public function getButton(): ActionButtonContract
    {
        return $this->prepareButton(
            ActionButton::make($this->getLabel(), $this->getUrl())
                ->icon('arrow-down-tray')
                ->blank(),
        );
    }
}
