<?php

declare(strict_types=1);

namespace Zandu\Platform\Time;

use DateTimeImmutable;
use DateTimeZone;
use Zandu\SharedKernel\Time\Clock;

final class SystemClock implements Clock
{
    private const UTC = 'UTC';

    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone(self::UTC));
    }
}
