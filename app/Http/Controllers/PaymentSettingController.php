<?php

namespace App\Http\Controllers;

use App\Models\PaymentSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PaymentSettingController extends Controller
{
    /**
     * Get payment settings
     */
    public function show(): JsonResponse
    {
        $settings = PaymentSetting::getSettings();
        return response()->json($settings);
    }

    /**
     * Update payment settings
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_terms' => 'required|string|max:500',
            'bank_payment_enabled' => 'boolean',
            'bank_name' => 'nullable|string|max:255',
            'account_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'swift_code' => 'nullable|string|max:255',
            'mobile_money_enabled' => 'boolean',
            'mobile_network' => 'nullable|string|max:255',
            'payment_number' => 'nullable|string|max:255',
            'payment_name' => 'nullable|string|max:255',
        ]);

        $settings = PaymentSetting::updateSettings($validated);

        return response()->json([
            'message' => 'Payment settings updated successfully',
            'settings' => $settings
        ]);
    }
}
