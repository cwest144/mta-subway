<?php

namespace App\Services;

use App\Models\Station;
use App\Services\MtaService;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

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
        foreach ($this->station->lines() as $line) {
            $path = $this->getPath($line);
            $result = $this->mta->callMta($path);

            $arrivals[$line] = $this->mta->parseFeed($line, $this->station, $result["entity"]);
        }

        return $arrivals;
    }

    /**
     * Returns the path for the MTA endpoint corresponding to the given $line
     * 
     * @param string $line
     * @return string
     */
    private function getPath(string $line): string
    {
        return match ($line) {
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