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

    public function lines(): array
    {
        // get all lines that serve this station
        $lines = $this->served_by;
        $connectedStations = $this->connected_stations;
        foreach ($connectedStations as $stationId) {
            $station = Station::where('station_id', $stationId)->first();
            $lines = [...$lines, ...$station->served_by];
        }
        return $lines;
    }
}
