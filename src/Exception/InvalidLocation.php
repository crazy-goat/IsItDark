<?php

declare(strict_types=1);

namespace CrazyGoat\IsItDark\Exception;

class InvalidLocation extends \InvalidArgumentException
{
    public static function invalidLatitude(float $latitude): self
    {
        return new self(sprintf('Invalid latitude value: %s. Must be between -90 and 90.', $latitude));
    }

    public static function invalidLongitude(float $longitude): self
    {
        return new self(sprintf('Invalid longitude value: %s. Must be between -180 and 180.', $longitude));
    }
}
