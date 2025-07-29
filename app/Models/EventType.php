<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'status',
        'sms_template',
        'whatsapp_template',
        'sms_invitation_template',
        'whatsapp_invitation_template',
        'sms_donation_template',
        'whatsapp_donation_template'
    ];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
} 