<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Services\StationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;

class StationController extends Controller
{
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