<?php

declare(strict_types=1);

namespace Zandu\Tests\SharedKernel\Time;

use DateTimeImmutable;
use Zandu\SharedKernel\Time\Clock;

final readonly class FrozenClock implements Clock
{
    public function __construct(private DateTimeImmutable $dateTime) {}

    public function now(): DateTimeImmutable
    {
        return $this->dateTime;
    }
}
