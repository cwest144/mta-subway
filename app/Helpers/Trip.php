<?php

namespace App\Helpers;

use App\Models\Station;
use App\Helpers\TripSegment;

class Trip
{
    public function __construct(public Station $start, public Station $end, public array $trip = []) { }

    /**
     * Returns a formatted version of a Trip for API responses.
     * 
     * @return array
     */
    public function format(): array
    {
        return [
            'start' => $this->start->id,
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
            'end' => $this->end->id,
        ];
    }
}