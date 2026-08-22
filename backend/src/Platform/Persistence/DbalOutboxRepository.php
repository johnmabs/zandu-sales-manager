<?php

declare(strict_types=1);

namespace Zandu\Platform\Persistence;

use Doctrine\DBAL\Connection;
use JsonException;
use Zandu\SharedKernel\Messaging\OutboxMessage;
use Zandu\SharedKernel\Messaging\OutboxRepository;

final readonly class DbalOutboxRepository implements OutboxRepository
{
    public function __construct(private Connection $connection) {}

    /** @throws JsonException */
    public function append(OutboxMessage $message): void
    {
        $this->connection->insert('messaging.outbox_messages', [
            'id' => $message->id->toString(),
            'organization_id' => $message->organizationId->toString(),
            'type' => $message->type,
            'payload' => json_encode($message->payload, JSON_THROW_ON_ERROR),
            'correlation_id' => $message->correlationId->toString(),
            'causation_id' => $message->causationId?->toString(),
            'occurred_at' => $message->occurredAt,
            'available_at' => $message->occurredAt,
        ], [
            'occurred_at' => 'datetimetz_immutable',
            'available_at' => 'datetimetz_immutable',
        ]);
    }
}
