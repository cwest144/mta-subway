<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Line extends Model
{
    use HasFactory;

    protected $fillable = [
        'id'
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
}
