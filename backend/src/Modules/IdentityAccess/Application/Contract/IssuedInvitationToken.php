<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application\Contract;

final readonly class IssuedInvitationToken
{
    public function __construct(private string $rawToken, private string $tokenHash) {}

    public function reveal(): string
    {
        return $this->rawToken;
    }
    public function tokenHash(): string
    {
        return $this->tokenHash;
    }
}
