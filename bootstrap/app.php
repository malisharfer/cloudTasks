<?php

use App\Models\Soldier;
use App\Services\Algorithm;
use App\Services\ConcurrentTasks;
use App\Services\DailyShiftNotification;
use App\Services\FixedConstraints;
use App\Services\RecurringEvents;
use App\Services\ShiftAssignmentNotification;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule) {
        $schedule->call(fn () => app(FixedConstraints::class)->createFixedConstraints())->monthlyOn(20, '06:00');
        $schedule->call(fn () => app(RecurringEvents::class)->recurringTask())->monthlyOn(20, '08:00');
        $schedule->call(fn () => app(Algorithm::class)->run())->monthlyOn(20, '10:00');
        $schedule->call(fn () => app(ConcurrentTasks::class)->run())->monthlyOn(20, '12:00');
        $schedule->call(fn () => app(ShiftAssignmentNotification::class)->sendNotification())->monthlyOn(1, '08:00');
        $schedule->call(fn () => app(DailyShiftNotification::class)->beforeShift())->dailyAt('06:00');
        $schedule->call(fn () => app(Soldier::class)->updateReserveDays())->monthlyOn(1, '00:00');
    })
    ->withMiddleware(function (Middleware $middleware) {})
    ->withExceptions(function (Exceptions $exceptions) {})->create();
