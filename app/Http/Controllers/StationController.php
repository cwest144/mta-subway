<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Services\StationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class StationController extends Controller
{
    /**
     * Handle a request to /stations
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function closestStationDepartures(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $latitude = $request->latitude;
        $longitude = $request->longitude;

        $numResults = 8;

        $stations = Station::distance($latitude, $longitude)->limit($numResults)->get();

        $closest = $stations[0];
        
        for ($i = 1; $i < $numResults; $i++) {
            $next = $stations[$i];
            if (!$closest->connectedStations->contains($next)) break;
        }

        $resultStations = [$closest, $next];

        $result = [];

        foreach ($resultStations as $station) {
            $service = App::makeWith(StationService::class, ['station' => $station]);
            $departures = $service->getDepartures();
            
            $rounded = round($station->distance * 20) / 20;

            $result[] = [
                'id' => $station->id,
                'name' => $station->name,
                'trains' => array_column($station->allLines(), 'id'),
                'distance' => $rounded,
                'platforms' => $departures,
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ], 200);
    }

    /**
     * Handle a request to /arrivals/{stationId}
     * 
     * @param string $stationId
     * @return JsonResponse
     */
    public function arrivals(string $stationId): JsonResponse
    {
        $station = Station::find($stationId);

        $service = App::makeWith(StationService::class, ['station' => $station]);

        $arrivals = $service->getArrivals();

        return response()->json([
            'status' => 'success',
            'data' => $arrivals,
        ], 200);
    }
}