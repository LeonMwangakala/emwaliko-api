<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
use HasFactory, Notifiable, \Laravel\Sanctum\HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'bio',
        'user_code',
        'country',
        'region',
        'postal_code',
        'profile_picture',
        'password',
        'role_id',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Boot the model and add event listeners
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->user_code)) {
                $user->user_code = self::generateUserCode();
            }
            
            // Set default bio if not provided
            if (empty($user->bio)) {
                $user->bio = 'KadiRafiki Team Member';
            }
        });
    }

    /**
     * Generate a unique user code (KR + 4 digits + 2 letters)
     */
    public static function generateUserCode(): string
    {
        do {
            $digits = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $letters = strtoupper(Str::random(2));
            $userCode = "KR{$digits}{$letters}";
        } while (self::where('user_code', $userCode)->exists());

        return $userCode;
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get all scanner assignments for this user
     */
    public function scannerAssignments()
    {
        return $this->hasMany(EventScanner::class);
    }

    /**
     * Get active scanner assignments for this user
     */
    public function activeScannerAssignments()
    {
        return $this->hasMany(EventScanner::class)->active();
    }

    /**
     * Get events where this user is assigned as a scanner
     */
    public function scannerEvents()
    {
        return $this->belongsToMany(Event::class, 'event_scanners')
                    ->withPivot(['role', 'is_active', 'assigned_at'])
                    ->wherePivot('is_active', true);
    }

    /**
     * Check if user is a scanner (has scanner role)
     */
    public function isScanner(): bool
    {
        return $this->role && in_array(strtolower($this->role->name), ['scanner']);
    }

    /**
     * Check if user is assigned to a specific event as scanner
     */
    public function isAssignedToEvent(int $eventId): bool
    {
        return $this->activeScannerAssignments()
                    ->where('event_id', $eventId)
                    ->exists();
    }

    /**
     * Get scanner role for a specific event
     */
    public function getScannerRoleForEvent(int $eventId): ?string
    {
        $assignment = $this->activeScannerAssignments()
                           ->where('event_id', $eventId)
                           ->first();
        
        return $assignment ? $assignment->role : null;
    }
}
