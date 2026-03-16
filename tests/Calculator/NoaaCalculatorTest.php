<?php

declare(strict_types=1);

namespace CrazyGoat\IsItDark\Tests\Calculator;

use CrazyGoat\IsItDark\Calculator\NoaaCalculator;
use CrazyGoat\IsItDark\Location;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

class NoaaCalculatorTest extends TestCase
{
    private NoaaCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new NoaaCalculator();
    }

    public function testWarsawSunriseSunset(): void
    {
        $location = new Location(52.2297, 21.0122);
        $tz = new DateTimeZone('Europe/Warsaw');
        $dateTime = new DateTimeImmutable('2026-03-16 12:00:00', $tz);

        $data = $this->calculator->calculate($location, $dateTime);

        self::assertNotNull($data->sunrise);
        self::assertNotNull($data->sunset);

        $this->assertTimeWithinTolerance($data->sunrise, '2026-03-16 05:47:00', $tz, 2);
        $this->assertTimeWithinTolerance($data->sunset, '2026-03-16 17:41:00', $tz, 2);
    }

    public function testPolarDayTromso(): void
    {
        $location = new Location(69.6496, 18.9560);
        $tz = new DateTimeZone('Europe/Oslo');
        $dateTime = new DateTimeImmutable('2026-06-21 12:00:00', $tz);

        $data = $this->calculator->calculate($location, $dateTime);

        self::assertNull($data->sunrise);
        self::assertNull($data->sunset);
    }

    public function testPolarNightTromso(): void
    {
        $location = new Location(69.6496, 18.9560);
        $tz = new DateTimeZone('Europe/Oslo');
        $dateTime = new DateTimeImmutable('2026-12-21 12:00:00', $tz);

        $data = $this->calculator->calculate($location, $dateTime);

        self::assertNull($data->sunrise);
        self::assertNull($data->sunset);
    }

    public function testTimezonePreserved(): void
    {
        $location = new Location(52.2297, 21.0122);
        $tz = new DateTimeZone('America/New_York');
        $dateTime = new DateTimeImmutable('2026-03-16 12:00:00', $tz);

        $data = $this->calculator->calculate($location, $dateTime);

        self::assertNotNull($data->sunrise);
        self::assertSame('America/New_York', $data->sunrise->getTimezone()->getName());
    }

    public function testSunAltitudeDuringDay(): void
    {
        $location = new Location(52.2297, 21.0122);
        $tz = new DateTimeZone('Europe/Warsaw');
        $dateTime = new DateTimeImmutable('2026-06-21 12:00:00', $tz);

        $data = $this->calculator->calculate($location, $dateTime);

        self::assertGreaterThan(0.0, $data->sunAltitude);
    }

    public function testSunAltitudeDuringNight(): void
    {
        $location = new Location(52.2297, 21.0122);
        $tz = new DateTimeZone('Europe/Warsaw');
        $dateTime = new DateTimeImmutable('2026-06-21 02:00:00', $tz);

        $data = $this->calculator->calculate($location, $dateTime);

        self::assertLessThan(0.0, $data->sunAltitude);
    }

    private function assertTimeWithinTolerance(
        DateTimeImmutable $actual,
        string $expectedStr,
        DateTimeZone $tz,
        int $toleranceMinutes
    ): void {
        $expected = new DateTimeImmutable($expectedStr, $tz);
        $diff = abs($actual->getTimestamp() - $expected->getTimestamp());
        self::assertLessThanOrEqual(
            $toleranceMinutes * 60,
            $diff,
            sprintf(
                'Expected time %s to be within %d minutes of %s, but difference was %d seconds',
                $actual->format('H:i:s'),
                $toleranceMinutes,
                $expected->format('H:i:s'),
                $diff
            )
        );
    }
}
