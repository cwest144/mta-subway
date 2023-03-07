<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Line extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'division_id',
    ];

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    /**
     * Get the Stations that this line serves.
     * 
     * @return BelongsToMany
     */
    public function stations(): BelongsToMany
    {
        return $this->belongsToMany(Station::class);
    }

    /**
     * Get the Divsion that corresponds to this line.
     * 
     * @return BelongsTo
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * Get the Stops that correspond to this line.
     * 
     * @return HasMany
     */
    public function stops(): HasMany
    {
        return $this->hasMany(Stop::class);
    }
}
