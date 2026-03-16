<?php

declare(strict_types=1);

namespace CrazyGoat\IsItDark\Tests\Exception;

use CrazyGoat\IsItDark\Exception\InvalidLocation;
use PHPUnit\Framework\TestCase;

class InvalidLocationTest extends TestCase
{
    public function testInvalidLatitudeMessage(): void
    {
        $exception = InvalidLocation::invalidLatitude(95.5);
        self::assertInstanceOf(\InvalidArgumentException::class, $exception);
        self::assertStringContainsString('95.5', $exception->getMessage());
    }

    public function testInvalidLongitudeMessage(): void
    {
        $exception = InvalidLocation::invalidLongitude(200.0);
        self::assertInstanceOf(\InvalidArgumentException::class, $exception);
        self::assertStringContainsString('200', $exception->getMessage());
    }
}
