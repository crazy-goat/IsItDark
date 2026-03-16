<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CrazyGoat\IsItDark\IsItDark;
use CrazyGoat\IsItDark\Location;

$warsaw = new Location(52.2297, 21.0122);
$dt = new DateTimeImmutable('2026-06-21 00:00:00', new DateTimeZone('Europe/Warsaw'));
$isItDark = new IsItDark($warsaw, $dt);

$fmt = fn(?DateTimeImmutable $d): string => $d?->format('H:i:s') ?? 'n/a';

echo 'Warsaw — summer solstice twilight phases' . PHP_EOL;
echo PHP_EOL;
echo 'Astronomical dawn : ' . $fmt($isItDark->astronomicalDawn()) . PHP_EOL;
echo 'Nautical dawn     : ' . $fmt($isItDark->nauticalDawn()) . PHP_EOL;
echo 'Civil dawn        : ' . $fmt($isItDark->civilDawn()) . PHP_EOL;
echo 'Sunrise           : ' . $fmt($isItDark->sunrise()) . PHP_EOL;
echo 'Solar noon        : ' . $fmt($isItDark->solarNoon()) . PHP_EOL;
echo 'Sunset            : ' . $fmt($isItDark->sunset()) . PHP_EOL;
echo 'Civil dusk        : ' . $fmt($isItDark->civilDusk()) . PHP_EOL;
echo 'Nautical dusk     : ' . $fmt($isItDark->nauticalDusk()) . PHP_EOL;
echo 'Astronomical dusk : ' . $fmt($isItDark->astronomicalDusk()) . PHP_EOL;
echo PHP_EOL;
echo 'Day length        : ' . gmdate('H:i:s', $isItDark->dayLength()) . PHP_EOL;
echo 'Night length      : ' . gmdate('H:i:s', $isItDark->nightLength()) . PHP_EOL;
