<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CrazyGoat\IsItDark\Calculator\MeeusCalculator;
use CrazyGoat\IsItDark\Calculator\NoaaCalculator;
use CrazyGoat\IsItDark\IsItDark;
use CrazyGoat\IsItDark\Location;

$warsaw = new Location(52.2297, 21.0122);
$dt = new DateTimeImmutable('2026-03-16 12:00:00', new DateTimeZone('Europe/Warsaw'));

$meeus = new IsItDark($warsaw, $dt, new MeeusCalculator());
$noaa  = new IsItDark($warsaw, $dt, new NoaaCalculator());

echo 'Warsaw 2026-03-16 — sunrise comparison' . PHP_EOL;
echo PHP_EOL;
echo 'Meeus : ' . $meeus->sunrise()?->format('H:i:s') . PHP_EOL;
echo 'NOAA  : ' . $noaa->sunrise()?->format('H:i:s') . PHP_EOL;
echo PHP_EOL;
echo 'Meeus sunset : ' . $meeus->sunset()?->format('H:i:s') . PHP_EOL;
echo 'NOAA  sunset : ' . $noaa->sunset()?->format('H:i:s') . PHP_EOL;
