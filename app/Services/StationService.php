<?php

namespace App\Services;

use App\Models\Station;
use App\Services\MtaService;
use Arr;

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
            $result = $this->mta->getFeed($line->division);

            $arrivals[$line->id] = $this->mta->parseFeedForArrivals($line, $this->station, $result);
        }

        return $arrivals;
    }

    /**
     * Get the upcoming arrivals at this station and return as an array keyed by line and heading
     * 
     * @return array
     */
    public function getDepartures(): array
    {
        $departures = [];
        $allStations = [$this->station, ...$this->station->connectedStations];
        $byStation = [];

        // foreach line, get upcoming departures at this station
        foreach ($allStations as $station) {
            $searched = [
                'ace' => false,
                'bdfm' => false,
                'nqrw' => false,
                'g' => false,
                'l' => false,
                'jz' => false,
                'si' => false,
                'numeric' => false
            ];
            foreach ($station->lines as $line) {
                $path = $line->division->endpoint;
                if ($path === '') $path = 'numeric';
                
                if ($searched[$path]) continue;
                $result = $this->mta->getFeed($line->division);
                $byStation[] = $this->mta->parseFeedForDepartures($station, $result);
                $searched[$path] = true;
            }
        }

        foreach ($byStation as $platform) {
            foreach ($platform as $heading => $data) {

                $departures[$heading]['heading'] = $heading;
                $departures[$heading]['cardinalDirection'] = Arr::get($data, '0.direction');

                if (Arr::get($departures[$heading], 'departures') === null) {
                    $departures[$heading]['departures'] = $data;
                } else {
                    $departures[$heading]['departures'] = [...$departures[$heading]['departures'], ...$data];
                }

                // HOYT SCHERMERHORN separate G trains from AC
                if ($this->station->id === 'A42') {
                    $newPlatform = $heading === 'Brooklyn' ? 'Church Av' : 'Queens';
                    $direction = $heading === 'Brooklyn' ? 'S' : 'N';
                    $departures[$newPlatform]['heading'] = $newPlatform;
                    $departures[$newPlatform]['cardinalDirection'] = $direction;

                    $gTrains = array_filter($departures[$heading]['departures'], fn($arr) => $arr['train'] === 'G');
                    $departures[$newPlatform]['departures'] = $gTrains;

                    $otherTrains = array_filter($departures[$heading]['departures'], fn($arr) => $arr['train'] !== 'G');
                    $departures[$heading]['departures'] = $otherTrains;
                }
                // BERGEN ST separate G trains from F
                if ($this->station->id === 'F20' && $heading === 'Manhattan') {
                    $newPlatform = 'Queens';
                    $departures[$newPlatform]['heading'] = $newPlatform;
                    $departures[$newPlatform]['cardinalDirection'] = 'N';

                    $gTrains = array_filter($departures[$heading]['departures'], fn($arr) => $arr['train'] === 'G');
                    $departures[$newPlatform]['departures'] = $gTrains;

                    $otherTrains = array_filter($departures[$heading]['departures'], fn($arr) => $arr['train'] !== 'G');
                    $departures[$heading]['departures'] = $otherTrains;
                }
                // CARROLL ST separate G trains from F
                if ($this->station->id === 'F21' && $heading === 'Manhattan') {
                    $newPlatform = 'Queens';
                    $departures[$newPlatform]['heading'] = $newPlatform;
                    $departures[$newPlatform]['cardinalDirection'] = 'N';

                    $gTrains = array_filter($departures[$heading]['departures'], fn($arr) => $arr['train'] === 'G');
                    $departures[$newPlatform]['departures'] = $gTrains;

                    $otherTrains = array_filter($departures[$heading]['departures'], fn($arr) => $arr['train'] !== 'G');
                    $departures[$heading]['departures'] = $otherTrains;
                }
            }
        }

        uasort($departures, function ($a, $b) {
            if ($a['cardinalDirection'] === $b['cardinalDirection']) return 0;
            return $a['cardinalDirection'] === 'N' ? -1 : 1;
        });

        foreach ($departures as $key => $platform) {
            usort($departures[$key]['departures'], function ($a, $b) {
                return $a['seconds'] <=> $b['seconds'];
            });
        }

        return $departures;
    }
}