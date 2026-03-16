# IsItDark

PHP library that determines whether it is dark at a given geographic location and point in time. Calculates sunrise, sunset, twilight phases, day/night duration, and handles polar day and polar night edge cases.

## Requirements

- PHP 8.1+

## Installation

```bash
composer require crazy-goat/is-it-dark
```

## Usage

### Basic — is it dark right now?

```php
use CrazyGoat\IsItDark\IsItDark;
use CrazyGoat\IsItDark\Location;

$isItDark = new IsItDark(new Location(52.2297, 21.0122));

$isItDark->isDark();   // true/false
$isItDark->isDay();    // true/false
$isItDark->sunrise();  // DateTimeImmutable|null
$isItDark->sunset();   // DateTimeImmutable|null
```

### With specific date and timezone

```php
$isItDark = new IsItDark(
    new Location(52.2297, 21.0122),
    new DateTimeImmutable('2026-03-16 20:00:00', new DateTimeZone('Europe/Warsaw'))
);

$isItDark->isDark();
$isItDark->dayLength(); // seconds
```

### Changing context with immutable `with...()` methods

```php
$now = new IsItDark(new Location(52.2297, 21.0122));
$tomorrow = $now->withDateTime(new DateTimeImmutable('tomorrow'));
$london   = $now->withLocation(new Location(51.5074, -0.1278));
```

### Using a different solar calculator

```php
use CrazyGoat\IsItDark\Calculator\NoaaCalculator;

$isItDark = new IsItDark(
    new Location(52.2297, 21.0122),
    new DateTimeImmutable('now'),
    new NoaaCalculator()
);
```

### Serialization

```php
$isItDark->toArray();
// [
//   'location'            => ['latitude' => 52.2297, 'longitude' => 21.0122],
//   'datetime'            => '2026-03-16T20:00:00+01:00',
//   'is_dark'             => true,
//   'is_day'              => false,
//   'state'               => 'night',
//   'sunrise'             => '2026-03-16T05:47:00+01:00',
//   'sunset'              => '2026-03-16T17:41:00+01:00',
//   'solar_noon'          => '2026-03-16T11:44:00+01:00',
//   'civil_dawn'          => '...',
//   'civil_dusk'          => '...',
//   'nautical_dawn'       => '...',
//   'nautical_dusk'       => '...',
//   'astronomical_dawn'   => '...',
//   'astronomical_dusk'   => '...',
//   'day_length'          => 43448,
//   'night_length'        => 42952,
//   'has_sunrise'         => true,
//   'has_sunset'          => true,
//   'is_polar_day'        => false,
//   'is_polar_night'      => false,
// ]
```

## API Reference

### `Location`

```php
new Location(float $latitude, float $longitude)
```

Validates coordinate ranges on construction. Throws `InvalidLocation` if out of range (`-90..90` lat, `-180..180` lon).

### `IsItDark`

```php
new IsItDark(
    Location $location,
    ?DateTimeInterface $dateTime = null,        // defaults to now
    ?SolarCalculatorInterface $calculator = null // defaults to MeeusCalculator
)
```

| Method | Returns | Description |
|--------|---------|-------------|
| `isDark()` | `bool` | Sun below horizon |
| `isDay()` | `bool` | Sun above horizon |
| `state()` | `SunState` | Detailed sun state |
| `sunrise()` | `?DateTimeImmutable` | Time of sunrise (null = polar) |
| `sunset()` | `?DateTimeImmutable` | Time of sunset (null = polar) |
| `solarNoon()` | `?DateTimeImmutable` | Solar noon |
| `civilDawn()` | `?DateTimeImmutable` | Civil twilight start |
| `civilDusk()` | `?DateTimeImmutable` | Civil twilight end |
| `nauticalDawn()` | `?DateTimeImmutable` | Nautical twilight start |
| `nauticalDusk()` | `?DateTimeImmutable` | Nautical twilight end |
| `astronomicalDawn()` | `?DateTimeImmutable` | Astronomical twilight start |
| `astronomicalDusk()` | `?DateTimeImmutable` | Astronomical twilight end |
| `dayLength()` | `int` | Seconds of daylight |
| `nightLength()` | `int` | Seconds of night |
| `hasSunrise()` | `bool` | Sunrise occurs today |
| `hasSunset()` | `bool` | Sunset occurs today |
| `isPolarDay()` | `bool` | Sun never sets |
| `isPolarNight()` | `bool` | Sun never rises |
| `nextSunrise()` | `?DateTimeImmutable` | Next sunrise after current datetime |
| `nextSunset()` | `?DateTimeImmutable` | Next sunset after current datetime |
| `withDateTime(DateTimeInterface)` | `IsItDark` | New instance with different time |
| `withLocation(Location)` | `IsItDark` | New instance with different location |
| `toArray()` | `array` | All data as associative array |

### `SunState` enum

| Case | Value | Condition |
|------|-------|-----------|
| `DAY` | `day` | altitude ≥ 0° |
| `CIVIL_TWILIGHT` | `civil_twilight` | -6° ≤ altitude < 0° |
| `NAUTICAL_TWILIGHT` | `nautical_twilight` | -12° ≤ altitude < -6° |
| `ASTRONOMICAL_TWILIGHT` | `astronomical_twilight` | -18° ≤ altitude < -12° |
| `NIGHT` | `night` | altitude < -18° |

## Solar Calculators

### `MeeusCalculator` (default)

Based on Jean Meeus *Astronomical Algorithms*, Chapter 25. Accuracy: ±1 minute for sunrise/sunset.

### `NoaaCalculator`

Based on NOAA Solar Calculator equations. Accuracy: ±1–2 minutes.

Both implement `SolarCalculatorInterface`:

```php
interface SolarCalculatorInterface
{
    public function calculate(Location $location, DateTimeImmutable $dateTime): SolarData;
}
```

You can implement your own calculator and pass it to `IsItDark`.

## Polar Conditions

When the sun never rises (polar night) or never sets (polar day):

- `sunrise()` and `sunset()` return `null`
- `isPolarDay()` / `isPolarNight()` return `true`
- `dayLength()` returns `86400` (polar day) or `0` (polar night)
- `nightLength()` returns `0` (polar day) or `86400` (polar night)

## Running Tests

```bash
composer install
./vendor/bin/phpunit
```
