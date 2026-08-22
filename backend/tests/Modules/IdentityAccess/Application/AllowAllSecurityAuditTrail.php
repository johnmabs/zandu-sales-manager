<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\IdentityAccess\Application;

use DateTimeImmutable;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\SecurityAudit\ResourceReference;
use Zandu\SharedKernel\SecurityAudit\SafeAuditMetadata;
use Zandu\SharedKernel\SecurityAudit\SecurityAction;
use Zandu\SharedKernel\SecurityAudit\SecurityAuditTrail;

final class AllowAllSecurityAuditTrail implements SecurityAuditTrail
{
    public function recordSuccess(ActorContext $actorContext, SecurityAction $action, ResourceReference $target, SafeAuditMetadata $metadata, DateTimeImmutable $occurredAt, ?OrganizationId $organizationId = null): void {}

    public function recordDenied(ActorContext $actorContext, ResourceReference $target, string $reason, SafeAuditMetadata $metadata, DateTimeImmutable $occurredAt): void {}
}
