<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScannerRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if scanner role already exists
        $scannerRole = DB::table('roles')->where('name', 'scanner')->first();
        
        if (!$scannerRole) {
            DB::table('roles')->insert([
                'name' => 'scanner',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
