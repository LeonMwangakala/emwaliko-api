<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Event extends Model
{
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($event) {
            if (empty($event->event_code)) {
                // Generate a unique event code: KREC + 6 unique digits
                do {
                    $code = 'KREC' . str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                } while (self::where('event_code', $code)->exists());
                $event->event_code = $code;
            }
        });
    }

    protected $fillable = [
        'event_code',
        'event_name',
        'customer_id',
        'event_type_id',
        'card_type_id',
        'package_id',
        'event_location',
        'event_date',
        'notification_date',
        'card_design_path',
        'country_id',
        'region_id',
        'district_id',
        'status',
        'scanner_person'
    ];

    protected $casts = [
        'event_date' => 'datetime',
        'notification_date' => 'datetime'
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function eventType(): BelongsTo
    {
        return $this->belongsTo(EventType::class);
    }

    public function cardType(): BelongsTo
    {
        return $this->belongsTo(CardType::class);
    }



    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }

    public function notifications(): HasManyThrough
    {
        return $this->hasManyThrough(Notification::class, Guest::class);
    }

    public function scans(): HasManyThrough
    {
        return $this->hasManyThrough(Scan::class, Guest::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sales::class);
    }

    public function getCardDesignBase64Attribute(): string
    {
        if (!$this->card_design_path) {
            return '';
        }

        try {
            $filePath = storage_path('app/public/' . $this->card_design_path);
            
            if (!file_exists($filePath)) {
                return '';
            }

            // Read file and convert to base64
            $imageData = file_get_contents($filePath);
            $base64Data = base64_encode($imageData);
            
            // Determine mime type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);
            
            // Create data URL
            return "data:{$mimeType};base64,{$base64Data}";
            
        } catch (\Exception $e) {
            \Log::error("Failed to get card design base64 for event {$this->id}: " . $e->getMessage());
            return '';
        }
    }
} 