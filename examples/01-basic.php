<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CrazyGoat\IsItDark\IsItDark;
use CrazyGoat\IsItDark\Location;

$warsaw = new Location(52.2297, 21.0122);
$isItDark = new IsItDark($warsaw);

echo 'Location : Warsaw' . PHP_EOL;
echo 'Time     : ' . $isItDark->dateTime()->format('Y-m-d H:i:s T') . PHP_EOL;
echo 'Is dark  : ' . ($isItDark->isDark() ? 'yes' : 'no') . PHP_EOL;
echo 'State    : ' . $isItDark->state()->value . PHP_EOL;
echo 'Sunrise  : ' . ($isItDark->sunrise()?->format('H:i:s T') ?? 'n/a') . PHP_EOL;
echo 'Sunset   : ' . ($isItDark->sunset()?->format('H:i:s T') ?? 'n/a') . PHP_EOL;
echo 'Day len  : ' . gmdate('H:i:s', $isItDark->dayLength()) . PHP_EOL;
