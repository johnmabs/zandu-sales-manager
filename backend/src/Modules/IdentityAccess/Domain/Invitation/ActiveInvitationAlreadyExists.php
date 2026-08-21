<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Domain\Invitation;

use LogicException;

final class ActiveInvitationAlreadyExists extends LogicException
{
    public static function forEmail(InvitationEmail $email): self
    {
        return new self(sprintf('An active invitation already exists for "%s".', $email->value()));
    }
}
