<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use DB;

class Station extends Model
{
    use HasFactory;

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
     * Get the Lines that directly serve this Station.
     * 
     * @return BelongsToMany
     */
    public function lines(): BelongsToMany
    {
        return $this->belongsToMany(Line::class);
    }

    /**
     * Get the Stations that this Station is connected to.
     * 
     * @return BelongsToMany
     */
    public function connectedStations(): BelongsToMany
    {
        return $this->belongsToMany(Station::class, 'station_station', 'station', 'connected_station');
    }

    /**
     * Get all the Lines that serve this Station.
     * 
     * @return array
     */
    public function allLines(): array
    {
        $lines = $this->lines->all();
        foreach ($this->connectedStations as $station) {
            $lines = [...$lines, ...$station->lines];
        }
        return array_unique($lines);
    }

    /**
     *  Scope a query to order stations by distance from the given $latitude and $longitude. 
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $latitude
     * @param float $longitude
     * @return void
     */
    public function scopeDistance(Builder $query, float $latitude, float $longitude): void
    {
        $distance = 10;
        $constant = 3959; // use 6371 for km

        $haversine = "(
            $constant * acos(
                cos(radians($latitude))
                * cos(radians(latitude))
                * cos(radians(longitude) - radians($longitude))
                + sin(radians($latitude)) * sin(radians(latitude))
            )
        )";

        return $query->select('*')->selectRaw("$haversine AS distance")->orderBy('distance', 'ASC');
    }
}
