<?php

declare(strict_types=1);

namespace Zandu\Modules\Payments\Domain;

enum PaymentStatus: string
{
    case Created = 'CREATED';
    case Confirmed = 'CONFIRMED';
    case Cancelled = 'CANCELLED';
}
