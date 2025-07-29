<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;

class SettingController extends Controller
{
    // Get VAT rate
    public function getVatRate()
    {
        $vat = Setting::getValue('vat_rate', 0.18);
        return response()->json(['vat_rate' => (float)$vat]);
    }

    // Update VAT rate (admin only)
    public function setVatRate(Request $request)
    {
        // You may want to use your own admin check here
        $validated = $request->validate([
            'vat_rate' => 'required|numeric|min:0|max:1',
        ]);
        Setting::setValue('vat_rate', $validated['vat_rate']);
        return response()->json(['vat_rate' => (float)$validated['vat_rate']]);
    }
} 