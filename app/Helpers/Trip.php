<?php

namespace App\Helpers;

use App\Helpers\TripSegment;
use Carbon\Carbon;

class Trip
{
    public function __construct(public array $trip = [], public Carbon|null $endTime = null) { }

    /**
     * Returns a formatted version of a Trip for API responses.
     * 
     * @return array
     */
    public function format(): array
    {
        return [
            'trip' => array_map( function($tripSegment) {
                    if ($tripSegment->type === TripSegment::STATION) {
                        return [
                            $tripSegment->type => $tripSegment->value->id,
                            'name' => $tripSegment->value->name,
                        ];
                    } else {
                        return [
                            $tripSegment->type => $tripSegment->value->id,
                        ];
                    }
                },
                $this->trip),
            'endTime' => $this->endTime,
        ];
    }

    /**
     * Returns an (ordered) array of IDs of the Lines this trip utilizes.
     * Used for checking similarity between two Trips.
     * 
     * @return array 
     */
    public function trains(): array
    {
        $unfiltered = array_map( function ($segment) {
                if ($segment->type === TripSegment::STATION) return null;
                return $segment->value->id;
            },
            $this->trip
        );

        return array_values(array_filter($unfiltered, fn ($e) => $e !== null));
    }
}