<?php

use App\Models\Department;
use App\Models\User;
use App\Resources\DepartmentResource\Pages\EditDepartment;
use Database\Seeders\PermissionSeeder;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this
        ->seed(PermissionSeeder::class)
        ->asUser('manager');
});

it('should assign department-commander role to the commander', function () {
    $department = Department::factory()->create();
    $commander = User::factory()->create();

    livewire(EditDepartment::class, [
        'record' => $department->id,
    ])
        ->set('data.commander_id', $commander->userable_id)
        ->call('save')
        ->assertHasNoFormErrors();
    $this->assertDatabaseHas('departments', [
        'id' => $department->id,
        'commander_id' => $commander->userable_id,
    ]);

    expect($commander->getRoleNames()->contains('department-commander'))->toBe(true);
});
