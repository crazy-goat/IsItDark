<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CrazyGoat\IsItDark\IsItDark;
use CrazyGoat\IsItDark\Location;

$tromso = new Location(69.6496, 18.9560);
$tz = new DateTimeZone('Europe/Oslo');

$polarDay   = new IsItDark($tromso, new DateTimeImmutable('2026-06-21 12:00:00', $tz));
$polarNight = new IsItDark($tromso, new DateTimeImmutable('2026-12-21 12:00:00', $tz));

echo 'Tromsø — polar day (June 21)' . PHP_EOL;
echo '  isPolarDay   : ' . ($polarDay->isPolarDay() ? 'yes' : 'no') . PHP_EOL;
echo '  isDark       : ' . ($polarDay->isDark() ? 'yes' : 'no') . PHP_EOL;
echo '  sunrise      : ' . ($polarDay->sunrise()?->format('H:i:s') ?? 'none — sun never sets') . PHP_EOL;
echo '  nextSunset   : ' . ($polarDay->nextSunset()?->format('Y-m-d H:i:s T') ?? 'n/a') . PHP_EOL;
echo PHP_EOL;
echo 'Tromsø — polar night (December 21)' . PHP_EOL;
echo '  isPolarNight : ' . ($polarNight->isPolarNight() ? 'yes' : 'no') . PHP_EOL;
echo '  isDark       : ' . ($polarNight->isDark() ? 'yes' : 'no') . PHP_EOL;
echo '  sunrise      : ' . ($polarNight->sunrise()?->format('H:i:s') ?? 'none — sun never rises') . PHP_EOL;
echo '  nextSunrise  : ' . ($polarNight->nextSunrise()?->format('Y-m-d H:i:s T') ?? 'n/a') . PHP_EOL;
