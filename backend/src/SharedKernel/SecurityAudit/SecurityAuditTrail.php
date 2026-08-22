<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\SecurityAudit;

use DateTimeImmutable;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\OrganizationId;

interface SecurityAuditTrail
{
    public function recordSuccess(
        ActorContext $actorContext,
        SecurityAction $action,
        ResourceReference $target,
        SafeAuditMetadata $metadata,
        DateTimeImmutable $occurredAt,
        ?OrganizationId $organizationId = null,
    ): void;

    public function recordDenied(
        ActorContext $actorContext,
        ResourceReference $target,
        string $reason,
        SafeAuditMetadata $metadata,
        DateTimeImmutable $occurredAt,
    ): void;
}
