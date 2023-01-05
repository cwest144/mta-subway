<?php

namespace App\Services;

use App\Models\Station;
use App\Models\Route;
use App\Helpers\Trip;
use App\Helpers\TripSegment;
use App\Services\MtaService;
use Illuminate\Database\Eloquent\Collection;

class TripService
{
    public function __construct(public Station $start, public Station $end) {}

    public function plan()
    {
        $trips = $this->findTrips();

        dd($trips);

        return $trips;

        // foreach ($trips as $trip) {
        //     calculateTravelTime($trip);

        // }
    }

    public function transfers(Collection $routeList, string $line): array
    {
        $transfers = [];
        foreach($routeList as $stop) {
            if (in_array($line, $stop->station->served_by)) {
                $transfers[] = $stop->station;
                continue;
            }
            //todo: for each connected station, check if served by line
        }
        return $transfers;
    }

    public function findTrips(): array
    {
        $trips = [];
        // find all direct or single transfer trips
        foreach($this->start->lines() as $line1) {
            foreach($this->end->lines() as $line2) {
                if ($line1 === $line2) {
                    $trips[] = new Trip(
                        $this->start,
                        $this->end,
                        [
                            new TripSegment([TripSegment::TRAIN, $line1]),
                        ],
                    );
                }
                else {
                    $route = Route::where('line', $line1)->orderBy('stop_number')->get();
                    $commonStations = $this->transfers($route, $line2);

                    dd($commonStations);
                }
            }
        }


    // linesBeg = stopDict[beg][5]
    // linesEnd = stopDict[end][5]

    // for line1 in linesBeg:

    //     for line2 in linesEnd:

    //         if line1 == line2:
    //             index = myTree.insert(end, line1)

    //         else:
    //             route1 = routeDict[line1]
    //             commonStations = transfers(route1, line2)

    //             for (station, transfer) in commonStations:

    //                 if transfer == station:
    //                     transfer = None
                             
    //                 index = myTree.insert(station, line1, transfer)
    //                 myTree.children[index].insert(end, line2)


    
    
    // allLines = ['1', '2', '3', '4', '5', '6', '7', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'J', 'L', 'M', 'N', 'Q', 'R', 'SM', 'SB', 'SR', 'W', 'Z']

    // linePairs = [('1', '2'), ('1', '3'), ('1', '7'), ('1', 'A'), ('1', 'B'), ('1', 'C'), ('1', 'D'), ('1', 'N'), ('1', 'Q'), ('1', 'R'), ('1', 'SM'), ('1', 'W'), ('2', '3'), ('2', '4'), ('2', '5'), ('2', '7'), ('2', 'A'), ('2', 'B'), ('2', 'C'), ('2', 'J'), ('2', 'N'), ('2', 'Q'), ('2', 'R'), ('2', 'SM'), ('2', 'W'), ('2', 'Z'), ('3', '4'), ('3', '5'), ('3', '7'), ('3', 'A'), ('3', 'B'), ('3', 'C'), ('3', 'J'), ('3', 'N'), ('3', 'Q'), ('3', 'R'), ('3', 'SM'), ('3', 'W'), ('3', 'Z'), ('4', '5'), ('4', '6'), ('4', '7'), ('4', 'A'), ('4', 'B'), ('4', 'C'), ('4', 'D'), ('4', 'J'), ('4', 'L'), ('4', 'N'), ('4', 'Q'), ('4', 'R'), ('4', 'SM'), ('4', 'W'), ('4', 'Z'), ('5', '6'), ('5', '7'), ('5', 'A'), ('5', 'B'), ('5', 'C'), ('5', 'J'), ('5', 'L'), ('5', 'N'), ('5', 'Q'), ('5', 'R'), ('5', 'SM'), ('5', 'W'), ('5', 'Z'), ('6', '7'), ('6', 'J'), ('6', 'L'), ('6', 'N'), ('6', 'Q'), ('6', 'R'), ('6', 'SM'), ('6', 'W'), ('6', 'Z'), ('7', 'G'), ('7', 'N'), ('7', 'Q'), ('7', 'R'), ('7', 'SM'), ('7', 'W'), ('A', 'B'), ('A', 'C'), ('A', 'D'), ('A', 'E'), ('A', 'F'), ('A', 'G'), ('A', 'J'), ('A', 'L'), ('A', 'M'), ('A', 'R'), ('A', 'Z'), ('B', 'C'), ('B', 'D'), ('B', 'E'), ('B', 'F'), ('B', 'M'), ('B', 'N'), ('B', 'Q'), ('B', 'R'), ('B', 'SB'), ('B', 'W'), ('C', 'D'), ('C', 'E'), ('C', 'F'), ('C', 'G'), ('C', 'J'), ('C', 'L'), ('C', 'M'), ('C', 'R'), ('C', 'SB'), ('C', 'Z'), ('D', 'E'), ('D', 'F'), ('D', 'M'), ('D', 'N'), ('D', 'Q'), ('D', 'R'), ('D', 'W'), ('E', 'F'), ('E', 'J'), ('E', 'M'), ('E', 'R'), ('E', 'Z'), ('F', 'G'), ('F', 'J'), ('F', 'M'), ('F', 'N'), ('F', 'Q'), ('F', 'R'), ('F', 'W'), ('F', 'Z'), ('G', 'R'), ('J', 'L'), ('J', 'M'), ('J', 'N'), ('J', 'Q'), ('J', 'R'), ('J', 'W'), ('J', 'Z'), ('L', 'M'), ('L', 'N'), ('L', 'Q'), ('L', 'R'), ('L', 'W'), ('L', 'Z'), ('M', 'N'), ('M', 'Q'), ('M', 'R'), ('M', 'W'), ('M', 'Z'), ('N', 'Q'), ('N', 'R'), ('N', 'SM'), ('N', 'W'), ('N', 'Z'), ('Q', 'R'), ('Q', 'SM'), ('Q', 'SB'), ('Q', 'W'), ('Q', 'Z'), ('R', 'SM'), ('R', 'W'), ('R', 'Z'), ('SM', 'W'), ('SR', 'A'), ('W', 'Z')]


    // linesMid = listRemove(allLines, linesBeg)
    // linesMid = listRemove(linesMid, linesEnd)

    // toDel = []

    // for line1 in linesBeg:
    //     for line2 in linesMid:
    //         for line3 in linesEnd:

    //             if pairContain(line1, line2, linePairs) and pairContain(line2, line3, linePairs):
    //                 continue

    //             else:
    //                 toDel.append(line2)
    
    // linesMid = listRemove(linesMid, toDel)

    // for line1 in linesBeg:
    //     for line2 in linesMid:
    //         for line3 in linesEnd:
    //             route1 = routeDict[line1]
    //             route2 = routeDict[line2]

    //             commonStations1 = transfers(route1, line2)
    //             commonStations2 = transfers(route2, line3)

    //             for (station1, transfer1) in commonStations1:
                    
    //                 if station1 == transfer1:
    //                     transfer1 = None

    //                 index1 = myTree.insert(station1, line1, transfer1)
    //                 childTree = myTree.children[index1]
                    
    //                 for (station2, transfer2) in commonStations2:
                        
    //                     if station2 == transfer2:
    //                         transfer2 = None

    //                     index2 = childTree.insert(station2, line2, transfer2)
    //                     grandchildTree = childTree.children[index2]
    //                     grandchildTree.insert(end, line3)

    // return

    }

    public function calculateTravelTime(Trip $trip): Trip
    {

    }
}