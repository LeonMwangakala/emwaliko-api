<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'shortform'
    ];

    public function regions(): HasMany
    {
        return $this->hasMany(Region::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
