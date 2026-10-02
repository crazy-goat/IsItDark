<?php

declare(strict_types=1);

namespace CrazyGoat\IsItDark;

use CrazyGoat\IsItDark\Exception\InvalidLocation;

final class Location
{
    private readonly float $latitude;
    private readonly float $longitude;

    public function __construct(float $latitude, float $longitude)
    {
        if ($latitude < -90.0 || $latitude > 90.0) {
            throw InvalidLocation::invalidLatitude($latitude);
        }
        if ($longitude < -180.0 || $longitude > 180.0) {
            throw InvalidLocation::invalidLongitude($longitude);
        }
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    public function latitude(): float
    {
        return $this->latitude;
    }

    public function longitude(): float
    {
        return $this->longitude;
    }
}
