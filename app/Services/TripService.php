<?php

namespace App\Services;

use App\Models\Station;
use App\Models\Line;
use App\Helpers\Trip;
use App\Helpers\TripSegment;
use App\Models\Stop;
use App\Models\Transfer;
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
        //find possible trips between $this->start and $this->end
        $trips = $this->findShortTrips();
        if (count($trips) < 3) {
            $trips = array_merge($trips, $this->findLongTrips());
        }

        //calculate travel time for all trips
        foreach ($trips as $trip) {
            $this->calculateTotalTravelTime($trip);
        }

        //sort the trips by endTime
        usort($trips, function ($a, $b) {
            if ($a->endTime === null) return 1;
            if ($b->endTime === null) return -1;
            return $a->endTime <=> $b->endTime;
        });

        //remove similar trips
        $culledTrips = $this->removeSimilarTrips($trips);

    
        return $culledTrips;
    }

    /**
     * Find all possible trips between $this->start and $this->end that require just 1 or 2 Lines.
     * 
     * @return array
     */
    public function findShortTrips(): array
    {
        $trips = [];

        // find all direct or single transfer trips
        foreach($this->start->allLines() as $line1) {

            $beginSegments = [new TripSegment(TripSegment::STATION, $this->start)];
            if (!$this->start->lines->contains($line1)) {
                foreach ($this->start->connectedStations as $connectedStation) {
                    if ($connectedStation->lines->contains($line1)) {
                        $beginSegments[] = new TripSegment(TripSegment::STATION, $connectedStation);
                        break;
                    }
                }
            }

            foreach($this->end->allLines() as $line2) {

                $endSegments = [];
                if (!$this->end->lines->contains($line2)) {
                    foreach ($this->end->connectedStations as $connectedStation) {
                        if ($connectedStation->lines->contains($line2)) {
                            $endSegments[] = new TripSegment(TripSegment::STATION, $connectedStation);
                            break;
                        }
                    }
                }
                $endSegments[] = new TripSegment(TripSegment::STATION, $this->end);

                if ($line1->id === $line2->id) {
                    $trips[] = new Trip(
                        [
                            ...$beginSegments,
                            new TripSegment(TripSegment::TRAIN, $line1),
                            ...$endSegments
                        ],
                    );
                }
                
                else {
                    $route = $line1->stops()->orderBy('stop_number')->get();

                    // this is an array of pairs [station, connectedStation] where line1 and line2 intersect
                    $commonStations = $this->transfers($route, $line2, [$this->start->id, $this->end->id]);

                    foreach ($commonStations as $pair) {
                        $transfer1 = $pair[0];
                        $transfer2 = $pair[1];

                        if ($transfer1->id === $transfer2->id) {
                            $transferSegments = [new TripSegment(TripSegment::STATION, $transfer1)];
                        } else {
                            $transferSegments = [
                                new TripSegment(TripSegment::STATION, $transfer1),
                                new TripSegment(TripSegment::STATION, $transfer2),
                            ];
                        }
                        $trips[] = new Trip([
                            ...$beginSegments,
                            new TripSegment(TripSegment::TRAIN, $line1),
                            ...$transferSegments,
                            new TripSegment(TripSegment::TRAIN, $line2),
                            ...$endSegments,
                        ]);
                    }
                }
            }
        }

        return $trips;
    }

    /**
     * Find all possible trips between $this->start and $this->end that require 3 Lines.
     * 
     * @return array 
     */
    public function findLongTrips(): array
    {
        $allLines = Line::get()->pluck('id')->toArray();

        $linesBeg = array_map(fn ($e) => $e->id, $this->start->allLines());
        $linesEnd = array_map(fn ($e) => $e->id, $this->end->allLines());
        $linesMidLetters = array_diff($allLines, array_merge($linesBeg, $linesEnd));
        $linesMid = array_map(fn ($e) => Line::find($e), $linesMidLetters);

        foreach($this->start->allLines() as $line1) {

            $beginSegments = [new TripSegment(TripSegment::STATION, $this->start)];
            if (!$this->start->lines->contains($line1)) {
                foreach ($this->start->connectedStations as $connectedStation) {
                    if ($connectedStation->lines->contains($line1)) {
                        $beginSegments[] = new TripSegment(TripSegment::STATION, $connectedStation);
                        break;
                    }
                }
            }

            $route1 = $line1->stops()->orderBy('stop_number')->get();

            foreach($linesMid as $line2) {

                // check if line1 and line2 intersect
                $commonStations1 = $this->transfers($route1, $line2, [$this->start->id]);
                if (count($commonStations1) === 0) continue;

                $route2 = $line2->stops()->orderBy('stop_number')->get();

                foreach($this->end->allLines() as $line3) {

                    //check if line2 and line3 intersect
                    $commonStations2 = $this->transfers($route2, $line3, [$this->end->id]);
                    if (count($commonStations2) === 0) continue;

                    $endSegments = [];
                    if (!$this->end->lines->contains($line3)) {
                        foreach ($this->end->connectedStations as $connectedStation) {
                            if ($connectedStation->lines->contains($line3)) {
                                $endSegments[] = new TripSegment(TripSegment::STATION, $connectedStation);
                                break;
                            }
                        }
                    }
                    $endSegments[] = new TripSegment(TripSegment::STATION, $this->end);

                    foreach ($commonStations1 as $pair1) {
                        if ($pair1[0]->id === $pair1[1]->id) {
                            $transferSegments1 = [new TripSegment(TripSegment::STATION, $pair1[0])];
                        } else {
                            $transferSegments1 = [
                                new TripSegment(TripSegment::STATION, $pair1[0]),
                                new TripSegment(TripSegment::STATION, $pair1[1])
                            ];
                        }

                        foreach ($commonStations2 as $pair2) {
                            if ($pair2[0]->id === $pair2[1]->id) {
                                $transferSegments2 = [new TripSegment(TripSegment::STATION, $pair2[0])];
                            } else {
                                $transferSegments2 = [
                                    new TripSegment(TripSegment::STATION, $pair2[0]),
                                    new TripSegment(TripSegment::STATION, $pair2[1])
                                ];
                            }

                            $trips[] = new Trip([
                                ...$beginSegments,
                                new TripSegment(TripSegment::TRAIN, $line1),
                                ...$transferSegments1,
                                new TripSegment(TripSegment::TRAIN, $line2),
                                ...$transferSegments2,
                                new TripSegment(TripSegment::TRAIN, $line3),
                                ...$endSegments,
                            ]);
                        }
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

    /**
     * Starting at now(), calculate the soonest endtime for a $trip, using realtime MTA data.
     * Save the earliest endtime in $trip->endTime.
     * 
     * @param Trip &$trip
     * @return void
     */
    public function calculateTotalTravelTime(Trip &$trip): void
    {        
        $mta = App::make(MtaService::class);

        $futureTime = now();
        $previous = $trip->trip[0];
        for($i = 1; $i < count($trip->trip); $i++) {
            $current = $trip->trip[$i];

            //trip time is calculated in chunks as station -> train -> station
            if ($previous->type === TripSegment::STATION && $current->type === TripSegment::TRAIN) {
                $next = $trip->trip[$i + 1];

                $potentialDestinationTime = $this->calculateTravelTimeForSegment($mta, $futureTime, $previous->value, $current->value, $next->value);
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

        $trip->endTime = $futureTime;
    }

    /**
     * Calculate the travel time from $this->station to $end via $line, starting at $now.
     * Returns the time of arrival or null if no routes are found.
     * 
     * @param Carbon $now
     * @param Line $line
     * @param Station $end
     * @return null|Carbon 
     */
    public function calculateTravelTimeForSegment(
        MtaService &$mta,
        Carbon $now,
        Station $start,
        Line $line,
        Station $end
    ): null|Carbon
    {
        $result = $mta->getFeed($line->division);

        $stop1 = Stop::where('line_id', $line->id)->where('station_id', $start->id)->first();
        $stop2 = Stop::where('line_id', $line->id)->where('station_id', $end->id)->first();
        if ($stop1 === null || $stop2 === null) return null;

        $heading = ($stop1->stop_number > $stop2->stop_number) ? 'N' : 'S';

        $destinationTime = $mta->parseFeedForTrip($now, $start, $line, $end, $heading, $result);

        return $destinationTime;
    }

    /**
     * Return an array that contains all unique Trips in $trips.
     * Similar trips take the exact same Lines in the same order, disregarding intermediate Station stops.
     * 
     * @param array $trips 
     * @return array 
     */
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