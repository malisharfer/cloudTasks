<?php

use App\Models\Soldier;
use App\Models\User;
use App\Services\FixedConstraints;

it('should create thursday_evening and sunday_morning constraints ', function () {
    $soldier1 = Soldier::factory()->create([
        'course' => 1,
        'max_shifts' => 3,
        'max_nights' => 3,
        'max_weekends' => 3,
        'capacity' => 3,
        'qualifications' => ['run'],
        'not_thursday_evening' => true,
        'not_sunday_morning' => true,
    ]);
    $soldier1 = User::factory()->create([
        'userable_id' => Soldier::factory()->create([
            'qualifications' => (['Clean']),
            'is_reservist' => false,
            'capacity' => 8,
            'max_shifts' => 8,
            'max_nights' => 8,
            'max_weekends' => 8,
            'not_thursday_evening' => true,
            'not_sunday_morning' => true,
        ]),
    ]);
    $result = new FixedConstraints;
    $result->createFixedConstraints();
    $this->assertDatabaseHas('constraints', [
        'soldier_id' => $soldier1->id,
        'constraint_type' => 'Not Thursday evening',
    ]);
    $this->assertDatabaseHas('constraints', [
        'soldier_id' => $soldier1->id,
        'constraint_type' => 'Not Sunday morning',
    ]);
});

it('should create thursday_evening constraint', function () {
    $soldier1 = Soldier::factory()->create([
        'course' => 1,
        'max_shifts' => 3,
        'max_nights' => 3,
        'max_weekends' => 3,
        'capacity' => 3,
        'qualifications' => ['run'],
        'not_thursday_evening' => true,
        'not_sunday_morning' => false,
    ]);
    $soldier1 = User::factory()->create([
        'userable_id' => Soldier::factory()->create([
            'qualifications' => (['Clean']),
            'is_reservist' => false,
            'capacity' => 8,
            'max_shifts' => 8,
            'max_nights' => 8,
            'max_weekends' => 8,
            'not_thursday_evening' => true,
            'not_sunday_morning' => true,
        ]),
    ]);
    $result = new FixedConstraints;
    $result->createFixedConstraints();
    $this->assertDatabaseHas('constraints', [
        'soldier_id' => $soldier1->id,
        'constraint_type' => 'Not Thursday evening',
    ]);
});
