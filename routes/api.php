<?php

use App\Http\Controllers\StationController;
use App\Http\Controllers\TripController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// get the upcoming arrivals at the given stationId
// todo: accept a timestamp query param to search for arrivals after, instead of now() -- maybe not
Route::get('/arrivals/{stationId}', [StationController::class, 'arrivals'])->name('arrivals');

Route::get('/stations', [StationController::class, 'closestStationDepartures'])->name('closestStationDepartures');

// TODO -- accept a beginning and ending station, return possible routes between them with estimated travel times
// further: accept ranking priorities -- fastest (default) - fewest transfers -- express trains (?) -- least waiting time
Route::get('/trip', [TripController::class, 'index'])->name('trip.index');
