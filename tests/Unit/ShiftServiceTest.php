<?php

use App\Enums\DaysInWeek;
use App\Services\Range;
use App\Services\Shift;
use Carbon\Carbon;

it('should return the day before and after if the shift is at night', function () {
    $shift = new Shift(
        1,
        'test',
        '2024-12-17 22:30:00',
        '2024-12-18 06:00:00',
        0.25,
        true,
        false,
        false,
        false,
        []
    );

    $result = $shift->getShiftSpaces([]);

    $expected = [
        new Range(Carbon::parse('2024-12-17 10:30:00'), Carbon::parse('2024-12-17 22:30:00')),
        new Range(Carbon::parse('2024-12-18 06:00:00'), Carbon::parse('2024-12-18 18:00:00')),
    ];

    expect(count($result))->toBe(count($expected));

    foreach ($result as $index => $range) {
        expect($range->start->toString())->toEqual($expected[$index]->start->toString());
        expect($range->end->toString())->toEqual($expected[$index]->end->toString());
    }
});

it('should return the next sunday if the shift is at weekend', function () {
    $shift = new Shift(
        1,
        'test',
        '2024-11-08 22:30:00',
        '2024-11-09 09:00:00',
        0.25,
        false,
        true,
        false,
        false,
        []
    );

    $result = $shift->getShiftSpaces([]);

    $expected = [
        new Range(Carbon::parse('2024-11-10 08:00:00'), Carbon::parse('2024-11-11 08:00:00')),
    ];

    expect(count($result))->toBe(count($expected));

    foreach ($result as $index => $range) {
        expect($range->start->toString())->toEqual($expected[$index]->start->toString());
        expect($range->end->toString())->toEqual($expected[$index]->end->toString());
    }
});

it('should return the next sunday if the shift is full weekend', function () {

    $range = new Range('2024-11-08 16:30:00', '2024-11-09 22:00:00');

    $shift = new Shift(1, 'test', '2024-11-08 16:30:00', '2024-11-09 22:00:00', 0.25, false, true, false, false, []);
    $reflection = new ReflectionClass(Shift::class);

    $method = $reflection->getMethod('getWeekendSpaces');
    $method->setAccessible(true);
    $result = $method->invoke($shift, $range, []);

    $expected = [
        new Range(Carbon::parse('2024-11-10 08:00:00'), Carbon::parse('2024-11-11 08:00:00')),
    ];

    expect(count($result))->toBe(count($expected));

    foreach ($result as $index => $range) {
        expect($range->start->toString())->toEqual($expected[$index]->start->toString());
        expect($range->end->toString())->toEqual($expected[$index]->end->toString());
    }
});

it('should not return the next sunday if the shift is not full weekend', function () {
    $shift = new Shift(
        1,
        'test',
        '2024-11-16 16:30:00',
        '2024-11-16 18:30:00',
        0.25,
        false,
        true,
        false,
        false,
        []
    );
    $reflection = new ReflectionClass(Shift::class);

    $method = $reflection->getMethod('getWeekendSpaces');
    $method->setAccessible(true);
    $result = $method->invoke($shift, []);

    expect($result)->toBe(null);
});

it('should return true if the weekend is full', function () {
    $shift = new Shift(
        1,
        'test',
        '2024-11-08 16:30:00',
        '2024-11-09 18:30:00',
        0.25,
        false,
        true,
        false,
        false,
        []
    );

    $reflection = new ReflectionClass(Shift::class);

    $method = $reflection->getMethod('isFullWeekend');
    $method->setAccessible(true);
    $result = $method->invoke($shift, []);

    expect($result)->toBeTrue();
});

it('should return false if the weekend is not full', function () {
    $shift = new Shift(
        1,
        'test',
        '2024-11-16 16:30:00',
        '2024-11-16 18:30:00',
        0.25,
        false,
        true,
        false,
        false,
        []
    );
    $reflection = new ReflectionClass(Shift::class);

    $method = $reflection->getMethod('isFullWeekend');
    $method->setAccessible(true);
    $result = $method->invoke($shift, []);

    expect($result)->toBeFalse();
});

it('should return true if the range includes the supplied day', function () {
    $range = new Range('2024-11-08 16:30:00', '2024-11-11 18:00:00');
    $shift = new Shift(
        1,
        'test',
        '2024-11-08 16:30:00',
        '2024-11-11 18:00:00',
        0.25,
        false,
        true,
        false,
        false,
        []
    );
    $reflection = new ReflectionClass(Shift::class);

    $method = $reflection->getMethod('isShiftInclude');
    $method->setAccessible(true);
    $result = $method->invoke($shift, $range, DaysInWeek::SUNDAY);

    expect($result)->toBeTrue();
});

it('should return false if the range does not include the supplied day', function () {
    $range = new Range('2024-11-08 16:30:00', '2024-11-11 18:00:00');
    $shift = new Shift(
        1,
        'test',
        '2024-11-08 16:30:00',
        '2024-11-11 18:00:00',
        0.25,
        false,
        true,
        false,
        false,
        []
    );
    $reflection = new ReflectionClass(Shift::class);

    $method = $reflection->getMethod('isShiftInclude');
    $method->setAccessible(true);
    $result = $method->invoke($shift, $range, DaysInWeek::WEDNESDAY);

    expect($result)->toBeFalse();
});

it('should return true if the shift date is adjacent to the selected date', function () {
    $shifts = [new Shift(
        1,
        'test',
        '2024-11-09 16:30:00',
        '2024-11-09 18:30:00',
        0.25,
        false,
        true,
        false,
        false,
        []
    )];
    $shift = new Shift(
        1,
        'test',
        '2024-11-08 16:30:00',
        '2024-11-08 18:00:00',
        0.25,
        false,
        true,
        false,
        false,
        []
    );
    $range = new Range('2024-11-08 16:30:00', '2024-11-08 18:00:00');
    $reflection = new ReflectionClass(Shift::class);

    $method = $reflection->getMethod('isAttached');
    $method->setAccessible(true);
    $result = $method->invoke($shift, $shifts, $range, DaysInWeek::SATURDAY);

    expect($result)->toBeTrue();
});

it('should return false if the shift date is not adjacent to the selected date', function () {
    $shifts = [new Shift(
        1,
        'test',
        '2024-11-16 16:30:00',
        '2024-11-16 18:30:00',
        0.25,
        false,
        true,
        false,
        false,
        []
    )];
    $range = new Range('2024-11-08 16:30:00', '2024-11-08 18:00:00');
    $shift = new Shift(
        1,
        'test',
        '2024-11-08 16:30:00',
        '2024-11-08 18:00:00',
        0.25,
        false,
        true,
        false,
        false,
        []
    );
    $reflection = new ReflectionClass(Shift::class);

    $method = $reflection->getMethod('isAttached');
    $method->setAccessible(true);
    $result = $method->invoke($shift, $shifts, $range, DaysInWeek::SATURDAY);

    expect($result)->toBeFalse();
});
