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
    public function parseFeedForArrivals(Line $line, Station $station, array $feed)
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
                        $arrivals[$platform][] = Arr::get($stopUpdate, 'departure.time');
                    }
                }
            }
        }

        return $arrivals;
    }

    /**
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

        $earliestDeparture = null;
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
                    $earliestDeparture = $departureTime;
                    $earliestDestination = $destinationTime;
                }
            }            
        }

        // dump($earliestDeparture);
        // dd($earliestDestination);

        return $earliestDestination;
    }

    /**
     * TODO
     * 
     * @param array $stopUpdates 
     * @param mixed $platform 
     * @param mixed $now 
     * @return null|Carbon 
     */
    public static function searchStopTimeUpdate(array $stopUpdates, $platform, $now): null|Carbon
    {
        $baseTime = new Carbon('first day of january 1970');


        foreach ($stopUpdates as $stopUpdate) {
            $thisPlatform = Arr::get($stopUpdate, 'stopId');
            $arrivalTimeStr = Arr::get($stopUpdate, 'arrival.time');
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
     * @param string $path
     * @return array|bool
     */
    public function callMta(string $path): array|bool
    {
        //$client = new Client();

        $url = config('services.mta.endpoint');
        if ($path !== '') {
            $url .= "-${path}";
        }

        $clientOptions = [
            'headers' => [
                'x-api-key' => config('services.mta.key'),
            ]
        ];

        $process = new Process([config('services.python.path'), base_path() . '/callApi.py', $url]);
        $process->run();
        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return json_decode($process->getOutput(), true);        
    }
}