<?php

namespace App\Helpers;

use App\Models\Station;

class Trip
{
    public function __construct(public Station $start, public Station $end, public array $trip = [])
    {

    }
}