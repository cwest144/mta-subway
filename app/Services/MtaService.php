<?php

namespace App\Services;

use App\Models\Station;
use App\Models\Line;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Arr;

class MtaService
{
    public function parseFeed(Line $line, Station $station, array $feed)
    {
        $now = time();

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