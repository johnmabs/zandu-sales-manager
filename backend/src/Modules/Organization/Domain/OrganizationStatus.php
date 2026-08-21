<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Domain;

enum OrganizationStatus: string
{
    case Active = 'ACTIVE';
    case Suspended = 'SUSPENDED';
    case ClosurePending = 'CLOSURE_PENDING';
    case Closed = 'CLOSED';
}
