<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = collect([
            'soldier',
            'team-commander',
            'department-commander',
            'shifts-assignment',
            'manager',
        ])->map(function (?string $role) {
            Role::firstOrCreate(
                ['name' => $role],
            );
        });
    }
}
