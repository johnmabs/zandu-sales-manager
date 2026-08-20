<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\Decimal;

enum RoundingMode
{
    case Unnecessary;
    case Up;
    case Down;
    case Ceiling;
    case Floor;
    case HalfUp;
    case HalfDown;
    case HalfEven;
}
