<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CardType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'status',
        'show_card_class',
        'show_guest_name',
        'show_qr_code'
    ];

    protected $casts = [
        'show_card_class' => 'boolean',
        'show_guest_name' => 'boolean',
        'show_qr_code' => 'boolean',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }
} 