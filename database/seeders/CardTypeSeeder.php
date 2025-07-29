<?php

namespace Database\Seeders;

use App\Models\CardType;
use Illuminate\Database\Seeder;

class CardTypeSeeder extends Seeder
{
    public function run(): void
    {
        $cardTypes = [
            ['name' => 'Invitation', 'status' => 'Active'],
            ['name' => 'Donation', 'status' => 'Active']
        ];

        foreach ($cardTypes as $cardType) {
            CardType::firstOrCreate($cardType);
        }
    }
} 