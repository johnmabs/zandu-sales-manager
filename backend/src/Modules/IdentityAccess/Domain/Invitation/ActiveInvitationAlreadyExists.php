<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Domain\Invitation;

use LogicException;
use Zandu\SharedKernel\Error\ResourceConflict;

final class ActiveInvitationAlreadyExists extends LogicException implements ResourceConflict
{
    public static function forEmail(InvitationEmail $email): self
    {
        return new self(sprintf('An active invitation already exists for "%s".', $email->value()));
    }
}
