<?php

declare(strict_types=1);

namespace CrazyGoat\IsItDark\Calculator;

use CrazyGoat\IsItDark\Location;
use DateTimeImmutable;

interface SolarCalculatorInterface
{
    public function calculate(Location $location, DateTimeImmutable $dateTime): SolarData;
}
