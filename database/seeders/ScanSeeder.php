<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Scan;
use App\Models\Guest;

class ScanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guests = Guest::take(10)->get();
        
        foreach ($guests as $guest) {
            // Create sample scans for some guests
            if (rand(1, 3) === 1) { // 33% chance of being scanned
                Scan::create([
                    'guest_id' => $guest->id,
                    'quantity' => rand(1, 5),
                    'scan_count' => rand(1, 3),
                    'scanned_by' => 1, // Assuming user ID 1 exists
                    'scanned_date' => now()->subHours(rand(1, 24)),
                    'status' => 'scanned',
                ]);
            } else {
                // Create unscanned records
                Scan::create([
                    'guest_id' => $guest->id,
                    'quantity' => rand(1, 5),
                    'scan_count' => 0,
                    'scanned_by' => null,
                    'scanned_date' => null,
                    'status' => 'not_scanned',
                ]);
            }
        }
    }
} 