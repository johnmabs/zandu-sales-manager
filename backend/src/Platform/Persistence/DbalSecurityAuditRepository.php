<?php

declare(strict_types=1);

namespace Zandu\Platform\Persistence;

use Doctrine\DBAL\Connection;
use JsonException;
use Zandu\SharedKernel\SecurityAudit\SecurityAuditEntry;
use Zandu\SharedKernel\SecurityAudit\SecurityAuditRepository;

final readonly class DbalSecurityAuditRepository implements SecurityAuditRepository
{
    public function __construct(private Connection $connection) {}

    /** @throws JsonException */
    public function append(SecurityAuditEntry $entry): void
    {
        $this->connection->insert('security.security_audit_entries', [
            'id' => $entry->id->toString(),
            'organization_id' => $entry->organizationId->toString(),
            'actor_id' => $entry->actor->actorId->toString(),
            'actor_type' => $entry->actor->actorType->name,
            'user_id' => $entry->actor->userId?->toString(),
            'action' => $entry->action->value,
            'target_type' => $entry->target->type,
            'target_id' => $entry->target->id,
            'outcome' => $entry->outcome->value,
            'reason' => $entry->reason,
            'metadata' => json_encode($entry->metadata->toArray(), JSON_THROW_ON_ERROR),
            'correlation_id' => $entry->correlationId->toString(),
            'causation_id' => $entry->causationId?->toString(),
            'session_id' => $entry->sessionId?->toString(),
            'ip_address' => $entry->ipAddress,
            'user_agent' => $entry->userAgent,
            'occurred_at' => $entry->occurredAt,
        ], [
            'occurred_at' => 'datetimetz_immutable',
        ]);
    }
}
