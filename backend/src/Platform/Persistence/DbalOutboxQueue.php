<?php

declare(strict_types=1);

namespace Zandu\Platform\Persistence;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;
use JsonException;
use Zandu\Platform\Messaging\ClaimedOutboxMessage;
use Zandu\Platform\Messaging\OutboxClaimLost;
use Zandu\Platform\Messaging\OutboxQueue;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\OutboxMessageId;
use Zandu\SharedKernel\Identity\Uuid;
use Zandu\SharedKernel\Identity\UuidFactory;
use Zandu\SharedKernel\Messaging\CausationId;
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Messaging\OutboxMessage;

final readonly class DbalOutboxQueue implements OutboxQueue
{
    public function __construct(
        private Connection $connection,
        private UuidFactory $uuidFactory,
    ) {}

    /**
     * @return list<ClaimedOutboxMessage>
     *
     * @throws JsonException
     */
    public function claimBatch(
        OrganizationId $organizationId,
        Uuid $claimId,
        DateTimeImmutable $now,
        DateTimeImmutable $claimedUntil,
        int $limit,
    ): array {
        $rows = $this->connection->executeQuery(<<<'SQL'
WITH candidates AS (
    SELECT id
    FROM messaging.outbox_messages
    WHERE organization_id = :organization_id
      AND available_at <= :now
      AND (
          status = 'PENDING'
          OR (status = 'PROCESSING' AND claimed_until <= :now)
      )
    ORDER BY occurred_at, id
    FOR UPDATE SKIP LOCKED
    LIMIT :batch_limit
)
UPDATE messaging.outbox_messages AS message
SET status = 'PROCESSING',
    claim_id = :claim_id,
    claimed_until = :claimed_until
FROM candidates
WHERE message.id = candidates.id
RETURNING message.id,
          message.organization_id,
          message.type,
          message.payload,
          message.correlation_id,
          message.causation_id,
          message.occurred_at,
          message.attempts
SQL, [
            'organization_id' => $organizationId->toString(),
            'claim_id' => $claimId->toString(),
            'now' => $now,
            'claimed_until' => $claimedUntil,
            'batch_limit' => $limit,
        ], [
            'now' => Types::DATETIMETZ_IMMUTABLE,
            'claimed_until' => Types::DATETIMETZ_IMMUTABLE,
            'batch_limit' => ParameterType::INTEGER,
        ])->fetchAllAssociative();

        return array_map(fn(array $row): ClaimedOutboxMessage => $this->hydrate($row, $claimId), $rows);
    }

    public function markPublished(ClaimedOutboxMessage $claimed, DateTimeImmutable $publishedAt): void
    {
        $affected = $this->connection->executeStatement(<<<'SQL'
UPDATE messaging.outbox_messages
SET status = 'PUBLISHED',
    published_at = :published_at,
    claimed_until = NULL,
    claim_id = NULL,
    last_error = NULL
WHERE id = :id
  AND organization_id = :organization_id
  AND status = 'PROCESSING'
  AND claim_id = :claim_id
SQL, [
            'id' => $claimed->message->id->toString(),
            'organization_id' => $claimed->message->organizationId->toString(),
            'claim_id' => $claimed->claimId->toString(),
            'published_at' => $publishedAt,
        ], [
            'published_at' => Types::DATETIMETZ_IMMUTABLE,
        ]);

        $this->assertClaimStillOwned($affected, $claimed);
    }

    public function markFailed(
        ClaimedOutboxMessage $claimed,
        DateTimeImmutable $availableAt,
        string $error,
        bool $permanentlyFailed,
    ): void {
        $affected = $this->connection->executeStatement(<<<'SQL'
UPDATE messaging.outbox_messages
SET status = :status,
    attempts = attempts + 1,
    available_at = :available_at,
    claimed_until = NULL,
    claim_id = NULL,
    last_error = :last_error
WHERE id = :id
  AND organization_id = :organization_id
  AND status = 'PROCESSING'
  AND claim_id = :claim_id
SQL, [
            'id' => $claimed->message->id->toString(),
            'organization_id' => $claimed->message->organizationId->toString(),
            'claim_id' => $claimed->claimId->toString(),
            'available_at' => $availableAt,
            'last_error' => $error,
            'status' => $permanentlyFailed ? 'FAILED' : 'PENDING',
        ], [
            'available_at' => Types::DATETIMETZ_IMMUTABLE,
        ]);

        $this->assertClaimStillOwned($affected, $claimed);
    }

    /**
     * @param array<string, mixed> $row
     *
     * @throws JsonException
     */
    private function hydrate(array $row, Uuid $claimId): ClaimedOutboxMessage
    {
        $payload = json_decode((string) $row['payload'], true, flags: JSON_THROW_ON_ERROR);

        if (!is_array($payload)) {
            throw new JsonException('An outbox payload must be a JSON object.');
        }

        /** @var array<string, bool|float|int|string|null> $payload */
        $message = new OutboxMessage(
            OutboxMessageId::fromString((string) $row['id'], $this->uuidFactory),
            OrganizationId::fromString((string) $row['organization_id'], $this->uuidFactory),
            (string) $row['type'],
            $payload,
            CorrelationId::fromString((string) $row['correlation_id'], $this->uuidFactory),
            null === $row['causation_id']
                ? null
                : CausationId::fromString((string) $row['causation_id'], $this->uuidFactory),
            new DateTimeImmutable((string) $row['occurred_at']),
        );

        return new ClaimedOutboxMessage($message, $claimId, (int) $row['attempts']);
    }

    private function assertClaimStillOwned(int $affected, ClaimedOutboxMessage $claimed): void
    {
        if (1 !== $affected) {
            throw new OutboxClaimLost($claimed);
        }
    }
}
