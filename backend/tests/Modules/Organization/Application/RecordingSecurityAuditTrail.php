<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Organization\Application;

use DateTimeImmutable;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\SecurityAudit\ResourceReference;
use Zandu\SharedKernel\SecurityAudit\SafeAuditMetadata;
use Zandu\SharedKernel\SecurityAudit\SecurityAction;
use Zandu\SharedKernel\SecurityAudit\SecurityAuditTrail;

final class RecordingSecurityAuditTrail implements SecurityAuditTrail
{
    /** @var list<array{SecurityAction,ResourceReference}> */
    public array $records = [];

    public function recordSuccess(ActorContext $actorContext, SecurityAction $action, ResourceReference $target, SafeAuditMetadata $metadata, DateTimeImmutable $occurredAt, ?OrganizationId $organizationId = null): void
    {
        $this->records[] = [$action, $target];
    }
}
