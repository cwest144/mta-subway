<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Stop;
use App\Models\Line;
use App\Models\LineStation;
use App\Models\StationStation;
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

        //seed the lines table;
        Line::create(['id' => 'A']);
        Line::create(['id' => 'B']);
        Line::create(['id' => 'C']);
        Line::create(['id' => 'D']);
        Line::create(['id' => 'E']);
        Line::create(['id' => 'F']);
        Line::create(['id' => 'G']);
        Line::create(['id' => 'J']);
        Line::create(['id' => 'L']);
        Line::create(['id' => 'M']);
        Line::create(['id' => 'N']);
        Line::create(['id' => 'Q']);
        Line::create(['id' => 'R']);
        Line::create(['id' => 'W']);
        Line::create(['id' => 'Z']);
        Line::create(['id' => '1']);
        Line::create(['id' => '2']);
        Line::create(['id' => '3']);
        Line::create(['id' => '4']);
        Line::create(['id' => '5']);
        Line::create(['id' => '6']);
        Line::create(['id' => '7']);
        Line::create(['id' => 'SM']);
        Line::create(['id' => 'SR']);
        Line::create(['id' => 'SB']);
        Line::create(['id' => 'SIR']);

        // seed the stations table;
        $json = file_get_contents("{$projectRoot}/database/seeders/stations.json");
        $stationData = json_decode($json, true);

        foreach($stationData as $stationId => $data) {
            $newStation = Station::create([
                'id' => $stationId,
                'name' => $data[0],
                'latitude' => $data[1],
                'longitude' => $data[2],
            ]);

            foreach($data[5] as $servedBy) {
                $line = Line::find($servedBy);
                $newStation->lines()->attach($line->id);
            }
        }
        foreach($stationData as $stationId => $data) {
            $station = Station::find($stationId);
            foreach($data[6] as $connected) {
                $connectedStation = Station::find($connected);
                $station->connectedStations()->attach($station->id);
            }

        }

        // seed the transfers table
        $json = file_get_contents("{$projectRoot}/database/seeders/transfers.json");
        $transferData = json_decode($json, true);

        foreach ($transferData as $transfer) {
            $station1 = Station::find($transfer[0]);
            $station2 = Station::find($transfer[1]);
            
            Transfer::create([
                'station_1_id' => $station1->id,
                'station_2_id' => $station2->id,
                'time' => $transfer[2],
            ]);
        }

        // seed the stops table
        $json = file_get_contents("{$projectRoot}/database/seeders/stops.json");
        $routeData = json_decode($json, true);

        foreach ($routeData as $lineId => $route) {
            $stopNum = 0;
            $line = Line::find($lineId);
            foreach ($route as $stop) {
                $station = Station::find($stop);
                Stop::create([
                    'line_id' => $line->id,
                    'station_id' => $station->id,
                    'stop_number' => $stopNum,
                ]);
                $stopNum += 1;
            }
        }
    }
}
