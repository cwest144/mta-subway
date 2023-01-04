<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Services\StationService;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\App;

class StationController extends BaseController
{
    /**
     * Handle a request to /arrivals/{stationId}
     * 
     * @param string $stationId
     * @return JsonResponse|ListingCollection
     */
    public function arrivals(string $stationId): JsonResponse
    {
        $station = Station::where('station_id', $stationId)->first();

        $service = App::makeWith(StationService::class, ['station' => $station]);

        $arrivals = $service->getArrivals();

        return response()->json([
            'status' => 'success',
            'data' => $arrivals,
        ], 200);
    }
}