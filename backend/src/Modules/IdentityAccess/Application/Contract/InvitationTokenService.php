<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application\Contract;

interface InvitationTokenService
{
    public function issue(): IssuedInvitationToken;

    public function hash(string $rawToken): string;
}
