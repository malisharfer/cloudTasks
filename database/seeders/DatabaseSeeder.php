<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Soldier;
use App\Models\User;
use App\Enums\ConstraintType;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
        ]);
    }
}
