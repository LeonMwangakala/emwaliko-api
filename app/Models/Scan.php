<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Scan extends Model
{
    use HasFactory;

    protected $fillable = [
        'guest_id',
        'quantity',
        'scan_count',
        'scanned_by',
        'scanned_date',
        'status'
    ];

    protected $casts = [
        'scanned_date' => 'datetime'
    ];

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function scannedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }
}
