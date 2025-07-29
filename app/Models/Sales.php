<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sales extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'guest_count',
        'package_id',
        'total_sale',
        'status'
    ];

    protected $casts = [
        'total_sale' => 'decimal:2',
        'guest_count' => 'integer'
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    /**
     * Calculate total sale based on guest count and package amount
     */
    public function calculateTotalSale(): void
    {
        if ($this->package && $this->guest_count > 0) {
            $this->total_sale = $this->guest_count * $this->package->amount;
        } else {
            $this->total_sale = 0.00;
        }
    }

    /**
     * Update guest count and recalculate total sale
     */
    public function updateGuestCount(int $guestCount): void
    {
        $this->guest_count = $guestCount;
        $this->calculateTotalSale();
        $this->save();
    }

    /**
     * Update package and recalculate total sale
     */
    public function updatePackage(int $packageId): void
    {
        $this->package_id = $packageId;
        $this->calculateTotalSale();
        $this->save();
    }
}
