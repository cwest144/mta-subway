<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Services\StationService;
use App\Services\TripService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;

class TripController extends Controller
{
    /**
     * Handle a request to /trip
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'start' => 'required|string|exists:App\Models\Station,station_id',
            'end' => 'required|string|exists:App\Models\Station,station_id|different:start',
        ]);

        $start = Station::where('station_id', $request->start)->first();
        $end = Station::where('station_id', $request->end)->first();

        $service = App::make(TripService::class);

        $result = $service->plan($start, $end);

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ], 200);

        $station = Station::where('station_id', $stationId)->first();

        $service = App::makeWith(StationService::class, ['station' => $station]);

        $arrivals = $service->getArrivals();

        
    }
}