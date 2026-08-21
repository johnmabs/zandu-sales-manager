<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Domain\Invitation;

enum InvitationStatus: string
{
    case Pending = 'PENDING';
    case Accepted = 'ACCEPTED';
    case Expired = 'EXPIRED';
    case Cancelled = 'CANCELLED';
}
