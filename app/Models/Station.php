<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Station extends Model
{
    use HasFactory;

    protected $casts = [
        'served_by' => 'array',
        'connected_stations' => 'array',
    ];

    protected $fillable = [
        'id',
        'name',
        'latitude',
        'longitude',
    ];

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    /**
     * Get the Lines that directly serve this station.
     * 
     * @return BelongsToMany
     */
    public function lines(): BelongsToMany
    {
        return $this->belongsToMany(Line::class);
    }

    /**
     * Get the Stations that this station is connected to.
     * 
     * @return BelongsToMany
     */
    public function connectedStations(): BelongsToMany
    {
        return $this->belongsToMany(Station::class, 'station_station', 'station', 'connected_station');
    }

    /**
     * Get all the Lines that serve this station.
     * 
     * @return 
     */
    public function allLines(): array
    {
        $lines = $this->lines->all();
        $connectedStations = $this->connectedStations;
        foreach ($this->connectedStations as $station) {
            $lines = [...$lines, ...$station->lines];
        }
        return array_unique($lines);
    }
}
