<?php

namespace Database\Seeders;

use App\Models\Soldier;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'first_name' => "name",
            'last_name' => "family",
            'password' => Hash::make(1234567),
            'userable_id' => Soldier::factory()->create()->id,
            'userable_type' => "App\Models\Soldier",
        ]);

        $this->call([
            PermissionSeeder::class,
        ]);
        $user->assignRole('manager');
    }
}
