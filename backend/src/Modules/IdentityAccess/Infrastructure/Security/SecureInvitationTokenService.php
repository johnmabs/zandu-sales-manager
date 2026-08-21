<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Infrastructure\Security;

use InvalidArgumentException;
use Zandu\Modules\IdentityAccess\Application\Contract\InvitationTokenService;
use Zandu\Modules\IdentityAccess\Application\Contract\IssuedInvitationToken;

final readonly class SecureInvitationTokenService implements InvitationTokenService
{
    public function __construct(private string $pepper) {}

    public function issue(): IssuedInvitationToken
    {
        $rawToken = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');

        return new IssuedInvitationToken($rawToken, $this->hash($rawToken));
    }

    public function hash(string $rawToken): string
    {
        if ('' === $rawToken || strlen($rawToken) > 512) {
            throw new InvalidArgumentException('Invalid invitation token.');
        }

        return hash_hmac('sha256', $rawToken, $this->pepper);
    }
}
