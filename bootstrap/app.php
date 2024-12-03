<?php

use App\Services\Algorithm;
use App\Services\RecurringEvents;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule) {
        $schedule->call(fn () => app(RecurringEvents::class)->recurringTask())->monthlyOn(20, '08:00');
        $schedule->call(fn () => app(Algorithm::class)->run())->monthlyOn(20, '10:00');
    })
    ->withMiddleware(function (Middleware $middleware) {})
    ->withExceptions(function (Exceptions $exceptions) {})->create();
