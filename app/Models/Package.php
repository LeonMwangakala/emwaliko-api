<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'amount', 'currency', 'status', 'description'];

    protected $casts = [
        'amount' => 'decimal:2'
    ];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
} 