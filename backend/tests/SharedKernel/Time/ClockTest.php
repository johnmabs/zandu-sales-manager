<?php

declare(strict_types=1);

namespace Zandu\Tests\SharedKernel\Time;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Zandu\Platform\Time\SystemClock;

final class ClockTest extends TestCase
{
    public function testSystemClockReturnsCurrentUtcTime(): void
    {
        $utc = new DateTimeZone('UTC');
        $before = new DateTimeImmutable('now', $utc);

        $now = (new SystemClock())->now();

        $after = new DateTimeImmutable('now', $utc);

        self::assertSame('UTC', $now->getTimezone()->getName());
        self::assertGreaterThanOrEqual($before, $now);
        self::assertLessThanOrEqual($after, $now);
    }

    public function testFrozenClockMakesTimeDeterministic(): void
    {
        $instant = new DateTimeImmutable('2026-08-20T15:30:45.123456+00:00');
        $clock = new FrozenClock($instant);

        self::assertSame($instant, $clock->now());
        self::assertSame($clock->now(), $clock->now());
    }
}
