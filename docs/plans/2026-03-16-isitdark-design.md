# IsItDark — Design Document

**Date:** 2026-03-16  
**PHP minimum:** 8.1 (enums available)  
**Namespace:** `CrazyGoat\IsItDark`

---

## Overview

PHP library that determines whether it is dark at a given geographic location and point in time. Calculates sunrise, sunset, twilight phases, day/night duration, and handles polar day/polar night edge cases.

---

## File Structure

```
src/
├── IsItDark.php
├── Location.php
├── Calculator/
│   ├── SolarCalculatorInterface.php
│   ├── SolarData.php
│   ├── MeeusCalculator.php          # Jean Meeus (default, more accurate)
│   └── NoaaCalculator.php           # NOAA Solar Calculator (simpler)
├── Enum/
│   └── SunState.php
└── Exception/
    └── InvalidLocation.php

tests/
├── IsItDarkTest.php
├── LocationTest.php
├── Calculator/
│   ├── MeeusCalculatorTest.php
│   └── NoaaCalculatorTest.php
└── Enum/
    └── SunStateTest.php
```

---

## Architecture

### Calculator Strategy Pattern

`IsItDark` accepts an optional `SolarCalculatorInterface` as the last constructor parameter. If not provided, defaults to `MeeusCalculator`.

```php
new IsItDark($location);                                    // MeeusCalculator (default)
new IsItDark($location, $dateTime);                         // MeeusCalculator (default)
new IsItDark($location, $dateTime, new MeeusCalculator());  // explicit Meeus
new IsItDark($location, $dateTime, new NoaaCalculator());   // NOAA
```

### `SolarCalculatorInterface`

```php
interface SolarCalculatorInterface
{
    public function calculate(Location $location, DateTimeImmutable $dateTime): SolarData;
}
```

### `SolarData` — Value Object

Readonly value object holding all calculated solar data for a given location+datetime:

```php
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
        public readonly float $sunAltitude,  // sun angle at the given moment (degrees)
    ) {}
}
```

`null` values indicate polar conditions (event does not occur on that day).

### Internal Caching

`IsItDark` calls `$calculator->calculate()` lazily on first method call and caches the `SolarData` result. Subsequent method calls reuse the cached result.

---

## Classes

### `Location` — `final readonly class`

- `latitude`: float, range `-90.0..90.0`
- `longitude`: float, range `-180.0..180.0`
- Throws `InvalidLocation::invalidLatitude()` / `InvalidLocation::invalidLongitude()` on invalid input
- Methods: `latitude(): float`, `longitude(): float`

### `IsItDark` — Main Class

**Constructor:**
```php
public function __construct(
    Location $location,
    ?DateTimeInterface $dateTime = null,
    ?SolarCalculatorInterface $calculator = null,
)
```

- `$dateTime = null` → uses `new DateTimeImmutable('now')` with system timezone
- `DateTime` input → converted via `DateTimeImmutable::createFromInterface()`
- All returned `DateTimeImmutable` values use the timezone from the input datetime

**Methods:** (as per spec — see spec document for full list)

### `SunState` — Backed Enum

```php
enum SunState: string
{
    case DAY = 'day';
    case CIVIL_TWILIGHT = 'civil_twilight';
    case NAUTICAL_TWILIGHT = 'nautical_twilight';
    case ASTRONOMICAL_TWILIGHT = 'astronomical_twilight';
    case NIGHT = 'night';
}
```

Determined from `SolarData::$sunAltitude`:
- `≥ 0°` → DAY
- `< 0°` and `≥ -6°` → CIVIL_TWILIGHT
- `< -6°` and `≥ -12°` → NAUTICAL_TWILIGHT
- `< -12°` and `≥ -18°` → ASTRONOMICAL_TWILIGHT
- `< -18°` → NIGHT

---

## Algorithms

### `MeeusCalculator` (default)

Based on Jean Meeus "Astronomical Algorithms" Chapter 25. Accuracy: ±30 seconds.

Steps:
1. Calculate Julian Day Number
2. Calculate time in Julian centuries (T)
3. Calculate geometric mean longitude and anomaly of the Sun
4. Calculate Sun's equation of center
5. Calculate Sun's true longitude and apparent longitude
6. Calculate Sun's declination and right ascension
7. Calculate hour angle for sunrise/sunset (and twilight angles: -6°, -12°, -18°)
8. Convert to local time using timezone offset
9. Calculate sun altitude at the given moment using azimuth/altitude formulas

### `NoaaCalculator`

Based on NOAA Solar Calculator equations (simplified Meeus). Accuracy: ±1 minute.

---

## Edge Cases

- **Polar day** (sun never sets): `sunrise()` = null, `sunset()` = null, `isPolarDay()` = true, `dayLength()` = 86400, `nightLength()` = 0
- **Polar night** (sun never rises): `sunrise()` = null, `sunset()` = null, `isPolarNight()` = true, `dayLength()` = 0, `nightLength()` = 86400
- **No exceptions** for polar conditions — return null and let consumer check via `hasSunrise()`, `hasSunset()`, `isPolarDay()`, `isPolarNight()`

---

## Testing Strategy

- **Standard locations:** Warsaw (52.23°N, 21.01°E), London (51.51°N, -0.13°W), Equator (0°, 0°)
- **Polar:** Tromsø (69.65°N, 18.96°E) — polar day in June, polar night in December
- **Edge coordinates:** lat ±90, lon ±180
- **Invalid coordinates:** expect `InvalidLocation` exception
- **Timezone correctness:** results match input timezone
- **DST transitions:** correct behavior during daylight saving time changes
- **`toArray()`:** verify structure and value types
- **`with...()`:** verify immutability (original instance unchanged)
- **`state()`:** correct `SunState` for different times of day
- **Ground truth:** USNO (US Naval Observatory) data for known sunrise/sunset times, tolerance ±2 min

---

## Dependencies

- PHP 8.1+
- PHPUnit (dev, testing only)
- No external runtime dependencies for calculations
