<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventScanner extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'role',
        'is_active',
        'assigned_at',
        'deactivated_at'
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'deactivated_at' => 'datetime',
        'is_active' => 'boolean'
    ];

    /**
     * Get the event that this scanner is assigned to
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the user who is assigned as a scanner
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get only active scanner assignments
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get primary scanners
     */
    public function scopePrimary($query)
    {
        return $query->where('role', 'primary');
    }

    /**
     * Scope to get secondary scanners
     */
    public function scopeSecondary($query)
    {
        return $query->where('role', 'secondary');
    }

    /**
     * Deactivate the scanner assignment
     */
    public function deactivate(): void
    {
        $this->update([
            'is_active' => false,
            'deactivated_at' => now()
        ]);
    }

    /**
     * Reactivate the scanner assignment
     */
    public function reactivate(): void
    {
        $this->update([
            'is_active' => true,
            'deactivated_at' => null
        ]);
    }
}
