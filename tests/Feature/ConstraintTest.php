<?php

use App\Enums\ConstraintType;
use App\Models\Constraint;
use Carbon\Carbon;
use Database\Seeders\PermissionSeeder;
use Filament\Forms\Get;
use Illuminate\Support\Facades\DB;
use Mockery as mock;

beforeEach(function () {
    $this
        ->seed(PermissionSeeder::class)
        ->asUser('manager');
});

it('should return the correct available options based on start date and used counts', function () {
    $startDate = '2023-04-01 00:00:00';
    $endDate = '2023-04-01 23:59:00';

    $class = new ReflectionClass(Constraint::class);
    $availableOptionsMethod = $class->getMethod('availableOptions');
    $availableOptionsMethod->setAccessible(true);

    $result = $availableOptionsMethod->invoke(null, $startDate, $endDate);

    expect($result)->toBeArray();
    expect($result)->toContain(__('Medical'), __('Vacation'), __('School'), __('Not task'), __('Low priority not task'), __('Not evening'));
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

it('should update dates for "Medical", "Vacation", "School", "Not task", and "Low priority not task" constraints', function () {
    $set = function ($key, $value) use (&$dates) {
        $dates[$key] = $value;
    };

    $getMock = mock::mock(Get::class);
    $getMock->shouldReceive('__invoke')->with('constraint_type')->andReturn('Medical');
    $getMock->shouldReceive('__invoke')->with('start_date')->andReturn('2023-04-01 00:00:00');
    $getMock->shouldReceive('__invoke')->with('end_date')->andReturn('2023-04-01 23:59:00');

    $dates = [];

    Constraint::updateDates($set, null, $getMock);

    expect($dates['start_date'])->toBe('2023-04-01 00:00:00');
    expect($dates['end_date'])->toBe('2023-04-01 23:59:00');
});

it('should update dates for "Not evening" and "Not Thursday evening" constraints', function () {
    $set = function ($key, $value) use (&$dates) {
        $dates[$key] = $value;
    };

    $getMock = mock::mock(Get::class);
    $getMock->shouldReceive('__invoke')->with('constraint_type')->andReturn('Not evening');
    $getMock->shouldReceive('__invoke')->with('start_date')->andReturn('2023-04-01 00:00:00');
    $getMock->shouldReceive('__invoke')->with('end_date')->andReturn('2023-04-01 23:59:00');

    $dates = [];

    Constraint::updateDates($set, null, $getMock);

    expect($dates['start_date'])->toBe('2023-04-01 18:00:00');
    expect($dates['end_date'])->toBe('2023-04-01 23:59:00');
});

it('should update dates for "Not weekend" and "Low priority not weekend" constraints', function () {
    $set = function ($key, $value) use (&$dates) {
        $dates[$key] = $value;
    };

    $getMock = mock::mock(Get::class);
    $getMock->shouldReceive('__invoke')->with('constraint_type')->andReturn('Not weekend');
    $getMock->shouldReceive('__invoke')->with('start_date')->andReturn('2023-04-01 00:00:00');
    $getMock->shouldReceive('__invoke')->with('end_date')->andReturn('2023-04-01 23:59:00');

    $dates = [];

    Constraint::updateDates($set, null, $getMock);

    expect($dates['start_date'])->toBe('2023-03-30 00:00:00');
    expect($dates['end_date'])->toBe('2023-04-01 23:59:00');
});