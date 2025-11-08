<?php

namespace Database\Seeders;

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
            AdminUserSeeder::class,
        ]);
    }
}
