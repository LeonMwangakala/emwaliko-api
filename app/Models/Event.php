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
        'latitude',
        'longitude',
        'google_maps_url',
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

    public function getGoogleMapsUrlAttribute(): string
    {
        // If we have a stored Google Maps URL, use it
        if (isset($this->attributes['google_maps_url']) && $this->attributes['google_maps_url']) {
            return $this->attributes['google_maps_url'];
        }

        // If the user explicitly set it to null/empty, return empty string
        if (array_key_exists('google_maps_url', $this->attributes) && $this->attributes['google_maps_url'] === null) {
            return '';
        }

        // If we have coordinates, generate a Google Maps URL
        if (isset($this->attributes['latitude']) && isset($this->attributes['longitude']) && 
            $this->attributes['latitude'] && $this->attributes['longitude']) {
            $location = urlencode($this->event_location ?? '');
            return "https://maps.google.com/?q={$this->attributes['latitude']},{$this->attributes['longitude']}&z=15";
        }

        // If we only have location name, search for it
        if ($this->event_location) {
            $location = urlencode($this->event_location);
            return "https://maps.google.com/?q={$location}";
        }

        // Fallback to a general location search
        return "https://maps.google.com/";
    }

    public function generateGoogleMapsUrl(): string
    {
        $url = $this->getGoogleMapsUrlAttribute();
        
        // Update the stored URL if we generated one
        if ($url !== (isset($this->attributes['google_maps_url']) ? $this->attributes['google_maps_url'] : null)) {
            $this->update(['google_maps_url' => $url]);
        }
        
        return $url;
    }
} 