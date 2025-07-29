<?php

namespace Database\Seeders;

use App\Models\CardClass;
use Illuminate\Database\Seeder;

class CardClassSeeder extends Seeder
{
    public function run(): void
    {
        $cardClasses = [
            ['name' => 'SINGLE', 'max_guests' => 1, 'status' => 'Active'],
            ['name' => 'DOUBLE', 'max_guests' => 2, 'status' => 'Active'],
            ['name' => 'MULTIPLE', 'max_guests' => 3, 'status' => 'Active']
        ];

        foreach ($cardClasses as $cardClass) {
            CardClass::firstOrCreate($cardClass);
        }
    }
} 