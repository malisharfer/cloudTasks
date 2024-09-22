<?php

use App\Models\Department;
use App\Models\Team;
use App\Models\User;
use App\Resources\TeamResource\Pages\CreateTeam;
use Database\Seeders\PermissionSeeder;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this
        ->seed(PermissionSeeder::class)
        ->asUser('manager');
});

it('should assign team-commander role to the commander', function () {
    $user1 = User::factory()->create();

    $department = Department::factory()->create();

    livewire(CreateTeam::class)
        ->set('data.name', 'team1')
        ->set('data.commander_id', $user1->userable_id)
        ->set('data.department_id', $department->id)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(Team::class, [
        'name' => 'team1',
        'commander_id' => $user1->userable_id,
        'department_id' => $department->id,
    ]);

    expect($user1->getRoleNames()->contains('team-commander'))->toBe(true);
});
