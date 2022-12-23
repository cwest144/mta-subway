<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Station extends Model
{
    use HasFactory;

    protected $casts = [
        'served_by' => 'array',
        'connected_stations' => 'array',
    ];

    protected $fillable = [
        'station_id',
        'name',
        'latitude',
        'longitude',
        'served_by',
        'connected_stations',
    ];
}
