<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Infrastructure\Security;

use InvalidArgumentException;
use Zandu\Modules\IdentityAccess\Application\Contract\InvitationTokenService;
use Zandu\Modules\IdentityAccess\Application\Contract\IssuedInvitationToken;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\UuidFactory;

final readonly class SecureInvitationTokenService implements InvitationTokenService
{
    public function __construct(private string $pepper, private UuidFactory $uuidFactory) {}

    public function issue(OrganizationId $organizationId): IssuedInvitationToken
    {
        $secret = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $rawToken = $organizationId->toString() . '.' . $secret;

        return new IssuedInvitationToken($rawToken, $this->hash($rawToken));
    }

    public function hash(string $rawToken): string
    {
        if ('' === $rawToken || strlen($rawToken) > 512) {
            throw new InvalidArgumentException('Invalid invitation token.');
        }

        return hash_hmac('sha256', $rawToken, $this->pepper);
    }

    public function organizationId(string $rawToken): OrganizationId
    {
        $separator = strpos($rawToken, '.');
        if (36 !== $separator) {
            throw new InvalidArgumentException('Invalid invitation token.');
        }

        try {
            return OrganizationId::fromString(substr($rawToken, 0, $separator), $this->uuidFactory);
        } catch (InvalidArgumentException) {
            throw new InvalidArgumentException('Invalid invitation token.');
        }
    }
}
