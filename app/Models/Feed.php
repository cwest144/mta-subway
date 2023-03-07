<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feed extends Model
{
    use HasFactory;

    protected $fillable = [
        'division_id',
        'path',
    ];

    /**
     * Get the Lines that belong to this Divison.
     * 
     * @return BelongsTo
     */
    public function lines(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }
}
