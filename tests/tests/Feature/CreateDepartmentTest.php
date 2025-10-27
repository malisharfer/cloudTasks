<?php

use App\Models\Department;
use App\Models\User;
use App\Resources\DepartmentResource\Pages\CreateDepartment;
use Database\Seeders\PermissionSeeder;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this
        ->seed(PermissionSeeder::class)
        ->asUser('manager');
});

it('should assign department-commander role to the commander', function () {
    $user1 = User::factory()->create();

    livewire(CreateDepartment::class)
        ->set('data.name', 'department1')
        ->set('data.commander_id', $user1->userable_id)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(Department::class, [
        'name' => 'department1',
        'commander_id' => $user1->userable_id,
    ]);

    expect($user1->getRoleNames()->contains('department-commander'))->toBe(true);
});
