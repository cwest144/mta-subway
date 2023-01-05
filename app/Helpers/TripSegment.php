<?php

namespace App\Helpers;

use App\Models\Station;
use App\Models\Line;

class TripSegment
{
    public const STATION = 'station';
    public const TRAIN = 'train';

    public function __construct(public string $type, public Station|Line $value) { }
}