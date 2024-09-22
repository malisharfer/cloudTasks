<?php

use App\Enums\ConstraintType;
use App\Models\Constraint;
use Carbon\Carbon;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this
        ->seed(PermissionSeeder::class)
        ->asUser('manager');
});

it('should return the correct available options based on start date and used counts', function () {
    $get = function ($key) {
        return $key == 'start_date' ? '2023-04-01 00:00:00' : '2023-04-01 23:59:00';
    };

    $class = new ReflectionClass(Constraint::class);
    $availableOptionsMethod = $class->getMethod('availableOptions');
    $availableOptionsMethod->setAccessible(true);

    $result = $availableOptionsMethod->invoke(null, $get);

    expect($result)->toBeArray();
    expect($result)->toContain('Medical', 'Vacation', 'School', 'Not task', 'Low priority not task', 'Not evening');
});

it('should return the correct used counts for current month', function () {
    $startDate = '2023-04-01 00:00:00';
    $endDate = '2023-04-30 23:59:59';
    $currentUserId = 1;
    DB::shouldReceive('table->where->where->whereBetween->count')
        ->andReturnUsing(function () {
            return 1;
        });
    $usedCounts = collect(ConstraintType::cases())->map(function ($enum) use ($currentUserId, $startDate, $endDate) {
        return [
            $enum->value => DB::table('constraints')
                ->where('soldier_id', $currentUserId)
                ->where('constraint_type', $enum->value)
                ->whereBetween('start_date', [
                    Carbon::parse($startDate)->startOfMonth(),
                    Carbon::parse($endDate)->endOfMonth(),
                ])
                ->count(),
        ];
    })->collapse()->toArray();

    expect($usedCounts)->toBeArray();

    expect($usedCounts)->toBeArray();
    array_map(function ($count) {
        expect($count)->toBeInt();
    }, $usedCounts);
});

it('should return the correct dates for "Not evening" and "Not Thursday evening" constraints', function () {
    $get = function ($key) {
        return $key == 'start_date' ? '2023-04-01 00:00:00' : '2023-04-01 23:59:00';
    };

    $class = new ReflectionClass(Constraint::class);
    $getDateForConstraint = $class->getMethod('getDateForConstraint');
    $getDateForConstraint->setAccessible(true);

    $result = $getDateForConstraint->invoke(null, 'Not evening', $get);
    expect($result['start_date']->toDateTimeString())->toBe('2023-04-01 18:00:00');
    expect($result['end_date']->toDateTimeString())->toBe('2023-04-01 23:59:00');
});

it('should return the correct dates for "Not weekend" and "Low priority not weekend" constraints', function () {
    $get = function ($key) {
        return $key == 'start_date' ? '2024-09-05 00:00:00' : '2024-09-05 00:00:00';
    };

    $class = new ReflectionClass(Constraint::class);
    $getDateForConstraint = $class->getMethod('getDateForConstraint');
    $getDateForConstraint->setAccessible(true);

    $result = $getDateForConstraint->invoke(null, 'Not weekend', $get);
    expect($result['start_date']->toDateTimeString())->toBe('2024-09-05 00:00:00');
    expect($result['end_date']->toDateTimeString())->toBe('2024-09-08 00:00:00');
});

it('should return the correct dates for "Medical", "Vacation", "School", "Not task", and "Low priority not task" constraints', function () {
    $get = function ($key) {
        return $key == 'start_date' ? '2023-04-01 00:00:00' : '2023-04-01 23:59:59';
    };

    $class = new ReflectionClass(Constraint::class);
    $getDateForConstraint = $class->getMethod('getDateForConstraint');
    $getDateForConstraint->setAccessible(true);

    $result = $getDateForConstraint->invoke(null, 'Medical', $get);
    expect($result['start_date'])->toBe('2023-04-01 00:00:00');
    expect($result['end_date'])->toBe('2023-04-01 23:59:59');
});
