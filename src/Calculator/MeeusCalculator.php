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

        $sunrise = $this->calculateEventTime($lat, $lon, $jdNoon, -0.8333, true, $tz);
        $sunset = $this->calculateEventTime($lat, $lon, $jdNoon, -0.8333, false, $tz);
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
        $L0 = fmod(280.46646 + 36000.76983 * $T + 0.0003032 * $T * $T, 360.0);
        $M = fmod(357.52911 + 35999.05029 * $T - 0.0001537 * $T * $T, 360.0);
        $e = 0.016708634 - 0.000042037 * $T - 0.0000001267 * $T * $T;
        $omega = 125.04 - 1934.136 * $T;

        $epsilon0 = 23.0 + 26.0 / 60.0 + 21.448 / 3600.0
            - (46.8150 / 3600.0) * $T
            - (0.00059 / 3600.0) * $T * $T
            + (0.001813 / 3600.0) * $T * $T * $T;
        $epsilon = $epsilon0 + 0.00256 * cos(deg2rad($omega));
        $epsilonRad = deg2rad($epsilon / 2.0);
        $y = tan($epsilonRad) * tan($epsilonRad);

        $L0rad = deg2rad($L0);
        $Mrad = deg2rad($M);

        $eqTime = $y * sin(2 * $L0rad)
            - 2 * $e * sin($Mrad)
            + 4 * $e * $y * sin($Mrad) * cos(2 * $L0rad)
            - 0.5 * $y * $y * sin(4 * $L0rad)
            - 1.25 * $e * $e * sin(2 * $Mrad);

        return rad2deg($eqTime) * 4.0;
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
        $zenithRad = deg2rad(90.0 - $depression);

        $cosHA = (cos($zenithRad) - sin($latRad) * sin($declRad)) / (cos($latRad) * cos($declRad));

        if ($cosHA < -1.0 || $cosHA > 1.0) {
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

        return rad2deg(asin(
            sin($latRad) * sin($declRad) + cos($latRad) * cos($declRad) * cos($HArad)
        ));
    }
}
