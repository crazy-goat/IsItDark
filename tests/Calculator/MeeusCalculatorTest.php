<?php

declare(strict_types=1);

namespace CrazyGoat\IsItDark\Tests\Calculator;

use CrazyGoat\IsItDark\Calculator\MeeusCalculator;
use CrazyGoat\IsItDark\Location;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

class MeeusCalculatorTest extends TestCase
{
    private MeeusCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new MeeusCalculator();
    }

    public function testWarsawSunriseSunset20260316(): void
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

    public function testLondonSummerSolstice(): void
    {
        $location = new Location(51.5074, -0.1278);
        $tz = new DateTimeZone('Europe/London');
        $dateTime = new DateTimeImmutable('2026-06-21 12:00:00', $tz);

        $data = $this->calculator->calculate($location, $dateTime);

        self::assertNotNull($data->sunrise);
        self::assertNotNull($data->sunset);

        $this->assertTimeWithinTolerance($data->sunrise, '2026-06-21 04:43:00', $tz, 3);
        $this->assertTimeWithinTolerance($data->sunset, '2026-06-21 21:21:00', $tz, 3);
    }

    public function testEquatorNoSeasonalVariation(): void
    {
        $location = new Location(0.0, 0.0);
        $tz = new DateTimeZone('UTC');
        $dateTime = new DateTimeImmutable('2026-03-20 12:00:00', $tz);

        $data = $this->calculator->calculate($location, $dateTime);

        self::assertNotNull($data->sunrise);
        self::assertNotNull($data->sunset);

        $this->assertTimeWithinTolerance($data->sunrise, '2026-03-20 06:04:00', $tz, 3);
        $this->assertTimeWithinTolerance($data->sunset, '2026-03-20 18:10:00', $tz, 3);
    }

    public function testPolarDayTromso(): void
    {
        $location = new Location(69.6496, 18.9560);
        $tz = new DateTimeZone('Europe/Oslo');
        $dateTime = new DateTimeImmutable('2026-06-21 12:00:00', $tz);

        $data = $this->calculator->calculate($location, $dateTime);

        self::assertNull($data->sunrise);
        self::assertNull($data->sunset);
        self::assertGreaterThan(0.0, $data->sunAltitude);
    }

    public function testPolarNightTromso(): void
    {
        $location = new Location(69.6496, 18.9560);
        $tz = new DateTimeZone('Europe/Oslo');
        $dateTime = new DateTimeImmutable('2026-12-21 12:00:00', $tz);

        $data = $this->calculator->calculate($location, $dateTime);

        self::assertNull($data->sunrise);
        self::assertNull($data->sunset);
        self::assertLessThan(0.0, $data->sunAltitude);
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

    public function testCivilTwilightTimesExist(): void
    {
        $location = new Location(52.2297, 21.0122);
        $tz = new DateTimeZone('Europe/Warsaw');
        $dateTime = new DateTimeImmutable('2026-03-16 12:00:00', $tz);

        $data = $this->calculator->calculate($location, $dateTime);

        self::assertNotNull($data->civilDawn);
        self::assertNotNull($data->civilDusk);
        self::assertNotNull($data->nauticalDawn);
        self::assertNotNull($data->nauticalDusk);
        self::assertNotNull($data->astronomicalDawn);
        self::assertNotNull($data->astronomicalDusk);
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

    private function assertTimeWithinTolerance(
        DateTimeImmutable $actual,
        string $expectedStr,
        DateTimeZone $tz,
        int $toleranceMinutes,
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
                $diff,
            ),
        );
    }
}
