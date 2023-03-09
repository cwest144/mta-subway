<?php

namespace App\Services;

use App\Models\Division;
use App\Models\Station;
use App\Models\Line;
use App\Models\Feed;
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
     *  Parse an MTA API $feed and return an array of $line arrivals at $station.
     * 
     * @param Line $line 
     * @param Station $station 
     * @param array $feed 
     * @return array 
     */
    public function parseFeedForDepartures(Station $station, array $feed): array
    {
        $platforms = [$station->id . 'N', $station->id . 'S'];
        $headings = [
            $station->id . 'N' => [
                'direction' => 'N',
                'name' => $station->n_heading,
            ],
            $station->id . 'S' => [
                'direction' => 'S',
                'name' => $station->s_heading
            ]
        ];

        $result = [];

        $baseTime = new Carbon('first day of january 1970'); //the base time that MTA measures from
        $nowInSeconds = now()->diffInSeconds($baseTime);

        foreach ($feed as $item) {
            $thisTrain = Arr::get($item, 'tripUpdate.trip.routeId');

            $stopUpdates = Arr::get($item, 'tripUpdate.stopTimeUpdate');
            if ($stopUpdates === null) continue;

            foreach($stopUpdates as $stopUpdate) {
                $platform = Arr::get($stopUpdate, 'stopId');
                if (in_array($platform, $platforms)) {

                    // don't return data for end of line platforms
                    if ($headings[$platform] === "") continue;

                    $departureTime = Arr::get($stopUpdate, 'departure.time');

                    if ($departureTime === null) continue;

                    $seconds = intval($departureTime, 10) - $nowInSeconds;
                    //3570 seconds is rounded to 3600 which is an hour
                    if ($seconds < 0 || $seconds > 3569) continue;
                    
                    $timeString = null;
                    $minutes = (int) round($seconds / 60);
                    if ($seconds < 30) $timeString = 'now';
                    else if ($seconds < 60) $timeString = '<1 min';
                    else $timeString = "$minutes min";

                    $destination = '';
                    $destStationId = Arr::get($stopUpdates[count($stopUpdates)-1], 'stopId');
                    if ($destStationId !== null) {
                        $trimmed = substr($destStationId, 0, -1);
                        $destStation = Station::find($trimmed);

                        if ($destStation !== null) {
                            // don't return data for trains that are arriving at their destination
                            if ($destStation->id === $station->id) continue;

                            $destination = $destStation->name;
                        }
                    }
                    $result[$headings[$platform]['name']][] = [
                        'direction' => $headings[$platform]['direction'],
                        'key' => $platform . $thisTrain . $departureTime,
                        'train' => $thisTrain,
                        'seconds' => intval($departureTime, 10),
                        'time' => $timeString,
                        'destination' => $destination
                    ];
                }
            }
        }
        return $result;
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
     * Get the most recent feed for the given $division and return it as an array
     * 
     * @param Division $division
     * @return array
     */
    public function getFeed(Division $division): array
    {
        $feed = $division->feeds()
            ->where( 'created_at', '>', now()->subMinute())
            ->orderBy('created_at', 'DESC')
            ->first();

        if ($feed === null) {
            $feed = $this->callMta($division);
        }

        $jsonStr = file_get_contents($feed->path);
        $result = json_decode($jsonStr, true);
        return $result;
    }

    /**
     * Call the MTA API, create a new Feed with the result, and return the Feed.
     * 
     * @param Division $division
     * @return Feed
     */
    public function callMta(Division $division): Feed
    {   
        $endpoint = $division->endpoint;

        $url = config('services.mta.endpoint');
        if ($endpoint !== '') {
            $url .= "-${endpoint}";
        }

        $process = new Process([config('services.python.path'), base_path() . '/callApi.py', $url]);
        $process->run();
        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $result = json_decode($process->getOutput(), true);

        $feedContent = $result['entity'];

        $jsonString = json_encode($feedContent, JSON_PRETTY_PRINT);
        
        $nowStr = now()->format('YmdHis');
        $filePath = base_path() . "/data/{$endpoint}_{$nowStr}.json";

        $file = fopen($filePath, 'w');
        fwrite($file, $jsonString);
        fclose($file);

        $feed = Feed::create([
            'division_id' => $division->id,
            'path' => $filePath
        ]);

        return $feed;
    }
}