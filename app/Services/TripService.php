<?php

namespace App\Services;

use App\Models\Station;
use App\Models\Route;
use App\Models\Line;
use App\Helpers\Trip;
use App\Helpers\TripSegment;
use App\Models\Transfer;
use App\Services\MtaService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\App;

class TripService
{
    public function __construct(public Station $start, public Station $end) {}

    /**
     * Returns a formatted version of an array of Trips to the controller.
     * 
     * @return array
     */
    public function formatTrips(array $trips): array
    {
        $formattedTrips = [];
        foreach ($trips as $trip) {
            $formattedTrips[] = $trip->format();
        }
        return $formattedTrips;
    }

    /**
     * Finds all possible trips between $this->start and $this->end and calculates the
     * current travel time for them.
     * 
     * @return array
     */
    public function plan(): array
    {
        $trips = $this->findTrips();

        foreach ($trips as $trip) {
            $this->calculateTravelTime($trip);
        }

        usort($trips, function ($a, $b) {
            if ($a->endTime === null) return 1;
            if ($b->endTime === null) return -1;
            return $a->endTime <=> $b->endTime;
        });

        $culledTrips = $this->removeSimilarTrips($trips);

        return $culledTrips;
    }

    /**
     * Find all possible trips between $this->start and $this->end.
     * 
     * @return array
     */
    public function findTrips(): array
    {
        $trips = [];

        // find all direct or single transfer trips
        foreach($this->start->allLines() as $line1) {
            foreach($this->end->allLines() as $line2) {
                if ($line1->id === $line2->id) {
                    $trips[] = new Trip(
                        [
                            new TripSegment(TripSegment::STATION, $this->start),
                            new TripSegment(TripSegment::TRAIN, $line1),
                            new TripSegment(TripSegment::STATION, $this->end),
                        ],
                    );
                }
                else {
                    $route = $line1->stops()->orderBy('stop_number')->get();
                    $commonStations = $this->transfers($route, $line2, [$this->start->id, $this->end->id]);

                    foreach ($commonStations as $pair) {
                        $transfer = $pair[0];
                        $connectedStation = $pair[1];

                        if ($transfer->id === $connectedStation->id) {
                            $thisTrip = [
                                new TripSegment(TripSegment::TRAIN, $line1),
                                new TripSegment(TripSegment::STATION, $transfer),
                                new TripSegment(TripSegment::TRAIN, $line2),
                            ];
                        }
                        else {
                            $thisTrip = [
                                new TripSegment(TripSegment::TRAIN, $line1),
                                new TripSegment(TripSegment::STATION, $transfer),
                                new TripSegment(TripSegment::STATION, $connectedStation),
                                new TripSegment(TripSegment::TRAIN, $line2),
                            ];
                        }
                        $trips[] = new Trip([
                            new TripSegment(TripSegment::STATION, $this->start),
                            ...$thisTrip,
                            new TripSegment(TripSegment::STATION, $this->end),
                        ]);
                    }
                }
            }
        }

        return $trips;
    }

    /**
     * Given a route (a collection of Stops) and a Line, returns an array of possible transfer stations,
     * each formatted as array pairs like: [station, connectedStation].
     * Exclude the stops in $exclude.
     * 
     * @param Collection $route
     * @param Line $line 
     * @param array $exclude
     * @return array
     */
    public function transfers(Collection $route, Line $line, array $exclude): array
    {
        $transfers = [];
        foreach ($route as $stop) {
            if (in_array($stop->station->id, $exclude)) continue;
            if ($stop->station->lines->contains('id', $line->id)) {
                $transfers[] = [$stop->station, $stop->station];
                continue;
            }
            foreach ($stop->station->connectedStations as $connectedStation) {
                if ($connectedStation->lines->contains('id', $line->id)) {
                    $transfers[] = [$stop->station, $connectedStation];
                }
            }
        }
        return $transfers;
    }
    
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

    /**
     * 
     */
    public function calculateTravelTime(Trip &$trip): void
    {        
        $futureTime = now();
        $previous = $trip->trip[0];
        for($i = 1; $i < count($trip->trip); $i++) {
            $current = $trip->trip[$i];

            //trip time is calculated in chunks as station -> train -> station
            if ($previous->type === TripSegment::STATION && $current->type === TripSegment::TRAIN) {
                $next = $trip->trip[$i + 1];

                $service = App::makeWith(StationService::class, ['station' => $previous->value]);
                $potentialDestinationTime = $service->calculateTravelTime($futureTime, $current->value, $next->value);
                if ($potentialDestinationTime === null) {
                    logger()->info("previous: {$previous->value->name}, current: {$current->value->id}, next: {$next->value->name}");
                    return;
                }
                $futureTime = $potentialDestinationTime;

                $previous = $next;
                $i++;
            } else { //the other way trip time is calculated is as station -> station
                $transfer = Transfer::where('station_1_id', $previous->value->id)->where('station_2_id', $current->value->id)->first();
                if ($transfer === null) {
                    $transfer = Transfer::where('station_1_id', $current->value->id)->where('station_2_id', $previous->value->id)->first();
                }
                $time = $transfer->time ?? 180;
                $futureTime->addSeconds($time);
                $previous = $current;
            }
        }

        //if ($futureTime !== null) dd($futureTime);
        $trip->endTime = $futureTime;

        //$trip->endTime = $futureTime;

        //return $trip;
    }

    public function removeSimilarTrips(array $trips): array
    {
        $culledTrips = [];

        $culledTripSignatures = [];

        foreach ($trips as $trip) {
            if (count($trip->trip) <= 3) {
                $culledTrips[] = $trip;
                continue;
            }
            $signature = $trip->trains();
            if (!in_array($signature, $culledTripSignatures)) {
                $culledTrips[] = $trip;
                $culledTripSignatures[] = $signature;
            }
        }

        return $culledTrips;
    }
}