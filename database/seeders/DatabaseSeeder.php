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
        ]);

        // Create a default admin user
        User::factory()->create([
            'name' => 'Admin User',
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@kadirafiki.com',
            'phone_number' => '+255762000043',
            'bio' => 'KadiRafiki Team Member',
            'country' => 'Kenya',
            'region' => 'Nairobi',
            'postal_code' => '00100',
            'role_id' => 1, // Admin role
        ]);
    }
}
