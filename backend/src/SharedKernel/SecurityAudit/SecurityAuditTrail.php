<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\SecurityAudit;

use DateTimeImmutable;
use Zandu\SharedKernel\Context\ActorContext;

interface SecurityAuditTrail
{
    public function recordSuccess(
        ActorContext $actorContext,
        SecurityAction $action,
        ResourceReference $target,
        SafeAuditMetadata $metadata,
        DateTimeImmutable $occurredAt,
    ): void;
}
