<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Admin'],
            ['name' => 'Customer'],
            ['name' => 'Scanner']
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate($role);
        }
    }
} 