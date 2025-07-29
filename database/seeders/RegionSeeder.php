<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tanzania = Country::where('shortform', 'TZ')->first();

        if ($tanzania) {
            $regions = [
                'Arusha',
                'Dar es Salaam',
                'Dodoma',
                'Geita',
                'Iringa',
                'Kagera',
                'Katavi',
                'Kigoma',
                'Kilimanjaro',
                'Lindi',
                'Manyara',
                'Mara',
                'Mbeya',
                'Morogoro',
                'Mtwara',
                'Mwanza',
                'Njombe',
                'Pemba North',
                'Pemba South',
                'Pwani',
                'Rukwa',
                'Ruvuma',
                'Shinyanga',
                'Simiyu',
                'Singida',
                'Songwe',
                'Tabora',
                'Tanga',
                'Unguja North',
                'Unguja South'
            ];

            foreach ($regions as $regionName) {
                Region::create([
                    'country_id' => $tanzania->id,
                    'name' => $regionName
                ]);
            }
        }
    }
}
