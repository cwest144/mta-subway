<?php

use App\Http\Controllers\StationController;
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

// TODO -- accept a beginning and ending station, return possible routes between them with estimated travel times
// further: accept ranking priorities -- fastest (default) - fewest transfers -- express trains (?) -- least waiting time
Route::get('/route', [RouteController::class, 'findRoutes'])->name('findRoutes');

//sketch -----
// TripService with planTrip function calls findRoutes and then calculateTravelTime
// need a structure to store trip data in

//other possible improvements
// can 'served_by' be an array of foreign keys to the routes table?
// same with 'connected_stations'
// change foreign keys to use station_ids
// remove times from transfers table
// implement transfers as a pivot table

