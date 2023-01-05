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
            'start' => 'required|string|exists:App\Models\Station,id',
            'end' => 'required|string|exists:App\Models\Station,id|different:start',
        ]);

        $start = Station::find($request->start);
        $end = Station::find($request->end);

        $service = App::make(TripService::class, ['start' => $start, 'end' => $end]);

        $trips = $service->plan();

        $formattedResult = $service->formatTrips($trips);

        return response()->json([
            'status' => 'success',
            'data' => $formattedResult,
        ], 200);        
    }
}