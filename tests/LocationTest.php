<?php

declare(strict_types=1);

namespace CrazyGoat\IsItDark\Tests;

use CrazyGoat\IsItDark\Exception\InvalidLocation;
use CrazyGoat\IsItDark\Location;
use PHPUnit\Framework\TestCase;

class LocationTest extends TestCase
{
    public function testValidLocation(): void
    {
        $location = new Location(52.2297, 21.0122);
        self::assertSame(52.2297, $location->latitude());
        self::assertSame(21.0122, $location->longitude());
    }

    public function testLatitudeTooHigh(): void
    {
        $this->expectException(InvalidLocation::class);
        new Location(91.0, 0.0);
    }

    public function testLatitudeTooLow(): void
    {
        $this->expectException(InvalidLocation::class);
        new Location(-91.0, 0.0);
    }

    public function testLongitudeTooHigh(): void
    {
        $this->expectException(InvalidLocation::class);
        new Location(0.0, 181.0);
    }

    public function testLongitudeTooLow(): void
    {
        $this->expectException(InvalidLocation::class);
        new Location(0.0, -181.0);
    }

    public function testBoundaryLatitude(): void
    {
        $loc1 = new Location(90.0, 0.0);
        $loc2 = new Location(-90.0, 0.0);
        self::assertSame(90.0, $loc1->latitude());
        self::assertSame(-90.0, $loc2->latitude());
    }

    public function testBoundaryLongitude(): void
    {
        $loc1 = new Location(0.0, 180.0);
        $loc2 = new Location(0.0, -180.0);
        self::assertSame(180.0, $loc1->longitude());
        self::assertSame(-180.0, $loc2->longitude());
    }
}
