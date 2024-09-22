<?php

use App\Models\Soldier;
use App\Resources\SoldierResource\Pages\ListSoldiers;
use Database\Seeders\PermissionSeeder;

it('after replicating a soldier, it redirects to edit the replicated soldier', function () {
    $soldier = Soldier::factory()->create();
    $this
        ->seed(PermissionSeeder::class)
        ->asUser('manager')
        ->livewire(ListSoldiers::class)
        ->callTableAction('replicate', $soldier)
        ->assertRedirect(route('filament.app.resources.soldiers.edit', ['record' => Soldier::latest('id')->first()->id]));
});
