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
    private readonly SolarCalculatorInterface $calculator;
    private ?SolarData $solarData = null;

    public function __construct(
        private readonly Location $location,
        ?DateTimeInterface $dateTime = null,
        ?SolarCalculatorInterface $calculator = null,
    ) {
        if (!$dateTime instanceof \DateTimeInterface) {
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
        if (!$data->sunrise instanceof \DateTimeImmutable && !$data->sunset instanceof \DateTimeImmutable) {
            return $data->sunAltitude >= 0.0 ? 86400 : 0;
        }
        if (!$data->sunrise instanceof \DateTimeImmutable || !$data->sunset instanceof \DateTimeImmutable) {
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
        return $this->getData()->sunrise instanceof \DateTimeImmutable;
    }

    public function hasSunset(): bool
    {
        return $this->getData()->sunset instanceof \DateTimeImmutable;
    }

    public function isPolarDay(): bool
    {
        $data = $this->getData();
        return !$data->sunrise instanceof \DateTimeImmutable && !$data->sunset instanceof \DateTimeImmutable && $data->sunAltitude >= 0.0;
    }

    public function isPolarNight(): bool
    {
        $data = $this->getData();
        return !$data->sunrise instanceof \DateTimeImmutable && !$data->sunset instanceof \DateTimeImmutable && $data->sunAltitude < 0.0;
    }

    public function nextSunrise(): ?DateTimeImmutable
    {
        $tz = $this->dateTime->getTimezone();
        $candidate = $this->withDateTime($this->dateTime->modify('+1 day')->setTime(0, 0));

        for ($i = 0; $i < 366; $i++) {
            $sunrise = $candidate->sunrise();
            if ($sunrise instanceof \DateTimeImmutable && $sunrise->getTimestamp() > $this->dateTime->getTimestamp()) {
                return $sunrise->setTimezone($tz);
            }
            $candidate = $candidate->withDateTime($candidate->dateTime()->modify('+1 day'));
        }

        return null;
    }

    public function nextSunset(): ?DateTimeImmutable
    {
        $tz = $this->dateTime->getTimezone();
        $candidate = $this->withDateTime($this->dateTime->setTime(0, 0));

        for ($i = 0; $i < 366; $i++) {
            $sunset = $candidate->sunset();
            if ($sunset instanceof \DateTimeImmutable && $sunset->getTimestamp() > $this->dateTime->getTimestamp()) {
                return $sunset->setTimezone($tz);
            }
            $candidate = $candidate->withDateTime($candidate->dateTime()->modify('+1 day'));
        }

        return null;
    }

    public function withDateTime(DateTimeInterface $dateTime): self
    {
        return new self($this->location, $dateTime, $this->calculator);
    }

    public function withLocation(Location $location): self
    {
        return new self($location, $this->dateTime, $this->calculator);
    }

    /**
     * @return array<string, mixed>
     */
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
        $this->solarData ??= $this->calculator->calculate($this->location, $this->dateTime);
        return $this->solarData;
    }
}
