<?php

namespace App\Services;

use App\Models\Station;
use App\Models\Line;
use App\Models\Stop;
use App\Services\MtaService;
use Carbon\Carbon;

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
            $path = $this->getPath($line);
            $result = $this->mta->callMta($path);

            $arrivals[$line->id] = $this->mta->parseFeedForArrivals($line, $this->station, $result["entity"]);
        }

        return $arrivals;
    }

    /**
     * Calculate the travel time from $this->station to $end via $line, starting at $now.
     * Returns the time of arrival or null if no routes are found.
     * 
     * @param Carbon $now
     * @param Line $line
     * @param Station $end
     * @return null|Carbon 
     */
    public function calculateTravelTime(Carbon $now, Line $line, Station $end): null|Carbon
    {
        $path = $this->getPath($line);
        $result = $this->mta->callMta($path);

        $stop1 = Stop::where('line_id', $line->id)->where('station_id', $this->station->id)->first();
        $stop2 = Stop::where('line_id', $line->id)->where('station_id', $end->id)->first();

        if ($stop1 === null || $stop2 === null) return null;

        $heading = ($stop1->stop_number > $stop2->stop_number) ? 'N' : 'S';

        $destinationTime = $this->mta->parseFeedForTrip($now, $this->station, $line, $end, $heading, $result["entity"]);

        logger()->info("returning time from station::calculateTravelTime()", [$destinationTime]);

        return $destinationTime;
    }

    /**
     * Returns the path for the MTA endpoint corresponding to the given $line
     * 
     * @param Line $line
     * @return string
     */
    private function getPath(Line $line): string
    {
        return match ($line->id) {
            'A', 'C', 'E' => 'ace',
            'G' => 'g',
            'N', 'Q', 'R', 'W' => 'nqrw',
            '1', '2', '3', '4', '5', '6', '7' => '',
            'B', 'D', 'F', 'M' => 'bdfm',
            'J', 'Z' => 'jz',
            'L' => 'l',
            'SIR' => 'si'
        };
    }
}