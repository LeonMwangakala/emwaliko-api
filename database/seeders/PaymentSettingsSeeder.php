<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PaymentSetting;

class PaymentSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PaymentSetting::updateOrCreate(
            ['id' => 1],
            [
                'payment_terms' => 'Payment is due within 30 days of invoice date. Please include invoice number with your payment.',
                'bank_payment_enabled' => true,
                'bank_name' => 'Kadirafiki Bank',
                'account_name' => 'Kadirafiki Events Ltd',
                'account_number' => '1234567890',
                'swift_code' => 'KADITZTZ',
                'mobile_money_enabled' => true,
                'mobile_network' => 'M-Pesa',
                'payment_number' => '+255 123 456 789',
                'payment_name' => 'Kadirafiki Events',
            ]
        );
    }
}
