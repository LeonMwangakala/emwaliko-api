<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CardClass extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'max_guests', 'status'];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }
} 