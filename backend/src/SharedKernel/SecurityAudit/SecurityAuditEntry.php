<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\SecurityAudit;

use DateTimeImmutable;
use InvalidArgumentException;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\SecurityAuditEntryId;
use Zandu\SharedKernel\Identity\SessionId;
use Zandu\SharedKernel\Messaging\CausationId;
use Zandu\SharedKernel\Messaging\CorrelationId;

final readonly class SecurityAuditEntry
{
    public function __construct(
        public SecurityAuditEntryId $id,
        public OrganizationId $organizationId,
        public ActorReference $actor,
        public SecurityAction $action,
        public ResourceReference $target,
        public AuditOutcome $outcome,
        public ?string $reason,
        public SafeAuditMetadata $metadata,
        public CorrelationId $correlationId,
        public ?CausationId $causationId,
        public ?SessionId $sessionId,
        public ?string $ipAddress,
        public ?string $userAgent,
        public DateTimeImmutable $occurredAt,
    ) {
        if (null !== $reason && ('' === trim($reason) || strlen($reason) > 512)) {
            throw new InvalidArgumentException('Audit reason must contain between 1 and 512 characters.');
        }
        if (null !== $ipAddress && false === filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            throw new InvalidArgumentException('Audit IP address is invalid.');
        }
        if (null !== $userAgent && ('' === trim($userAgent) || strlen($userAgent) > 512)) {
            throw new InvalidArgumentException('Audit user agent must contain between 1 and 512 characters.');
        }
        if (0 !== $occurredAt->getOffset()) {
            throw new InvalidArgumentException('Audit occurrence time must use UTC.');
        }
    }
}
