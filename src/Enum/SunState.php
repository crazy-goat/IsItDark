<?php

declare(strict_types=1);

namespace CrazyGoat\IsItDark\Enum;

enum SunState: string
{
    case DAY = 'day';
    case CIVIL_TWILIGHT = 'civil_twilight';
    case NAUTICAL_TWILIGHT = 'nautical_twilight';
    case ASTRONOMICAL_TWILIGHT = 'astronomical_twilight';
    case NIGHT = 'night';

    public static function fromAltitude(float $altitude): self
    {
        if ($altitude >= 0.0) {
            return self::DAY;
        }
        if ($altitude >= -6.0) {
            return self::CIVIL_TWILIGHT;
        }
        if ($altitude >= -12.0) {
            return self::NAUTICAL_TWILIGHT;
        }
        if ($altitude >= -18.0) {
            return self::ASTRONOMICAL_TWILIGHT;
        }
        return self::NIGHT;
    }
}
