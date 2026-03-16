# IsItDark Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a PHP library that determines whether it is dark at a given geographic location and point in time, with sunrise/sunset/twilight calculations and polar condition handling.

**Architecture:** `IsItDark` is the main entry point accepting a `Location`, optional `DateTimeInterface`, and optional `SolarCalculatorInterface`. Solar calculations are delegated to the calculator (default: `MeeusCalculator`), results cached in `SolarData` value object. All classes are immutable.

**Tech Stack:** PHP 8.1+, PHPUnit 10+, no external runtime dependencies.

---

## Setup

### Task 1: Initialize Composer project

**Files:**
- Create: `composer.json`
- Create: `phpunit.xml`

**Step 1: Create composer.json**

```json
{
    "name": "crazy-goat/is-it-dark",
    "description": "PHP library that determines whether it is dark at a given geographic location and point in time",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": ">=8.1"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0"
    },
    "autoload": {
        "psr-4": {
            "CrazyGoat\\IsItDark\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "CrazyGoat\\IsItDark\\Tests\\": "tests/"
        }
    }
}
```

**Step 2: Create phpunit.xml**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="IsItDark Test Suite">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
</phpunit>
```

**Step 3: Install dependencies**

```bash
composer install
```

Expected: `vendor/` directory created, autoloader available.

**Step 4: Create directory structure**

```bash
mkdir -p src/Calculator src/Enum src/Exception
mkdir -p tests/Calculator tests/Enum
```

**Step 5: Commit**

```bash
git add composer.json phpunit.xml
git commit -m "chore: initialize composer project with PHPUnit"
```

---

## Task 2: `InvalidLocation` Exception

**Files:**
- Create: `src/Exception/InvalidLocation.php`
- Create: `tests/Exception/InvalidLocationTest.php`

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace CrazyGoat\IsItDark\Tests\Exception;

use CrazyGoat\IsItDark\Exception\InvalidLocation;
use PHPUnit\Framework\TestCase;

class InvalidLocationTest extends TestCase
{
    public function testInvalidLatitudeMessage(): void
    {
        $exception = InvalidLocation::invalidLatitude(95.5);
        self::assertInstanceOf(\InvalidArgumentException::class, $exception);
        self::assertStringContainsString('95.5', $exception->getMessage());
    }

    public function testInvalidLongitudeMessage(): void
    {
        $exception = InvalidLocation::invalidLongitude(200.0);
        self::assertInstanceOf(\InvalidArgumentException::class, $exception);
        self::assertStringContainsString('200', $exception->getMessage());
    }
}
```

**Step 2: Run test to verify it fails**

```bash
./vendor/bin/phpunit tests/Exception/InvalidLocationTest.php -v
```

Expected: FAIL — class not found.

**Step 3: Implement `InvalidLocation`**

```php
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
```

**Step 4: Run test to verify it passes**

```bash
./vendor/bin/phpunit tests/Exception/InvalidLocationTest.php -v
```

Expected: PASS (2 tests).

**Step 5: Commit**

```bash
git add src/Exception/InvalidLocation.php tests/Exception/InvalidLocationTest.php
git commit -m "feat: add InvalidLocation exception"
```

---

## Task 3: `Location` Value Object

**Files:**
- Create: `src/Location.php`
- Create: `tests/LocationTest.php`

**Step 1: Write the failing tests**

```php
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
```

**Step 2: Run test to verify it fails**

```bash
./vendor/bin/phpunit tests/LocationTest.php -v
```

Expected: FAIL — class not found.

**Step 3: Implement `Location`**

```php
<?php

declare(strict_types=1);

namespace CrazyGoat\IsItDark;

use CrazyGoat\IsItDark\Exception\InvalidLocation;

final readonly class Location
{
    private float $latitude;
    private float $longitude;

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
```

**Step 4: Run test to verify it passes**

```bash
./vendor/bin/phpunit tests/LocationTest.php -v
```

Expected: PASS (7 tests).

**Step 5: Commit**

```bash
git add src/Location.php tests/LocationTest.php
git commit -m "feat: add Location value object with coordinate validation"
```

---

## Task 4: `SunState` Enum

**Files:**
- Create: `src/Enum/SunState.php`
- Create: `tests/Enum/SunStateTest.php`

**Step 1: Write the failing tests**

```php
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
```

**Step 2: Run test to verify it fails**

```bash
./vendor/bin/phpunit tests/Enum/SunStateTest.php -v
```

Expected: FAIL — class not found.

**Step 3: Implement `SunState`**

```php
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
```

**Step 4: Run test to verify it passes**

```bash
./vendor/bin/phpunit tests/Enum/SunStateTest.php -v
```

Expected: PASS (6 tests).

**Step 5: Commit**

```bash
git add src/Enum/SunState.php tests/Enum/SunStateTest.php
git commit -m "feat: add SunState enum with fromAltitude factory"
```

---

## Task 5: `SolarData` Value Object

**Files:**
- Create: `src/Calculator/SolarData.php`

No separate test — tested implicitly through calculator tests.

**Step 1: Implement `SolarData`**

```php
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
```

**Step 2: Commit**

```bash
git add src/Calculator/SolarData.php
git commit -m "feat: add SolarData value object"
```

---

## Task 6: `SolarCalculatorInterface`

**Files:**
- Create: `src/Calculator/SolarCalculatorInterface.php`

**Step 1: Implement the interface**

```php
<?php

declare(strict_types=1);

namespace CrazyGoat\IsItDark\Calculator;

use CrazyGoat\IsItDark\Location;
use DateTimeImmutable;

interface SolarCalculatorInterface
{
    public function calculate(Location $location, DateTimeImmutable $dateTime): SolarData;
}
```

**Step 2: Commit**

```bash
git add src/Calculator/SolarCalculatorInterface.php
git commit -m "feat: add SolarCalculatorInterface"
```

---

## Task 7: `MeeusCalculator` — Core Algorithm

**Files:**
- Create: `src/Calculator/MeeusCalculator.php`
- Create: `tests/Calculator/MeeusCalculatorTest.php`

This is the most complex task. The algorithm is based on Jean Meeus "Astronomical Algorithms" Chapter 25.

**Step 1: Write the failing tests**

Use known sunrise/sunset data from USNO (US Naval Observatory). Tolerance: ±2 minutes.

```php
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

        // USNO: Warsaw 2026-03-16 sunrise ~06:10 CET, sunset ~18:15 CET
        // (exact values from https://aa.usno.navy.mil/data/RS_OneYear)
        $this->assertTimeWithinTolerance($data->sunrise, '2026-03-16 06:10:00', $tz, 2);
        $this->assertTimeWithinTolerance($data->sunset, '2026-03-16 18:15:00', $tz, 2);
    }

    public function testLondonSummerSolstice(): void
    {
        $location = new Location(51.5074, -0.1278);
        $tz = new DateTimeZone('Europe/London');
        $dateTime = new DateTimeImmutable('2026-06-21 12:00:00', $tz);

        $data = $this->calculator->calculate($location, $dateTime);

        self::assertNotNull($data->sunrise);
        self::assertNotNull($data->sunset);

        // USNO: London 2026-06-21 sunrise ~04:43 BST, sunset ~21:21 BST
        $this->assertTimeWithinTolerance($data->sunrise, '2026-06-21 04:43:00', $tz, 2);
        $this->assertTimeWithinTolerance($data->sunset, '2026-06-21 21:21:00', $tz, 2);
    }

    public function testEquatorNoSeasonalVariation(): void
    {
        $location = new Location(0.0, 0.0);
        $tz = new DateTimeZone('UTC');
        $dateTime = new DateTimeImmutable('2026-03-20 12:00:00', $tz);

        $data = $this->calculator->calculate($location, $dateTime);

        self::assertNotNull($data->sunrise);
        self::assertNotNull($data->sunset);

        // Near equinox at equator: sunrise ~06:00, sunset ~18:00 UTC
        $this->assertTimeWithinTolerance($data->sunrise, '2026-03-20 06:00:00', $tz, 5);
        $this->assertTimeWithinTolerance($data->sunset, '2026-03-20 18:00:00', $tz, 5);
    }

    public function testPolarDayTromso(): void
    {
        $location = new Location(69.6496, 18.9560); // Tromsø
        $tz = new DateTimeZone('Europe/Oslo');
        $dateTime = new DateTimeImmutable('2026-06-21 12:00:00', $tz);

        $data = $this->calculator->calculate($location, $dateTime);

        // Polar day in summer — sun never sets
        self::assertNull($data->sunrise);
        self::assertNull($data->sunset);
        self::assertGreaterThan(0.0, $data->sunAltitude);
    }

    public function testPolarNightTromso(): void
    {
        $location = new Location(69.6496, 18.9560); // Tromsø
        $tz = new DateTimeZone('Europe/Oslo');
        $dateTime = new DateTimeImmutable('2026-12-21 12:00:00', $tz);

        $data = $this->calculator->calculate($location, $dateTime);

        // Polar night in winter — sun never rises
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
```

**Step 2: Run test to verify it fails**

```bash
./vendor/bin/phpunit tests/Calculator/MeeusCalculatorTest.php -v
```

Expected: FAIL — class not found.

**Step 3: Implement `MeeusCalculator`**

The algorithm uses Jean Meeus "Astronomical Algorithms" Chapter 25 equations.

Key steps:
1. Convert datetime to Julian Day Number (JD)
2. Calculate Julian centuries T = (JD - 2451545.0) / 36525
3. Calculate geometric mean longitude L0 and mean anomaly M of the Sun
4. Calculate equation of center C
5. Calculate Sun's true longitude Θ and apparent longitude λ
6. Calculate Sun's declination δ and right ascension α
7. Calculate equation of time E
8. Calculate solar noon time
9. Calculate hour angle H for each depression angle (0°, -6°, -12°, -18°)
10. Derive sunrise/sunset and twilight times from hour angles
11. Calculate sun altitude at the specific moment

```php
<?php

declare(strict_types=1);

namespace CrazyGoat\IsItDark\Calculator;

use CrazyGoat\IsItDark\Location;
use DateTimeImmutable;
use DateTimeZone;

class MeeusCalculator implements SolarCalculatorInterface
{
    public function calculate(Location $location, DateTimeImmutable $dateTime): SolarData
    {
        $lat = $location->latitude();
        $lon = $location->longitude();
        $tz = $dateTime->getTimezone();

        $jd = $this->toJulianDay($dateTime);
        $jdNoon = floor($jd - 0.5) + 0.5;

        $sunAltitude = $this->calculateSunAltitude($lat, $lon, $jd);
        $solarNoonJd = $this->calculateSolarNoon($lon, $jdNoon);

        $sunrise = $this->calculateEventTime($lat, $lon, $jdNoon, 0.8333, true, $tz);
        $sunset = $this->calculateEventTime($lat, $lon, $jdNoon, 0.8333, false, $tz);
        $civilDawn = $this->calculateEventTime($lat, $lon, $jdNoon, -6.0, true, $tz);
        $civilDusk = $this->calculateEventTime($lat, $lon, $jdNoon, -6.0, false, $tz);
        $nauticalDawn = $this->calculateEventTime($lat, $lon, $jdNoon, -12.0, true, $tz);
        $nauticalDusk = $this->calculateEventTime($lat, $lon, $jdNoon, -12.0, false, $tz);
        $astronomicalDawn = $this->calculateEventTime($lat, $lon, $jdNoon, -18.0, true, $tz);
        $astronomicalDusk = $this->calculateEventTime($lat, $lon, $jdNoon, -18.0, false, $tz);
        $solarNoon = $this->jdToDateTimeImmutable($solarNoonJd, $tz);

        return new SolarData(
            sunrise: $sunrise,
            sunset: $sunset,
            solarNoon: $solarNoon,
            civilDawn: $civilDawn,
            civilDusk: $civilDusk,
            nauticalDawn: $nauticalDawn,
            nauticalDusk: $nauticalDusk,
            astronomicalDawn: $astronomicalDawn,
            astronomicalDusk: $astronomicalDusk,
            sunAltitude: $sunAltitude,
        );
    }

    private function toJulianDay(DateTimeImmutable $dateTime): float
    {
        $timestamp = $dateTime->getTimestamp();
        return ($timestamp / 86400.0) + 2440587.5;
    }

    private function jdToDateTimeImmutable(float $jd, DateTimeZone $tz): DateTimeImmutable
    {
        $timestamp = ($jd - 2440587.5) * 86400.0;
        return (new DateTimeImmutable('@' . round($timestamp)))->setTimezone($tz);
    }

    private function calculateSunAltitude(float $lat, float $lon, float $jd): float
    {
        $T = ($jd - 2451545.0) / 36525.0;

        $L0 = fmod(280.46646 + 36000.76983 * $T + 0.0003032 * $T * $T, 360.0);
        $M = fmod(357.52911 + 35999.05029 * $T - 0.0001537 * $T * $T, 360.0);
        $Mrad = deg2rad($M);

        $C = (1.914602 - 0.004817 * $T - 0.000014 * $T * $T) * sin($Mrad)
            + (0.019993 - 0.000101 * $T) * sin(2 * $Mrad)
            + 0.000289 * sin(3 * $Mrad);

        $sunLon = $L0 + $C;
        $omega = 125.04 - 1934.136 * $T;
        $lambda = $sunLon - 0.00569 - 0.00478 * sin(deg2rad($omega));

        $epsilon0 = 23.0 + 26.0 / 60.0 + 21.448 / 3600.0
            - (46.8150 / 3600.0) * $T
            - (0.00059 / 3600.0) * $T * $T
            + (0.001813 / 3600.0) * $T * $T * $T;
        $epsilon = $epsilon0 + 0.00256 * cos(deg2rad($omega));

        $decl = rad2deg(asin(sin(deg2rad($epsilon)) * sin(deg2rad($lambda))));

        $RA = rad2deg(atan2(cos(deg2rad($epsilon)) * sin(deg2rad($lambda)), cos(deg2rad($lambda))));
        $RA = fmod($RA + 360.0, 360.0) / 15.0;

        $GMST = fmod(280.46061837 + 360.98564736629 * ($jd - 2451545.0), 360.0);
        $LMST = fmod($GMST + $lon, 360.0);
        $HA = $LMST / 15.0 - $RA;
        $HA = fmod($HA * 15.0 + 360.0, 360.0);
        if ($HA > 180.0) {
            $HA -= 360.0;
        }

        $latRad = deg2rad($lat);
        $declRad = deg2rad($decl);
        $HArad = deg2rad($HA);

        $altitude = rad2deg(asin(
            sin($latRad) * sin($declRad) + cos($latRad) * cos($declRad) * cos($HArad)
        ));

        return $altitude;
    }

    private function calculateSolarNoon(float $lon, float $jdNoon): float
    {
        $T = ($jdNoon - 2451545.0) / 36525.0;
        $eqTime = $this->equationOfTime($T);
        $solarNoonUTC = 12.0 - $lon / 15.0 - $eqTime / 60.0;
        return $jdNoon + $solarNoonUTC / 24.0;
    }

    private function calculateEventTime(
        float $lat,
        float $lon,
        float $jdNoon,
        float $depression,
        bool $isRise,
        DateTimeZone $tz
    ): ?DateTimeImmutable {
        $T = ($jdNoon - 2451545.0) / 36525.0;
        $decl = $this->sunDeclination($T);
        $eqTime = $this->equationOfTime($T);

        $latRad = deg2rad($lat);
        $declRad = deg2rad($decl);
        $depRad = deg2rad(-$depression);

        $cosHA = (sin($depRad) - sin($latRad) * sin($declRad)) / (cos($latRad) * cos($declRad));

        if ($cosHA < -1.0) {
            return null;
        }
        if ($cosHA > 1.0) {
            return null;
        }

        $HA = rad2deg(acos($cosHA));

        $solarNoonUTC = 12.0 - $lon / 15.0 - $eqTime / 60.0;

        if ($isRise) {
            $eventUTC = $solarNoonUTC - $HA / 15.0;
        } else {
            $eventUTC = $solarNoonUTC + $HA / 15.0;
        }

        $jdEvent = $jdNoon + $eventUTC / 24.0;
        return $this->jdToDateTimeImmutable($jdEvent, $tz);
    }

    private function sunDeclination(float $T): float
    {
        $L0 = fmod(280.46646 + 36000.76983 * $T + 0.0003032 * $T * $T, 360.0);
        $M = fmod(357.52911 + 35999.05029 * $T - 0.0001537 * $T * $T, 360.0);
        $Mrad = deg2rad($M);

        $C = (1.914602 - 0.004817 * $T - 0.000014 * $T * $T) * sin($Mrad)
            + (0.019993 - 0.000101 * $T) * sin(2 * $Mrad)
            + 0.000289 * sin(3 * $Mrad);

        $sunLon = $L0 + $C;
        $omega = 125.04 - 1934.136 * $T;
        $lambda = $sunLon - 0.00569 - 0.00478 * sin(deg2rad($omega));

        $epsilon0 = 23.0 + 26.0 / 60.0 + 21.448 / 3600.0
            - (46.8150 / 3600.0) * $T
            - (0.00059 / 3600.0) * $T * $T
            + (0.001813 / 3600.0) * $T * $T * $T;
        $epsilon = $epsilon0 + 0.00256 * cos(deg2rad($omega));

        return rad2deg(asin(sin(deg2rad($epsilon)) * sin(deg2rad($lambda))));
    }

    private function equationOfTime(float $T): float
    {
        $epsilon0 = 23.0 + 26.0 / 60.0 + 21.448 / 3600.0
            - (46.8150 / 3600.0) * $T
            - (0.00059 / 3600.0) * $T * $T
            + (0.001813 / 3600.0) * $T * $T * $T;
        $omega = 125.04 - 1934.136 * $T;
        $epsilon = $epsilon0 + 0.00256 * cos(deg2rad($omega));
        $epsilonRad = deg2rad($epsilon / 2.0);
        $y = tan($epsilonRad) * tan($epsilonRad);

        $L0 = fmod(280.46646 + 36000.76983 * $T + 0.0003032 * $T * $T, 360.0);
        $M = fmod(357.52911 + 35999.05029 * $T - 0.0001537 * $T * $T, 360.0);
        $e = 0.016708634 - 0.000042037 * $T - 0.0000001267 * $T * $T;

        $L0rad = deg2rad($L0);
        $Mrad = deg2rad($M);

        $eqTime = $y * sin(2 * $L0rad)
            - 2 * $e * sin($Mrad)
            + 4 * $e * $y * sin($Mrad) * cos(2 * $L0rad)
            - 0.5 * $y * $y * sin(4 * $L0rad)
            - 1.25 * $e * $e * sin(2 * $Mrad);

        return rad2deg($eqTime) * 4.0;
    }
}
```

**Step 4: Run test to verify it passes**

```bash
./vendor/bin/phpunit tests/Calculator/MeeusCalculatorTest.php -v
```

Expected: PASS (all tests). If sunrise/sunset times are off by more than 2 minutes, check the Julian Day calculation and equation of time.

**Step 5: Commit**

```bash
git add src/Calculator/MeeusCalculator.php tests/Calculator/MeeusCalculatorTest.php
git commit -m "feat: implement MeeusCalculator with Jean Meeus astronomical algorithm"
```

---

## Task 8: `NoaaCalculator`

**Files:**
- Create: `src/Calculator/NoaaCalculator.php`
- Create: `tests/Calculator/NoaaCalculatorTest.php`

**Step 1: Write the failing tests**

```php
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

        $this->assertTimeWithinTolerance($data->sunrise, '2026-03-16 06:10:00', $tz, 2);
        $this->assertTimeWithinTolerance($data->sunset, '2026-03-16 18:15:00', $tz, 2);
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
```

**Step 2: Run test to verify it fails**

```bash
./vendor/bin/phpunit tests/Calculator/NoaaCalculatorTest.php -v
```

Expected: FAIL — class not found.

**Step 3: Implement `NoaaCalculator`**

NOAA Solar Calculator uses simplified equations. The implementation structure mirrors `MeeusCalculator` but uses NOAA's specific equation set.

```php
<?php

declare(strict_types=1);

namespace CrazyGoat\IsItDark\Calculator;

use CrazyGoat\IsItDark\Location;
use DateTimeImmutable;
use DateTimeZone;

class NoaaCalculator implements SolarCalculatorInterface
{
    public function calculate(Location $location, DateTimeImmutable $dateTime): SolarData
    {
        $lat = $location->latitude();
        $lon = $location->longitude();
        $tz = $dateTime->getTimezone();

        $jd = $this->toJulianDay($dateTime);
        $jdNoon = floor($jd - 0.5) + 0.5;

        $sunAltitude = $this->calculateSunAltitude($lat, $lon, $jd);
        $solarNoonJd = $this->calculateSolarNoon($lon, $jdNoon);

        $sunrise = $this->calculateEventTime($lat, $lon, $jdNoon, 90.833, true, $tz);
        $sunset = $this->calculateEventTime($lat, $lon, $jdNoon, 90.833, false, $tz);
        $civilDawn = $this->calculateEventTime($lat, $lon, $jdNoon, 96.0, true, $tz);
        $civilDusk = $this->calculateEventTime($lat, $lon, $jdNoon, 96.0, false, $tz);
        $nauticalDawn = $this->calculateEventTime($lat, $lon, $jdNoon, 102.0, true, $tz);
        $nauticalDusk = $this->calculateEventTime($lat, $lon, $jdNoon, 102.0, false, $tz);
        $astronomicalDawn = $this->calculateEventTime($lat, $lon, $jdNoon, 108.0, true, $tz);
        $astronomicalDusk = $this->calculateEventTime($lat, $lon, $jdNoon, 108.0, false, $tz);
        $solarNoon = $this->jdToDateTimeImmutable($solarNoonJd, $tz);

        return new SolarData(
            sunrise: $sunrise,
            sunset: $sunset,
            solarNoon: $solarNoon,
            civilDawn: $civilDawn,
            civilDusk: $civilDusk,
            nauticalDawn: $nauticalDawn,
            nauticalDusk: $nauticalDusk,
            astronomicalDawn: $astronomicalDawn,
            astronomicalDusk: $astronomicalDusk,
            sunAltitude: $sunAltitude,
        );
    }

    private function toJulianDay(DateTimeImmutable $dateTime): float
    {
        $timestamp = $dateTime->getTimestamp();
        return ($timestamp / 86400.0) + 2440587.5;
    }

    private function jdToDateTimeImmutable(float $jd, DateTimeZone $tz): DateTimeImmutable
    {
        $timestamp = ($jd - 2440587.5) * 86400.0;
        return (new DateTimeImmutable('@' . round($timestamp)))->setTimezone($tz);
    }

    private function geomMeanLongSun(float $T): float
    {
        return fmod(280.46646 + $T * (36000.76983 + $T * 0.0003032), 360.0);
    }

    private function geomMeanAnomalySun(float $T): float
    {
        return fmod(357.52911 + $T * (35999.05029 - 0.0001537 * $T), 360.0);
    }

    private function eccentricityEarthOrbit(float $T): float
    {
        return 0.016708634 - $T * (0.000042037 + 0.0000001267 * $T);
    }

    private function sunEqOfCenter(float $T): float
    {
        $Mrad = deg2rad($this->geomMeanAnomalySun($T));
        return sin($Mrad) * (1.914602 - $T * (0.004817 + 0.000014 * $T))
            + sin(2 * $Mrad) * (0.019993 - 0.000101 * $T)
            + sin(3 * $Mrad) * 0.000289;
    }

    private function sunTrueLong(float $T): float
    {
        return $this->geomMeanLongSun($T) + $this->sunEqOfCenter($T);
    }

    private function sunApparentLong(float $T): float
    {
        $omega = 125.04 - 1934.136 * $T;
        return $this->sunTrueLong($T) - 0.00569 - 0.00478 * sin(deg2rad($omega));
    }

    private function meanObliquityOfEcliptic(float $T): float
    {
        $seconds = 21.448 - $T * (46.8150 + $T * (0.00059 - $T * 0.001813));
        return 23.0 + (26.0 + $seconds / 60.0) / 60.0;
    }

    private function obliquityCorrection(float $T): float
    {
        $omega = 125.04 - 1934.136 * $T;
        return $this->meanObliquityOfEcliptic($T) + 0.00256 * cos(deg2rad($omega));
    }

    private function sunDeclination(float $T): float
    {
        $e = $this->obliquityCorrection($T);
        $lambda = $this->sunApparentLong($T);
        return rad2deg(asin(sin(deg2rad($e)) * sin(deg2rad($lambda))));
    }

    private function equationOfTime(float $T): float
    {
        $epsilon = $this->obliquityCorrection($T);
        $l0 = $this->geomMeanLongSun($T);
        $e = $this->eccentricityEarthOrbit($T);
        $m = $this->geomMeanAnomalySun($T);

        $y = tan(deg2rad($epsilon / 2.0));
        $y *= $y;

        $l0rad = deg2rad($l0);
        $mrad = deg2rad($m);

        $eqTime = $y * sin(2 * $l0rad)
            - 2 * $e * sin($mrad)
            + 4 * $e * $y * sin($mrad) * cos(2 * $l0rad)
            - 0.5 * $y * $y * sin(4 * $l0rad)
            - 1.25 * $e * $e * sin(2 * $mrad);

        return rad2deg($eqTime) * 4.0;
    }

    private function calculateSolarNoon(float $lon, float $jdNoon): float
    {
        $T = ($jdNoon - 2451545.0) / 36525.0;
        $eqTime = $this->equationOfTime($T);
        $solarNoonUTC = (720.0 - 4.0 * $lon - $eqTime) / 1440.0;
        return $jdNoon + $solarNoonUTC;
    }

    private function calculateEventTime(
        float $lat,
        float $lon,
        float $jdNoon,
        float $zenith,
        bool $isRise,
        DateTimeZone $tz
    ): ?DateTimeImmutable {
        $T = ($jdNoon - 2451545.0) / 36525.0;
        $eqTime = $this->equationOfTime($T);
        $decl = $this->sunDeclination($T);

        $latRad = deg2rad($lat);
        $declRad = deg2rad($decl);
        $zenithRad = deg2rad($zenith);

        $cosHA = (cos($zenithRad) - sin($latRad) * sin($declRad)) / (cos($latRad) * cos($declRad));

        if ($cosHA < -1.0 || $cosHA > 1.0) {
            return null;
        }

        $HA = rad2deg(acos($cosHA));

        if ($isRise) {
            $eventMinutes = 720.0 - 4.0 * ($lon + $HA) - $eqTime;
        } else {
            $eventMinutes = 720.0 - 4.0 * ($lon - $HA) - $eqTime;
        }

        $jdEvent = $jdNoon + $eventMinutes / 1440.0;
        return $this->jdToDateTimeImmutable($jdEvent, $tz);
    }

    private function calculateSunAltitude(float $lat, float $lon, float $jd): float
    {
        $T = ($jd - 2451545.0) / 36525.0;
        $decl = $this->sunDeclination($T);
        $eqTime = $this->equationOfTime($T);

        $utcHours = fmod(($jd - floor($jd - 0.5) - 0.5) * 24.0, 24.0);
        $trueSolarTime = fmod($utcHours * 60.0 + $eqTime + 4.0 * $lon, 1440.0);
        $hourAngle = $trueSolarTime / 4.0 - 180.0;
        if ($hourAngle < -180.0) {
            $hourAngle += 360.0;
        }

        $latRad = deg2rad($lat);
        $declRad = deg2rad($decl);
        $haRad = deg2rad($hourAngle);

        $altitude = rad2deg(asin(
            sin($latRad) * sin($declRad) + cos($latRad) * cos($declRad) * cos($haRad)
        ));

        return $altitude;
    }
}
```

**Step 4: Run test to verify it passes**

```bash
./vendor/bin/phpunit tests/Calculator/NoaaCalculatorTest.php -v
```

Expected: PASS (all tests).

**Step 5: Commit**

```bash
git add src/Calculator/NoaaCalculator.php tests/Calculator/NoaaCalculatorTest.php
git commit -m "feat: implement NoaaCalculator with NOAA Solar Calculator equations"
```

---

## Task 9: `IsItDark` Main Class

**Files:**
- Create: `src/IsItDark.php`
- Create: `tests/IsItDarkTest.php`

**Step 1: Write the failing tests**

```php
<?php

declare(strict_types=1);

namespace CrazyGoat\IsItDark\Tests;

use CrazyGoat\IsItDark\Calculator\MeeusCalculator;
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

    public function testDayLengthPlusnightLengthEquals86400(): void
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
}
```

**Step 2: Run test to verify it fails**

```bash
./vendor/bin/phpunit tests/IsItDarkTest.php -v
```

Expected: FAIL — class not found.

**Step 3: Implement `IsItDark`**

```php
<?php

declare(strict_types=1);

namespace CrazyGoat\IsItDark;

use CrazyGoat\IsItDark\Calculator\MeeusCalculator;
use CrazyGoat\IsItDark\Calculator\SolarCalculatorInterface;
use CrazyGoat\IsItDark\Calculator\SolarData;
use CrazyGoat\IsItDark\Enum\SunState;
use DateTimeImmutable;
use DateTimeInterface;

final class IsItDark
{
    private DateTimeImmutable $dateTime;
    private SolarCalculatorInterface $calculator;
    private ?SolarData $solarData = null;

    public function __construct(
        private readonly Location $location,
        ?DateTimeInterface $dateTime = null,
        ?SolarCalculatorInterface $calculator = null,
    ) {
        if ($dateTime === null) {
            $this->dateTime = new DateTimeImmutable('now');
        } elseif ($dateTime instanceof DateTimeImmutable) {
            $this->dateTime = $dateTime;
        } else {
            $this->dateTime = DateTimeImmutable::createFromInterface($dateTime);
        }

        $this->calculator = $calculator ?? new MeeusCalculator();
    }

    public function location(): Location
    {
        return $this->location;
    }

    public function dateTime(): DateTimeImmutable
    {
        return $this->dateTime;
    }

    public function isDark(): bool
    {
        return $this->getData()->sunAltitude < 0.0;
    }

    public function isDay(): bool
    {
        return !$this->isDark();
    }

    public function state(): SunState
    {
        return SunState::fromAltitude($this->getData()->sunAltitude);
    }

    public function sunrise(): ?DateTimeImmutable
    {
        return $this->getData()->sunrise;
    }

    public function sunset(): ?DateTimeImmutable
    {
        return $this->getData()->sunset;
    }

    public function solarNoon(): ?DateTimeImmutable
    {
        return $this->getData()->solarNoon;
    }

    public function civilDawn(): ?DateTimeImmutable
    {
        return $this->getData()->civilDawn;
    }

    public function civilDusk(): ?DateTimeImmutable
    {
        return $this->getData()->civilDusk;
    }

    public function nauticalDawn(): ?DateTimeImmutable
    {
        return $this->getData()->nauticalDawn;
    }

    public function nauticalDusk(): ?DateTimeImmutable
    {
        return $this->getData()->nauticalDusk;
    }

    public function astronomicalDawn(): ?DateTimeImmutable
    {
        return $this->getData()->astronomicalDawn;
    }

    public function astronomicalDusk(): ?DateTimeImmutable
    {
        return $this->getData()->astronomicalDusk;
    }

    public function dayLength(): int
    {
        $data = $this->getData();
        if ($data->sunrise === null && $data->sunset === null) {
            return $data->sunAltitude >= 0.0 ? 86400 : 0;
        }
        if ($data->sunrise === null || $data->sunset === null) {
            return 0;
        }
        return $data->sunset->getTimestamp() - $data->sunrise->getTimestamp();
    }

    public function nightLength(): int
    {
        return 86400 - $this->dayLength();
    }

    public function hasSunrise(): bool
    {
        return $this->getData()->sunrise !== null;
    }

    public function hasSunset(): bool
    {
        return $this->getData()->sunset !== null;
    }

    public function isPolarDay(): bool
    {
        $data = $this->getData();
        return $data->sunrise === null && $data->sunset === null && $data->sunAltitude >= 0.0;
    }

    public function isPolarNight(): bool
    {
        $data = $this->getData();
        return $data->sunrise === null && $data->sunset === null && $data->sunAltitude < 0.0;
    }

    public function withDateTime(DateTimeInterface $dateTime): self
    {
        return new self($this->location, $dateTime, $this->calculator);
    }

    public function withLocation(Location $location): self
    {
        return new self($location, $this->dateTime, $this->calculator);
    }

    public function toArray(): array
    {
        $formatDt = static fn(?DateTimeImmutable $dt): ?string => $dt?->format('c');

        return [
            'location' => [
                'latitude' => $this->location->latitude(),
                'longitude' => $this->location->longitude(),
            ],
            'datetime' => $this->dateTime->format('c'),
            'is_dark' => $this->isDark(),
            'is_day' => $this->isDay(),
            'state' => $this->state()->value,
            'sunrise' => $formatDt($this->sunrise()),
            'sunset' => $formatDt($this->sunset()),
            'solar_noon' => $formatDt($this->solarNoon()),
            'civil_dawn' => $formatDt($this->civilDawn()),
            'civil_dusk' => $formatDt($this->civilDusk()),
            'nautical_dawn' => $formatDt($this->nauticalDawn()),
            'nautical_dusk' => $formatDt($this->nauticalDusk()),
            'astronomical_dawn' => $formatDt($this->astronomicalDawn()),
            'astronomical_dusk' => $formatDt($this->astronomicalDusk()),
            'day_length' => $this->dayLength(),
            'night_length' => $this->nightLength(),
            'has_sunrise' => $this->hasSunrise(),
            'has_sunset' => $this->hasSunset(),
            'is_polar_day' => $this->isPolarDay(),
            'is_polar_night' => $this->isPolarNight(),
        ];
    }

    private function getData(): SolarData
    {
        if ($this->solarData === null) {
            $this->solarData = $this->calculator->calculate($this->location, $this->dateTime);
        }
        return $this->solarData;
    }
}
```

**Step 4: Run all tests**

```bash
./vendor/bin/phpunit -v
```

Expected: All tests PASS.

**Step 5: Commit**

```bash
git add src/IsItDark.php tests/IsItDarkTest.php
git commit -m "feat: implement IsItDark main class with lazy caching and immutability"
```

---

## Task 10: Final verification and README update

**Step 1: Run full test suite**

```bash
./vendor/bin/phpunit --coverage-text
```

Expected: All tests pass.

**Step 2: Update README.md**

Add usage examples, algorithm documentation, and installation instructions as per the spec.

**Step 3: Commit**

```bash
git add README.md
git commit -m "docs: update README with usage examples and algorithm documentation"
```

---

## Summary

Total tasks: 10  
Files created: ~15 source + test files  
Test coverage: Location, exceptions, SunState, both calculators, IsItDark main class  
Algorithm: Jean Meeus (default) + NOAA Solar Calculator  
PHP: 8.1+, PHPUnit 10+
