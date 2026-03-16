<?php

declare(strict_types=1);

namespace CrazyGoat\IsItDark\Calculator;

use DateTimeImmutable;

final class SolarData
{
    public function __construct(
        public readonly ?DateTimeImmutable $sunrise,
        public readonly ?DateTimeImmutable $sunset,
        public readonly ?DateTimeImmutable $solarNoon,
        public readonly ?DateTimeImmutable $civilDawn,
        public readonly ?DateTimeImmutable $civilDusk,
        public readonly ?DateTimeImmutable $nauticalDawn,
        public readonly ?DateTimeImmutable $nauticalDusk,
        public readonly ?DateTimeImmutable $astronomicalDawn,
        public readonly ?DateTimeImmutable $astronomicalDusk,
        public readonly float $sunAltitude,
    ) {}
}
