<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Route;
use App\Models\Station;
use App\Models\Transfer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {

        $projectRoot = base_path();

        // seed the stations table;
        $json = file_get_contents("{$projectRoot}/database/seeders/stations.json");
        $stations = json_decode($json, true);

        foreach($stations as $stationId => $data) {
            Station::create([
                'station_id' => $stationId,
                'name' => $data[0],
                'latitude' => $data[1],
                'longitude' => $data[2],
                'served_by' => $data[5],
                'connected_stations' => $data[6],
            ]);
        }

        // seed the transfers table
        $json = file_get_contents("{$projectRoot}/database/seeders/transfers.json");
        $transfers = json_decode($json, true);

        foreach ($transfers as $transfer) {
            $station1 = Station::where('station_id', $transfer[0])->first();
            $station2 = Station::where('station_id', $transfer[1])->first();
            
            Transfer::create([
                'station_1_id' => $station1->id,
                'station_2_id' => $station2->id,
                'time' => $transfer[2],
            ]);
        }

        // seed the routes table
        $json = file_get_contents("{$projectRoot}/database/seeders/routes.json");
        $routes = json_decode($json, true);

        foreach ($routes as $line => $route) {
            $stopNum = 0;
            foreach ($route as $stop) {
                $station = Station::where('station_id', $stop)->first();
                Route::create([
                    'line' => $line,
                    'station_id' => $station->id,
                    'stop_number' => $stopNum,
                ]);
                $stopNum += 1;
            }
        }
    }
}
