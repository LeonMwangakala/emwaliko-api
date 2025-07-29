<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Region;
use App\Models\District;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    public function getCountries(): JsonResponse
    {
        $countries = Country::orderBy('name')->get();
        return response()->json($countries);
    }

    public function getRegions(int $countryId): JsonResponse
    {
        $regions = Region::where('country_id', $countryId)
            ->orderBy('name')
            ->get();
        return response()->json($regions);
    }

    public function getDistricts(int $regionId): JsonResponse
    {
        $districts = District::where('region_id', $regionId)
            ->orderBy('name')
            ->get();
        return response()->json($districts);
    }

    public function getAllLocations(): JsonResponse
    {
        $countries = Country::with(['regions.districts'])->orderBy('name')->get();
        return response()->json($countries);
    }
}
