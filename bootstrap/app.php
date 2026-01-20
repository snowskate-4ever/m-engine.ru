<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'detect.channel' => \App\Http\Middleware\DetectAuthChannel::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        // Очистка старых попыток авторизации каждый день в 2:00
        $schedule->command('auth:cleanup')->dailyAt('02:00');

        // Запуск очереди для обработки фоновых задач
        $schedule->command('queue:work --tries=3 --max-jobs=1000')
            ->everyMinute()
            ->withoutOverlapping();
    })
    ->create();
