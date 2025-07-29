<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            ['name' => 'Basic Package', 'amount' => 50.00, 'currency' => 'TZS', 'status' => 'Active'],
            ['name' => 'Standard Package', 'amount' => 100.00, 'currency' => 'TZS', 'status' => 'Active'],
            ['name' => 'Premium Package', 'amount' => 150.00, 'currency' => 'TZS', 'status' => 'Active'],
            ['name' => 'VIP Package', 'amount' => 250.00, 'currency' => 'TZS', 'status' => 'Active'],
            ['name' => 'Enterprise Package', 'amount' => 500.00, 'currency' => 'TZS', 'status' => 'Active']
        ];

        foreach ($packages as $package) {
            Package::firstOrCreate($package);
        }
    }
} 