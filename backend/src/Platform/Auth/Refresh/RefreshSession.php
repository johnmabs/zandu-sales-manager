<?php

declare(strict_types=1);

namespace Zandu\Platform\Auth\Refresh;

use DateTimeImmutable;
use Zandu\SharedKernel\Identity\SessionId;

final class RefreshSession
{
    /**
     * @param list<string> $usedTokenHashes
     */
    public function __construct(
        private readonly SessionId $id,
        private readonly string $userIdentifier,
        private string $currentTokenHash,
        private array $usedTokenHashes,
        private readonly DateTimeImmutable $expiresAt,
        private ?DateTimeImmutable $revokedAt = null,
    ) {
    }

    public function rotate(string $presentedHash, string $replacementHash, DateTimeImmutable $now): void
    {
        if ($this->isRevoked() || $now >= $this->expiresAt) {
            throw new InvalidRefreshToken('Refresh session is inactive.');
        }

        if (in_array($presentedHash, $this->usedTokenHashes, true)) {
            $this->revokedAt = $now;

            throw new RefreshTokenReuse('A rotated refresh token was reused.');
        }

        if (!hash_equals($this->currentTokenHash, $presentedHash)) {
            throw new InvalidRefreshToken('Refresh token does not match its session.');
        }

        $this->usedTokenHashes[] = $this->currentTokenHash;
        $this->currentTokenHash = $replacementHash;
    }

    public function revoke(DateTimeImmutable $now): void
    {
        $this->revokedAt ??= $now;
    }

    public function id(): SessionId
    {
        return $this->id;
    }

    public function userIdentifier(): string
    {
        return $this->userIdentifier;
    }

    public function currentTokenHash(): string
    {
        return $this->currentTokenHash;
    }

    /**
     * @return list<string>
     */
    public function usedTokenHashes(): array
    {
        return $this->usedTokenHashes;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function revokedAt(): ?DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function isRevoked(): bool
    {
        return null !== $this->revokedAt;
    }
}
