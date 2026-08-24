<?php

declare(strict_types=1);

namespace Zandu\Platform\Auth\Refresh;

use DateTimeImmutable;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\SessionId;

final readonly class IssuedRefreshToken
{
    public function __construct(
        private string $token,
        private SessionId $sessionId,
        private string $userIdentifier,
        private OrganizationId $organizationId,
        private int $authorizationVersion,
        private DateTimeImmutable $expiresAt,
    ) {}

    public function token(): string
    {
        return $this->token;
    }

    public function sessionId(): SessionId
    {
        return $this->sessionId;
    }

    public function userIdentifier(): string
    {
        return $this->userIdentifier;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function authorizationVersion(): int
    {
        return $this->authorizationVersion;
    }
}
