<?php

use App\Models\Department;
use App\Models\Soldier;
use App\Models\Team;
use App\Models\User;
use App\Resources\SoldierResource\Pages\ListSoldiers;
use App\Resources\TeamResource\Pages\ListTeams;
use Database\Seeders\PermissionSeeder;

it('allows department commander to view his soldiers', function () {
    $department_commander_user = User::factory()->create([
        'userable_id' => Soldier::factory()->create()->id,
    ]);
    $soldiers = Soldier::factory()->count(10)->create([
        'team_id' => Team::factory()->create([
            'department_id' => Department::factory()->create([
                'commander_id' => $department_commander_user->userable_id,
            ])->id,
        ])->id,
    ]);
    $newSoldiers = Soldier::factory()->count(10)->create([]);
    $this
        ->seed(PermissionSeeder::class)
        ->asUser('department-commander', $department_commander_user)
        ->livewire(ListSoldiers::class)
        ->assertCanSeeTableRecords($soldiers)
        ->assertCanNotSeeTableRecords($newSoldiers);
});

it('allows manager to view all teams', function () {
    $manager = User::factory()->create([]);
    $teams = Team::factory()->count(10)->create([]);
    $this
        ->seed(PermissionSeeder::class)
        ->asUser('manager', $manager)
        ->livewire(ListTeams::class)
        ->assertCanSeeTableRecords($teams);
});
