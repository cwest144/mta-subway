<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Division extends Model
{
    use HasFactory;

    protected $fillable = [
        'endpoint'
    ];

    public $timestamps = false;

    /**
     * Get the Lines that belong to this Divison.
     * 
     * @return HasMany
     */
    public function lines(): HasMany
    {
        return $this->hasMany(Line::class);
    }
}
