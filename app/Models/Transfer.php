<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'station_1_id',
        'station_2_id',
        'time',
    ];

    public $timestamps = false;
}
