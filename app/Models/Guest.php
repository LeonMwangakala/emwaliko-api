<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guest extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'title',
        'phone_number',
        'table_number',
        'card_class_id',
        'invite_code',
        'qr_code_path',
        'guest_card_path',
        'rsvp_status'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($guest) {
            if (empty($guest->invite_code)) {
                // Generate a unique invite code: KRGC + 6 unique digits
                do {
                    $code = 'KRGC' . str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                } while (self::where('invite_code', $code)->exists());
                $guest->invite_code = $code;
            }
        });

        static::created(function ($guest) {
            // Generate QR code for new guest
            $guest->generateQrCode();
        });

        static::updated(function ($guest) {
            // Generate QR code if guest doesn't have one after update
            if (empty($guest->qr_code_path)) {
                $guest->generateQrCode();
            }
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function cardClass(): BelongsTo
    {
        return $this->belongsTo(CardClass::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class);
    }

    public function generateQrCode(): void
    {
        try {
            // Check if imagick is available
            if (!extension_loaded('imagick')) {
                throw new \Exception('Imagick extension is not installed. Please install it to generate QR codes.');
            }

            $qrCode = QrCode::format('png')
                ->size(300)
                ->margin(10)
                ->errorCorrection('M')
                ->generate(route('guest.rsvp', $this->invite_code));

            $filename = 'qr_codes/' . $this->invite_code . '.png';
            Storage::disk('public')->put($filename, $qrCode);

            $this->update(['qr_code_path' => $filename, 'updated_at' => now()]);
            
        } catch (\Exception $e) {
            \Log::error("Failed to generate QR code for guest {$this->id}: " . $e->getMessage());
            throw $e;
        }
    }

    public function regenerateQrCode(): void
    {
        // Delete old QR code if exists
        if ($this->qr_code_path && Storage::disk('public')->exists($this->qr_code_path)) {
            Storage::disk('public')->delete($this->qr_code_path);
        }
        
        $this->generateQrCode();
    }

    public function getQrCodeUrlAttribute(): string
    {
        if (!$this->qr_code_path) {
            return '';
        }
        return Storage::disk('public')->url($this->qr_code_path);
    }

    public function getQrCodeBase64Attribute(): string
    {
        if (!$this->qr_code_path) {
            return '';
        }

        try {
            $filePath = Storage::disk('public')->path($this->qr_code_path);
            
            if (!file_exists($filePath)) {
                \Log::warning("QR code file not found: {$filePath}");
                return '';
            }

            // Read file and convert to base64
            $imageData = file_get_contents($filePath);
            if ($imageData === false) {
                \Log::error("Failed to read QR code file: {$filePath}");
                return '';
            }
            
            $base64Data = base64_encode($imageData);
            
            // Determine mime type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);
            
            // Create data URL
            return "data:{$mimeType};base64,{$base64Data}";
            
        } catch (\Exception $e) {
            \Log::error("Failed to get QR code base64 for guest {$this->id}: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Generate QR codes for all guests that don't have them
     */
    public static function generateMissingQrCodes(): int
    {
        $guestsWithoutQrCodes = self::whereNull('qr_code_path')->orWhere('qr_code_path', '')->get();
        $count = 0;
        
        foreach ($guestsWithoutQrCodes as $guest) {
            try {
                $guest->generateQrCode();
                $count++;
            } catch (\Exception $e) {
                \Log::error("Failed to generate QR code for guest {$guest->id}: " . $e->getMessage());
            }
        }
        
        return $count;
    }
} 