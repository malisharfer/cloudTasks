<?php

use App\Models\Department;
use App\Models\Soldier;
use App\Models\User;
use App\Resources\DepartmentResource\Pages\ListDepartments;
use Database\Seeders\PermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this
        ->seed(PermissionSeeder::class)
        ->asUser('manager');
});

it('should return departments by commander_id', function () {
    $department1 = Department::factory()->create([
        'commander_id' => User::factory()->create([
            'userable_id' => Soldier::factory()->create()->id,
        ])->userable_id,
    ]);

    Department::factory()->create([
        'commander_id' => User::factory()->create([
            'userable_id' => Soldier::factory()->create()->id,
        ])->userable_id,
    ]);

    Livewire::withQueryParams(['commander_id' => $department1->commander_id])
        ->test(ListDepartments::class)
        ->assertCountTableRecords(1);
});

it('should return department by department_id', function () {
    $department1 = Department::factory()->create();
    Department::factory()->count(4)->create();

    Livewire::withQueryParams(['department_id' => $department1->id])
        ->test(ListDepartments::class)
        ->assertCountTableRecords(1);
});
