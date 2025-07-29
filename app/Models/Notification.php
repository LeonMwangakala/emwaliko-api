<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'guest_id',
        'message',
        'notification_type',
        'status',
        'sent_date',
        'sms_reference',
        'message_id'
    ];

    protected $casts = [
        'sent_date' => 'datetime'
    ];

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }
}
