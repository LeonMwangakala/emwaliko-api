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
        'name_position_x',
        'name_position_y',
        'qr_position_x',
        'qr_position_y',
        'card_class_position_x',
        'card_class_position_y',
        'show_card_class',
        'show_guest_name',
        'show_qr_code'
    ];

    protected $casts = [
        'name_position_x' => 'decimal:2',
        'name_position_y' => 'decimal:2',
        'qr_position_x' => 'decimal:2',
        'qr_position_y' => 'decimal:2',
        'card_class_position_x' => 'decimal:2',
        'card_class_position_y' => 'decimal:2',
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