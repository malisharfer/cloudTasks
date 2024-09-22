<?php

use App\Services\ReccurenceEvents;
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
        $schedule->call(fn () => app(ReccurenceEvents::class)->recurrenceTask())->monthlyOn(20);
    })
    ->withMiddleware(function (Middleware $middleware) {})
    ->withExceptions(function (Exceptions $exceptions) {})->create();
