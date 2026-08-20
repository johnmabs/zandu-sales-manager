<?php

declare(strict_types=1);

namespace Zandu\Platform\Auth\Refresh;

use DateInterval;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\SessionId;
use Zandu\SharedKernel\Identity\UuidFactory;
use Zandu\SharedKernel\Time\Clock;

final readonly class RefreshSessionManager
{
    private const TOKEN_BYTES = 32;

    public function __construct(
        private RefreshSessionStore $store,
        private IdGenerator $idGenerator,
        private UuidFactory $uuidFactory,
        private Clock $clock,
        private int $ttlSeconds,
    ) {
    }

    public function issue(string $userIdentifier): IssuedRefreshToken
    {
        $sessionId = SessionId::generate($this->idGenerator);
        $token = $this->token($sessionId);
        $expiresAt = $this->clock->now()->add(new DateInterval('PT'.$this->ttlSeconds.'S'));

        $this->store->add(new RefreshSession(
            $sessionId,
            $userIdentifier,
            $this->hash($token),
            [],
            $expiresAt,
        ));

        return new IssuedRefreshToken($token, $sessionId, $userIdentifier, $expiresAt);
    }

    public function rotate(string $token): IssuedRefreshToken
    {
        $sessionId = $this->sessionIdFromToken($token);

        return $this->store->transactional(function () use ($sessionId, $token): IssuedRefreshToken {
            $session = $this->store->getForUpdate($sessionId)
                ?? throw new InvalidRefreshToken('Refresh session does not exist.');
            $replacement = $this->token($sessionId);

            try {
                $session->rotate($this->hash($token), $this->hash($replacement), $this->clock->now());
            } finally {
                $this->store->save($session);
            }

            return new IssuedRefreshToken(
                $replacement,
                $sessionId,
                $session->userIdentifier(),
                $session->expiresAt(),
            );
        });
    }

    public function revoke(string $token): void
    {
        $sessionId = $this->sessionIdFromToken($token);

        $this->store->transactional(function () use ($sessionId): void {
            $session = $this->store->getForUpdate($sessionId)
                ?? throw new InvalidRefreshToken('Refresh session does not exist.');
            $session->revoke($this->clock->now());
            $this->store->save($session);
        });
    }

    private function token(SessionId $sessionId): string
    {
        return $sessionId->toString().'.'.rtrim(strtr(base64_encode(random_bytes(self::TOKEN_BYTES)), '+/', '-_'), '=');
    }

    private function sessionIdFromToken(string $token): SessionId
    {
        $parts = explode('.', $token, 2);

        if (2 !== count($parts) || '' === $parts[1]) {
            throw new InvalidRefreshToken('Refresh token has an invalid format.');
        }

        try {
            return SessionId::fromString($parts[0], $this->uuidFactory);
        } catch (\InvalidArgumentException $exception) {
            throw new InvalidRefreshToken('Refresh token has an invalid session identifier.', previous: $exception);
        }
    }

    private function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
