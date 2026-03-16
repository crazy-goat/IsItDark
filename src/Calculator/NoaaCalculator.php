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

    private function sunApparentLong(float $T): float
    {
        $omega = 125.04 - 1934.136 * $T;
        $sunTrueLon = $this->geomMeanLongSun($T) + $this->sunEqOfCenter($T);
        return $sunTrueLon - 0.00569 - 0.00478 * sin(deg2rad($omega));
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
        $solarNoonMinutes = 720.0 - 4.0 * $lon - $eqTime;
        return $jdNoon + $solarNoonMinutes / 1440.0;
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

        $utcMinutes = fmod(($jd - floor($jd - 0.5) - 0.5) * 1440.0, 1440.0);
        $trueSolarTime = fmod($utcMinutes + $eqTime + 4.0 * $lon, 1440.0);
        $hourAngle = $trueSolarTime / 4.0 - 180.0;
        if ($hourAngle < -180.0) {
            $hourAngle += 360.0;
        }

        $latRad = deg2rad($lat);
        $declRad = deg2rad($decl);
        $haRad = deg2rad($hourAngle);

        return rad2deg(asin(
            sin($latRad) * sin($declRad) + cos($latRad) * cos($declRad) * cos($haRad)
        ));
    }
}
