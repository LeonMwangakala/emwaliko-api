<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class DistrictSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample districts for Dar es Salaam region
        $darEsSalaam = Region::where('name', 'Dar es Salaam')->first();
        
        if ($darEsSalaam) {
            $districts = [
                'Ilala',
                'Kinondoni',
                'Temeke',
                'Kigamboni',
                'Ubungo'
            ];

            foreach ($districts as $districtName) {
                \App\Models\District::create([
                    'region_id' => $darEsSalaam->id,
                    'name' => $districtName
                ]);
            }
        }

        // Sample districts for Arusha region
        $arusha = Region::where('name', 'Arusha')->first();
        
        if ($arusha) {
            $districts = [
                'Arusha City',
                'Arusha District',
                'Karatu',
                'Longido',
                'Meru',
                'Monduli',
                'Ngorongoro'
            ];

            foreach ($districts as $districtName) {
                \App\Models\District::create([
                    'region_id' => $arusha->id,
                    'name' => $districtName
                ]);
            }
        }

        // Sample districts for Kilimanjaro region
        $kilimanjaro = Region::where('name', 'Kilimanjaro')->first();
        
        if ($kilimanjaro) {
            $districts = [
                'Hai',
                'Moshi District',
                'Moshi Municipal',
                'Mwanga',
                'Rombo',
                'Same'
            ];

            foreach ($districts as $districtName) {
                \App\Models\District::create([
                    'region_id' => $kilimanjaro->id,
                    'name' => $districtName
                ]);
            }
        }
    }
}
