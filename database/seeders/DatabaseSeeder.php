<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed the basic data first
        $this->call([
            RoleSeeder::class,
            CountrySeeder::class,
            RegionSeeder::class,
            DistrictSeeder::class,
            EventTypeSeeder::class,
            CardTypeSeeder::class,
            CardClassSeeder::class,
            PackageSeeder::class,
            CustomerSeeder::class,
            ScannerRoleSeeder::class,
        ]);

        // Create a default admin user
        User::factory()->create([
            'name' => 'Admin User',
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@emwaliko.com',
            'phone_number' => '+255762000043',
            'bio' => 'Emwaliko Team Member',
            'country' => 'Tanzania',
            'region' => 'Dar es Salaam',
            'postal_code' => '15129',
            'role_id' => 1, // Admin role
        ]);
    }
}
