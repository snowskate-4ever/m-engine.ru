<?php

declare(strict_types=1);

namespace App\MoonShine\Handlers;

use Illuminate\Support\Facades\Artisan;
use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\Crud\Handlers\Handler;
use MoonShine\UI\Components\ActionButton;
use Symfony\Component\HttpFoundation\Response;

final class RunMatchingEntitiesNowHandler extends Handler
{
    public function handle(): Response
    {
        $user = auth()->user();
        abort_unless(
            $user !== null && method_exists($user, 'hasRole') && $user->hasRole('admin'),
            403,
        );

        Artisan::call('music:run-matching', [
            '--scope' => 'entities',
            '--manual' => true,
            '--run-by-user-id' => (string) $user->id,
        ]);

        return redirect()->back();
    }

    public function getButton(): ActionButtonContract
    {
        return $this->prepareButton(
            ActionButton::make($this->getLabel(), $this->getUrl())
                ->icon('building-office')
        );
    }
}
