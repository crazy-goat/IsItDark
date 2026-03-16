<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CrazyGoat\IsItDark\IsItDark;
use CrazyGoat\IsItDark\Location;

$warsaw = new Location(52.2297, 21.0122);
$london = new Location(51.5074, -0.1278);
$tz = new DateTimeZone('Europe/Warsaw');

$base = new IsItDark($warsaw, new DateTimeImmutable('2026-03-16 20:00:00', $tz));

$tomorrow = $base->withDateTime(new DateTimeImmutable('2026-03-17 20:00:00', $tz));
$inLondon = $base->withLocation($london);

echo 'Warsaw  2026-03-16 20:00 — dark: ' . ($base->isDark() ? 'yes' : 'no') . PHP_EOL;
echo 'Warsaw  2026-03-17 20:00 — dark: ' . ($tomorrow->isDark() ? 'yes' : 'no') . PHP_EOL;
echo 'London  2026-03-16 20:00 — dark: ' . ($inLondon->isDark() ? 'yes' : 'no') . PHP_EOL;
