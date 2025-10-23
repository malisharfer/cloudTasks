
<?php

use App\Models\Soldier;
use App\Models\User;
use App\Resources\SoldierResource\Pages\ListSoldiers;
use Database\Seeders\PermissionSeeder;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this
        ->seed(PermissionSeeder::class)
        ->asUser('manager');
});

it('hiding the update of reserve dates if the soldier is not a reserve', function () {
    $soldier = Soldier::factory()->create(['is_reservist' => false]);
    User::factory()->create(['userable_id' => $soldier->id]);
    livewire(ListSoldiers::class)
        ->assertTableActionHidden('update reserve days', $soldier);
});

it('can filter soldiers by `is_reservist`', function () {
    $soldier = Soldier::factory()->count(5)->create(['is_reservist' => false])->each(function ($s) {
        User::factory()->create(['userable_id' => $s->id])->assignRole('soldier');
    });
    $reservist = Soldier::factory()->count(5)->create(['is_reservist' => true])->each(function ($r) {
        User::factory()->create(['userable_id' => $r->id])->assignRole('soldier');
    });

    livewire(ListSoldiers::class)
        ->assertCanSeeTableRecords($soldier)
        ->filterTable('reservist', true)
        ->assertCanSeeTableRecords($reservist)
        ->assertCanNotSeeTableRecords($soldier);
});

it('if you edit from a reserve soldier to a simple soldier, the reserve days are updated to null', function () {
    $soldier = Soldier::factory()->create(['is_reservist' => true, 'reserve_dates' => ['2023-04-01', '2023-04-05', '2023-04-19']]);
    User::factory()->create(['userable_id' => $soldier->id]);
    $soldier->user->assignRole('soldier');
    livewire(ListSoldiers::class)
        ->callTableAction('edit', $soldier, ['is_reservist' => false]);
    $this->assertDatabaseHas('soldiers', [
        'reserve_dates' => null,
    ]);
});
