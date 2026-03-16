<?php

declare(strict_types=1);

namespace CrazyGoat\IsItDark\Tests;

use CrazyGoat\IsItDark\Calculator\NoaaCalculator;
use CrazyGoat\IsItDark\Enum\SunState;
use CrazyGoat\IsItDark\IsItDark;
use CrazyGoat\IsItDark\Location;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

class IsItDarkTest extends TestCase
{
    private Location $warsaw;
    private Location $tromso;
    private DateTimeZone $warsawTz;

    protected function setUp(): void
    {
        $this->warsaw = new Location(52.2297, 21.0122);
        $this->tromso = new Location(69.6496, 18.9560);
        $this->warsawTz = new DateTimeZone('Europe/Warsaw');
    }

    public function testIsDarkAtNight(): void
    {
        $dt = new DateTimeImmutable('2026-03-16 23:00:00', $this->warsawTz);
        $isItDark = new IsItDark($this->warsaw, $dt);
        self::assertTrue($isItDark->isDark());
        self::assertFalse($isItDark->isDay());
    }

    public function testIsDayAtNoon(): void
    {
        $dt = new DateTimeImmutable('2026-03-16 12:00:00', $this->warsawTz);
        $isItDark = new IsItDark($this->warsaw, $dt);
        self::assertFalse($isItDark->isDark());
        self::assertTrue($isItDark->isDay());
    }

    public function testSunriseAndSunsetNotNull(): void
    {
        $dt = new DateTimeImmutable('2026-03-16 12:00:00', $this->warsawTz);
        $isItDark = new IsItDark($this->warsaw, $dt);
        self::assertNotNull($isItDark->sunrise());
        self::assertNotNull($isItDark->sunset());
    }

    public function testSunriseBeforeSunset(): void
    {
        $dt = new DateTimeImmutable('2026-03-16 12:00:00', $this->warsawTz);
        $isItDark = new IsItDark($this->warsaw, $dt);
        self::assertLessThan($isItDark->sunset()->getTimestamp(), $isItDark->sunrise()->getTimestamp());
    }

    public function testDayLengthPlusNightLengthEquals86400(): void
    {
        $dt = new DateTimeImmutable('2026-03-16 12:00:00', $this->warsawTz);
        $isItDark = new IsItDark($this->warsaw, $dt);
        self::assertSame(86400, $isItDark->dayLength() + $isItDark->nightLength());
    }

    public function testPolarDayTromso(): void
    {
        $tz = new DateTimeZone('Europe/Oslo');
        $dt = new DateTimeImmutable('2026-06-21 12:00:00', $tz);
        $isItDark = new IsItDark($this->tromso, $dt);

        self::assertTrue($isItDark->isPolarDay());
        self::assertFalse($isItDark->isPolarNight());
        self::assertFalse($isItDark->hasSunrise());
        self::assertFalse($isItDark->hasSunset());
        self::assertNull($isItDark->sunrise());
        self::assertNull($isItDark->sunset());
        self::assertSame(86400, $isItDark->dayLength());
        self::assertSame(0, $isItDark->nightLength());
        self::assertFalse($isItDark->isDark());
    }

    public function testPolarNightTromso(): void
    {
        $tz = new DateTimeZone('Europe/Oslo');
        $dt = new DateTimeImmutable('2026-12-21 12:00:00', $tz);
        $isItDark = new IsItDark($this->tromso, $dt);

        self::assertTrue($isItDark->isPolarNight());
        self::assertFalse($isItDark->isPolarDay());
        self::assertFalse($isItDark->hasSunrise());
        self::assertFalse($isItDark->hasSunset());
        self::assertNull($isItDark->sunrise());
        self::assertNull($isItDark->sunset());
        self::assertSame(0, $isItDark->dayLength());
        self::assertSame(86400, $isItDark->nightLength());
        self::assertTrue($isItDark->isDark());
    }

    public function testStateDay(): void
    {
        $dt = new DateTimeImmutable('2026-03-16 12:00:00', $this->warsawTz);
        $isItDark = new IsItDark($this->warsaw, $dt);
        self::assertSame(SunState::DAY, $isItDark->state());
    }

    public function testStateNight(): void
    {
        $dt = new DateTimeImmutable('2026-03-16 02:00:00', $this->warsawTz);
        $isItDark = new IsItDark($this->warsaw, $dt);
        self::assertSame(SunState::NIGHT, $isItDark->state());
    }

    public function testWithDateTimeReturnsNewInstance(): void
    {
        $dt = new DateTimeImmutable('2026-03-16 12:00:00', $this->warsawTz);
        $original = new IsItDark($this->warsaw, $dt);
        $newDt = new DateTimeImmutable('2026-06-21 12:00:00', $this->warsawTz);
        $modified = $original->withDateTime($newDt);

        self::assertNotSame($original, $modified);
        self::assertSame($dt->getTimestamp(), $original->dateTime()->getTimestamp());
        self::assertSame($newDt->getTimestamp(), $modified->dateTime()->getTimestamp());
    }

    public function testWithLocationReturnsNewInstance(): void
    {
        $dt = new DateTimeImmutable('2026-03-16 12:00:00', $this->warsawTz);
        $original = new IsItDark($this->warsaw, $dt);
        $london = new Location(51.5074, -0.1278);
        $modified = $original->withLocation($london);

        self::assertNotSame($original, $modified);
        self::assertSame($this->warsaw->latitude(), $original->location()->latitude());
        self::assertSame($london->latitude(), $modified->location()->latitude());
    }

    public function testTimezonePreservedInSunrise(): void
    {
        $tz = new DateTimeZone('America/New_York');
        $dt = new DateTimeImmutable('2026-03-16 12:00:00', $tz);
        $isItDark = new IsItDark($this->warsaw, $dt);

        self::assertNotNull($isItDark->sunrise());
        self::assertSame('America/New_York', $isItDark->sunrise()->getTimezone()->getName());
    }

    public function testDefaultDateTimeIsNow(): void
    {
        $before = new DateTimeImmutable('now');
        $isItDark = new IsItDark($this->warsaw);
        $after = new DateTimeImmutable('now');

        self::assertGreaterThanOrEqual($before->getTimestamp(), $isItDark->dateTime()->getTimestamp());
        self::assertLessThanOrEqual($after->getTimestamp(), $isItDark->dateTime()->getTimestamp());
    }

    public function testAcceptsDateTimeInterface(): void
    {
        $dt = new \DateTime('2026-03-16 12:00:00', $this->warsawTz);
        $isItDark = new IsItDark($this->warsaw, $dt);
        self::assertInstanceOf(DateTimeImmutable::class, $isItDark->dateTime());
    }

    public function testAcceptsCustomCalculator(): void
    {
        $dt = new DateTimeImmutable('2026-03-16 12:00:00', $this->warsawTz);
        $isItDark = new IsItDark($this->warsaw, $dt, new NoaaCalculator());
        self::assertNotNull($isItDark->sunrise());
    }

    public function testToArrayStructure(): void
    {
        $dt = new DateTimeImmutable('2026-03-16 12:00:00', $this->warsawTz);
        $isItDark = new IsItDark($this->warsaw, $dt);
        $array = $isItDark->toArray();

        self::assertArrayHasKey('location', $array);
        self::assertArrayHasKey('latitude', $array['location']);
        self::assertArrayHasKey('longitude', $array['location']);
        self::assertArrayHasKey('datetime', $array);
        self::assertArrayHasKey('is_dark', $array);
        self::assertArrayHasKey('is_day', $array);
        self::assertArrayHasKey('state', $array);
        self::assertArrayHasKey('sunrise', $array);
        self::assertArrayHasKey('sunset', $array);
        self::assertArrayHasKey('solar_noon', $array);
        self::assertArrayHasKey('civil_dawn', $array);
        self::assertArrayHasKey('civil_dusk', $array);
        self::assertArrayHasKey('nautical_dawn', $array);
        self::assertArrayHasKey('nautical_dusk', $array);
        self::assertArrayHasKey('astronomical_dawn', $array);
        self::assertArrayHasKey('astronomical_dusk', $array);
        self::assertArrayHasKey('day_length', $array);
        self::assertArrayHasKey('night_length', $array);
        self::assertArrayHasKey('has_sunrise', $array);
        self::assertArrayHasKey('has_sunset', $array);
        self::assertArrayHasKey('is_polar_day', $array);
        self::assertArrayHasKey('is_polar_night', $array);

        self::assertIsBool($array['is_dark']);
        self::assertIsBool($array['is_day']);
        self::assertIsString($array['state']);
        self::assertIsInt($array['day_length']);
        self::assertIsInt($array['night_length']);
        self::assertIsBool($array['has_sunrise']);
        self::assertIsBool($array['has_sunset']);
        self::assertIsBool($array['is_polar_day']);
        self::assertIsBool($array['is_polar_night']);
    }

    public function testToArrayDatetimeFormat(): void
    {
        $dt = new DateTimeImmutable('2026-03-16 12:00:00', $this->warsawTz);
        $isItDark = new IsItDark($this->warsaw, $dt);
        $array = $isItDark->toArray();

        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/', $array['datetime']);
    }

    public function testSolarNoonNotNull(): void
    {
        $dt = new DateTimeImmutable('2026-03-16 12:00:00', $this->warsawTz);
        $isItDark = new IsItDark($this->warsaw, $dt);
        self::assertNotNull($isItDark->solarNoon());
    }

    public function testTwilightTimesNotNull(): void
    {
        $dt = new DateTimeImmutable('2026-03-16 12:00:00', $this->warsawTz);
        $isItDark = new IsItDark($this->warsaw, $dt);
        self::assertNotNull($isItDark->civilDawn());
        self::assertNotNull($isItDark->civilDusk());
        self::assertNotNull($isItDark->nauticalDawn());
        self::assertNotNull($isItDark->nauticalDusk());
        self::assertNotNull($isItDark->astronomicalDawn());
        self::assertNotNull($isItDark->astronomicalDusk());
    }

    public function testCachingDoesNotRecalculate(): void
    {
        $dt = new DateTimeImmutable('2026-03-16 12:00:00', $this->warsawTz);
        $isItDark = new IsItDark($this->warsaw, $dt);

        $sunrise1 = $isItDark->sunrise();
        $sunrise2 = $isItDark->sunrise();

        self::assertSame($sunrise1, $sunrise2);
    }
}
