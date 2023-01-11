<?php

namespace App\Services;

use App\Models\Station;
use App\Models\Line;
use Carbon\Carbon;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Arr;

class MtaService
{
    public function __construct(public array $feeds = []) {}

    /**
     *  Parse an MTA API $feed and return an array of $line arrivals at $station.
     * 
     * @param Line $line 
     * @param Station $station 
     * @param array $feed 
     * @return array 
     */
    public function parseFeedForArrivals(Line $line, Station $station, array $feed): array
    {
        $platforms = [$station->id . 'N',  $station->id . 'S'];

        $arrivals = [];

        foreach ($feed as $item) {
            if (Arr::get($item, 'tripUpdate.trip.routeId') === $line->id) {
                $stopUpdates = Arr::get($item, 'tripUpdate.stopTimeUpdate');
                if ($stopUpdates === null) continue;
                foreach($stopUpdates as $stopUpdate) {
                    $platform = Arr::get($stopUpdate, 'stopId');
                    if (in_array($platform, $platforms)) {
                        $departureTime = Arr::get($stopUpdate, 'departure.time');
                        if ($departureTime !== null) {
                            $arrivals[$platform][] = $departureTime;
                        }
                    }
                }
            }
        }

        return $arrivals;
    }

    /**
     * Parse the MTA API $feed to calculate the earliest arrival time at Station $end, starting at
     * time $now and Station $start and traveling on $line with $heading.
     * 
     * @param Carbon $now 
     * @param Station $start 
     * @param Line $line 
     * @param Station $end 
     * @param string $heading 
     * @param array $feed 
     * @return null|Carbon 
     */
    public function parseFeedForTrip(Carbon $now, Station $start, Line $line, Station $end, string $heading, array $feed): null|Carbon
    {
        $platformStart = $start->id . $heading;
        $platformEnd = $end->id . $heading;

        //$earliestDeparture = null; not used for now
        $earliestDestination = null;

        foreach ($feed as $item) {
            $departureTime = null;
            $destinationTime = null;

            if (Arr::get($item, 'tripUpdate.trip.routeId') === $line->id) {
                $stopUpdates = Arr::get($item, 'tripUpdate.stopTimeUpdate');
                if ($stopUpdates === null) continue;

                $departureTime = static::searchStopTimeUpdate($stopUpdates, $platformStart, $now);
                if ($departureTime === null) continue;
                $destinationTime = static::searchStopTimeUpdate($stopUpdates, $platformEnd, $departureTime);
            }

            if ($departureTime !== null && $destinationTime !== null) {
                if ($earliestDestination === null || $earliestDestination->gt($destinationTime)) {
                    //$earliestDeparture = $departureTime;
                    $earliestDestination = $destinationTime;
                }
            }            
        }

        return $earliestDestination;
    }

    /**
     * Search an array of $stopUpdates (the live-data itinerary for a train) for an arrival at $platform at a time later than $now.
     * 
     * @param array $stopUpdates 
     * @param string $platform 
     * @param Carbon $now 
     * @return null|Carbon 
     */
    public static function searchStopTimeUpdate(array $stopUpdates, string $platform, Carbon $now): null|Carbon
    {
        $baseTime = new Carbon('first day of january 1970'); //the base time that MTA measures from

        foreach ($stopUpdates as $stopUpdate) {
            $thisPlatform = Arr::get($stopUpdate, 'stopId');
            $arrivalTimeStr = Arr::get($stopUpdate, 'arrival.time') ?? Arr::get($stopUpdate, 'departure.time');
            if ($thisPlatform === null || $arrivalTimeStr === null) continue;
            $arrivalTime = $baseTime->copy()->addSeconds(intval($arrivalTimeStr, 10));
            if ($thisPlatform === $platform && $arrivalTime->gt($now)) {
                return $arrivalTime;
            }
        }
        return null;
    }

    /**
     * Call the MTA API and return the response body.
     * 
     * @param Line $line
     * @return array|bool
     */
    public function callMta(Line $line): array|bool
    {   
        $path = $this->getPath($line);

        $pathKey = ($path === '') ? 'irt' : $path;

        $existingFeed = Arr::get($this->feeds, $pathKey) ?? [];
        if ($existingFeed !== []) return $existingFeed;

        $url = config('services.mta.endpoint');
        if ($path !== '') {
            $url .= "-${path}";
        }

        $process = new Process([config('services.python.path'), base_path() . '/callApi.py', $url]);
        $process->run();
        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $result = json_decode($process->getOutput(), true);

        $this->feeds[$pathKey] = $result['entity'];

        return $result['entity'];
    }

    /**
     * Returns the path for the MTA endpoint corresponding to the given $line.
     * 
     * @param Line $line
     * @return string
     */
    private function getPath(Line $line): string
    {
        return match ($line->id) {
            'A', 'C', 'E', 'H', 'FS' => 'ace',
            'G' => 'g',
            'N', 'Q', 'R', 'W' => 'nqrw',
            '1', '2', '3', '4', '5', '6', '7', 'GS' => '',
            'B', 'D', 'F', 'M' => 'bdfm',
            'J', 'Z' => 'jz',
            'L' => 'l',
            'SIR' => 'si'
        };
    }
}