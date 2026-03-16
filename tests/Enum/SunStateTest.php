<?php

declare(strict_types=1);

namespace CrazyGoat\IsItDark\Tests\Enum;

use CrazyGoat\IsItDark\Enum\SunState;
use PHPUnit\Framework\TestCase;

class SunStateTest extends TestCase
{
    public function testFromAltitudeDay(): void
    {
        self::assertSame(SunState::DAY, SunState::fromAltitude(10.0));
        self::assertSame(SunState::DAY, SunState::fromAltitude(0.0));
    }

    public function testFromAltitudeCivilTwilight(): void
    {
        self::assertSame(SunState::CIVIL_TWILIGHT, SunState::fromAltitude(-1.0));
        self::assertSame(SunState::CIVIL_TWILIGHT, SunState::fromAltitude(-6.0));
    }

    public function testFromAltitudeNauticalTwilight(): void
    {
        self::assertSame(SunState::NAUTICAL_TWILIGHT, SunState::fromAltitude(-6.01));
        self::assertSame(SunState::NAUTICAL_TWILIGHT, SunState::fromAltitude(-12.0));
    }

    public function testFromAltitudeAstronomicalTwilight(): void
    {
        self::assertSame(SunState::ASTRONOMICAL_TWILIGHT, SunState::fromAltitude(-12.01));
        self::assertSame(SunState::ASTRONOMICAL_TWILIGHT, SunState::fromAltitude(-18.0));
    }

    public function testFromAltitudeNight(): void
    {
        self::assertSame(SunState::NIGHT, SunState::fromAltitude(-18.01));
        self::assertSame(SunState::NIGHT, SunState::fromAltitude(-90.0));
    }

    public function testValues(): void
    {
        self::assertSame('day', SunState::DAY->value);
        self::assertSame('civil_twilight', SunState::CIVIL_TWILIGHT->value);
        self::assertSame('nautical_twilight', SunState::NAUTICAL_TWILIGHT->value);
        self::assertSame('astronomical_twilight', SunState::ASTRONOMICAL_TWILIGHT->value);
        self::assertSame('night', SunState::NIGHT->value);
    }
}
