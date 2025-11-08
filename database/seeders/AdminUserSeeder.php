<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);

        User::updateOrCreate(
            ['email' => 'admin@emwaliko.com'],
            [
                'name' => 'Super Admin',
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'phone_number' => '+255762000043',
                'bio' => 'Emwaliko Team Member',
                'country' => 'Tanzania',
                'region' => 'Dar es Salaam',
                'postal_code' => '15129',
                'role_id' => $adminRole->id,
                'status' => 'active',
                'password' => Hash::make('Miracle@2025'),
            ]
        );
    }
}

