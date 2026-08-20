<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\Time;

use DateTimeImmutable;

interface Clock
{
    public function now(): DateTimeImmutable;
}
