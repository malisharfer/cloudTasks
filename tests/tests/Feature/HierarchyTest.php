<?php

use App\Models\Department;
use App\Models\Soldier;
use App\Models\Team;
use App\Models\User;
use App\Resources\SoldierResource\Pages\ListSoldiers;
use App\Resources\TeamResource\Pages\ListTeams;
use Database\Seeders\PermissionSeeder;

it('allows department commander to view his soldiers', function () {
    $commander = User::factory()->create(['userable_id' => Soldier::factory()->create()->id]);
    $department = Department::factory()->create(['commander_id' => $commander->id]);
    $team = Team::factory()->create(['department_id' => $department->id]);
    $soldiers = Soldier::factory()->count(5)->create(['team_id' => $team->id]);
    $newSoldiers = Soldier::factory()->count(10)->create([]);
    $this
        ->seed(PermissionSeeder::class)
        ->asUser('department-commander', $commander)
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
