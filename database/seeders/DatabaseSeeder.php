<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Stop;
use App\Models\Line;
use App\Models\Division;
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

        //seed the lines and divisions table
        $div = Division::create(['endpoint' => 'ace']);
        foreach(['A', 'C', 'E', 'H', 'FS'] as $lineLetter) {
            $line = Line::create(['id' => $lineLetter, 'division_id' => $div->id]);
        }

        $div = Division::create(['endpoint' => 'g']);
        foreach(['G'] as $lineLetter) {
            $line = Line::create(['id' => $lineLetter, 'division_id' => $div->id]);
        }

        $div = Division::create(['endpoint' => 'nqrw']);
        foreach(['N', 'Q', 'R', 'W'] as $lineLetter) {
            $line = Line::create(['id' => $lineLetter, 'division_id' => $div->id]);
        }

        $div = Division::create(['endpoint' => '']);
        foreach(['1', '2', '3', '4', '5', '6', '7', 'GS'] as $lineLetter) {
            $line = Line::create(['id' => $lineLetter, 'division_id' => $div->id]);
        }

        $div = Division::create(['endpoint' => 'bdfm']);
        foreach(['B', 'D', 'F', 'M'] as $lineLetter) {
            $line = Line::create(['id' => $lineLetter, 'division_id' => $div->id]);
        }

        $div = Division::create(['endpoint' => 'jz']);
        foreach(['J', 'Z'] as $lineLetter) {
            $line = Line::create(['id' => $lineLetter, 'division_id' => $div->id]);
        }

        $div = Division::create(['endpoint' => 'l']);
        foreach(['L'] as $lineLetter) {
            $line = Line::create(['id' => $lineLetter, 'division_id' => $div->id]);
        }
        
        $div = Division::create(['endpoint' => 'si']);
        foreach(['SIR'] as $lineLetter) {
            $line = Line::create(['id' => $lineLetter, 'division_id' => $div->id]);
        }

        // seed the stations table;
        $json = file_get_contents("{$projectRoot}/database/seeders/stations.json");
        $stationData = json_decode($json, true);

        foreach($stationData as $stationId => $data) {
            $newStation = Station::create([
                'id' => $stationId,
                'name' => $data[0],
                'latitude' => $data[1],
                'longitude' => $data[2],
                'n_heading' => $data[3],
                's_heading' => $data[4],
            ]);

            foreach($data[7] as $servedBy) {
                $line = Line::find($servedBy);
                $newStation->lines()->attach($line->id);
            }
        }
        foreach($stationData as $stationId => $data) {
            $station = Station::find($stationId);
            foreach($data[8] as $connected) {
                $connectedStation = Station::find($connected);
                $station->connectedStations()->attach($connectedStation->id);
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
