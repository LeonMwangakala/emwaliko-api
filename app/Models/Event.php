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
                // Generate a unique event code: EMEC + 6 unique digits
                do {
                    $code = 'EMEC' . str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
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
        'name_position_x',
        'name_position_y',
        'qr_position_x',
        'qr_position_y',
        'card_class_position_x',
        'card_class_position_y',
        'name_text_color',
        'card_class_text_color',
        'name_text_size',
        'card_class_text_size',
        'country_id',
        'region_id',
        'district_id',
        'status',
        'scanner_person'
    ];

    protected $casts = [
        'event_date' => 'datetime',
        'notification_date' => 'datetime',
        'name_position_x' => 'decimal:2',
        'name_position_y' => 'decimal:2',
        'qr_position_x' => 'decimal:2',
        'qr_position_y' => 'decimal:2',
        'card_class_position_x' => 'decimal:2',
        'card_class_position_y' => 'decimal:2',
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

    /**
     * Get all scanner assignments for this event
     */
    public function scanners(): HasMany
    {
        return $this->hasMany(EventScanner::class);
    }

    /**
     * Get active scanner assignments for this event
     */
    public function activeScanners(): HasMany
    {
        return $this->hasMany(EventScanner::class)->active();
    }

    /**
     * Get primary scanner for this event
     */
    public function primaryScanner(): HasMany
    {
        return $this->hasMany(EventScanner::class)->active()->primary();
    }

    /**
     * Get all scanner users for this event
     */
    public function scannerUsers()
    {
        return $this->belongsToMany(User::class, 'event_scanners')
                    ->withPivot(['role', 'is_active', 'assigned_at'])
                    ->wherePivot('is_active', true);
    }

    /**
     * Get the primary scanner user
     */
    public function primaryScannerUser()
    {
        return $this->belongsToMany(User::class, 'event_scanners')
                    ->withPivot(['role', 'is_active', 'assigned_at'])
                    ->wherePivot('is_active', true)
                    ->wherePivot('role', 'primary')
                    ->first();
    }

    /**
     * Get all secondary scanner users
     */
    public function secondaryScannerUsers()
    {
        return $this->belongsToMany(User::class, 'event_scanners')
                    ->withPivot(['role', 'is_active', 'assigned_at'])
                    ->wherePivot('is_active', true)
                    ->wherePivot('role', 'secondary');
    }

    /**
     * Check if a user is assigned as a scanner for this event
     */
    public function isUserAssignedAsScanner(int $userId): bool
    {
        return $this->scanners()
                    ->where('user_id', $userId)
                    ->where('is_active', true)
                    ->exists();
    }

    /**
     * Get scanner person (for backward compatibility)
     */
    public function getScannerPersonAttribute(): ?string
    {
        $primaryScanner = $this->primaryScannerUser();
        return $primaryScanner ? $primaryScanner->name : null;
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