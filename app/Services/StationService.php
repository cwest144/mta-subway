<?php

namespace App\Services;

use App\Models\Station;
use App\Services\MtaService;

class StationService
{
    public function __construct(public Station $station, public MtaService $mta) { }

    /**
     * Get the upcoming arrivals at this station and return as an array keyed by line and heading
     * 
     * @return array
     */
    public function getArrivals(): array
    {
        $arrivals = [];

        // foreach line, get upcoming arrivals at this station
        foreach ($this->station->allLines() as $line) {
            $result = $this->mta->callMta($line);

            $arrivals[$line->id] = $this->mta->parseFeedForArrivals($line, $this->station, $result);
        }

        return $arrivals;
    }
}