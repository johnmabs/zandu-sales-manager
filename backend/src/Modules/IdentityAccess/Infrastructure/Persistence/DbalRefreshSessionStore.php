<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Infrastructure\Persistence;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Throwable;
use Zandu\Platform\Auth\Refresh\RefreshSession;
use Zandu\Platform\Auth\Refresh\RefreshSessionStore;
use Zandu\Platform\Auth\Refresh\RefreshTokenReuse;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\SessionId;
use Zandu\SharedKernel\Identity\UuidFactory;

final readonly class DbalRefreshSessionStore implements RefreshSessionStore
{
    public function __construct(
        private Connection $connection,
        private UuidFactory $uuidFactory,
    ) {}

    public function add(RefreshSession $session): void
    {
        $this->connection->insert('identity_access.refresh_session', $this->data($session), $this->types());
    }

    public function getForUpdate(SessionId $id): ?RefreshSession
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM identity_access.refresh_session WHERE id = ? FOR UPDATE',
            [$id->toString()],
        );

        if (false === $row) {
            return null;
        }

        $usedTokenHashes = json_decode((string) $row['used_token_hashes'], true, flags: JSON_THROW_ON_ERROR);

        return new RefreshSession(
            SessionId::fromString((string) $row['id'], $this->uuidFactory),
            (string) $row['user_identifier'],
            OrganizationId::fromString((string) $row['organization_id'], $this->uuidFactory),
            (int) $row['authorization_version'],
            (string) $row['current_token_hash'],
            is_array($usedTokenHashes) ? array_values($usedTokenHashes) : [],
            new DateTimeImmutable((string) $row['expires_at']),
            null !== $row['revoked_at'] ? new DateTimeImmutable((string) $row['revoked_at']) : null,
        );
    }

    public function save(RefreshSession $session): void
    {
        $this->connection->update(
            'identity_access.refresh_session',
            $this->data($session),
            ['id' => $session->id()->toString()],
            $this->types() + ['id' => Types::GUID],
        );
    }

    public function transactional(callable $operation): mixed
    {
        $this->connection->beginTransaction();

        try {
            $result = $operation();
            $this->connection->commit();

            return $result;
        } catch (RefreshTokenReuse $exception) {
            $this->connection->commit();

            throw $exception;
        } catch (Throwable $exception) {
            $this->connection->rollBack();

            throw $exception;
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function data(RefreshSession $session): array
    {
        return [
            'id' => $session->id()->toString(),
            'user_identifier' => $session->userIdentifier(),
            'organization_id' => $session->organizationId()->toString(),
            'authorization_version' => $session->authorizationVersion(),
            'current_token_hash' => $session->currentTokenHash(),
            'used_token_hashes' => json_encode($session->usedTokenHashes(), JSON_THROW_ON_ERROR),
            'expires_at' => $session->expiresAt(),
            'revoked_at' => $session->revokedAt(),
        ];
    }

    /**
     * @return array<string,string>
     */
    private function types(): array
    {
        return [
            'id' => Types::GUID,
            'organization_id' => Types::GUID,
            'authorization_version' => Types::INTEGER,
            'used_token_hashes' => Types::JSON,
            'expires_at' => Types::DATETIMETZ_IMMUTABLE,
            'revoked_at' => Types::DATETIMETZ_IMMUTABLE,
        ];
    }
}
