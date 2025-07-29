<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_terms',
        'bank_payment_enabled',
        'bank_name',
        'account_name',
        'account_number',
        'swift_code',
        'mobile_money_enabled',
        'mobile_network',
        'payment_number',
        'payment_name',
    ];

    protected $casts = [
        'bank_payment_enabled' => 'boolean',
        'mobile_money_enabled' => 'boolean',
    ];

    /**
     * Get the first payment setting (singleton pattern)
     */
    public static function getSettings(): self
    {
        return self::firstOrCreate([], [
            'payment_terms' => 'Payment is due within 30 days of invoice date.',
            'bank_payment_enabled' => true,
            'mobile_money_enabled' => true,
        ]);
    }

    /**
     * Update payment settings
     */
    public static function updateSettings(array $data): self
    {
        $settings = self::getSettings();
        $settings->update($data);
        return $settings;
    }

    /**
     * Get formatted bank details for display
     */
    public function getFormattedBankDetails(): string
    {
        if (!$this->bank_payment_enabled) {
            return '';
        }

        $details = [];
        if ($this->bank_name) $details[] = "Bank: {$this->bank_name}";
        if ($this->account_name) $details[] = "Account: {$this->account_name}";
        if ($this->account_number) $details[] = "Number: {$this->account_number}";
        if ($this->swift_code) $details[] = "Swift: {$this->swift_code}";

        return implode(' | ', $details);
    }

    /**
     * Get formatted mobile money details for display
     */
    public function getFormattedMobileMoneyDetails(): string
    {
        if (!$this->mobile_money_enabled) {
            return '';
        }

        $details = [];
        if ($this->mobile_network) $details[] = "Network: {$this->mobile_network}";
        if ($this->payment_name) $details[] = "Name: {$this->payment_name}";
        if ($this->payment_number) $details[] = "Number: {$this->payment_number}";

        return implode(' | ', $details);
    }
}
