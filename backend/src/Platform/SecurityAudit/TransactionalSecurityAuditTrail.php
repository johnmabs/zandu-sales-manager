<?php

declare(strict_types=1);

namespace Zandu\Platform\SecurityAudit;

use DateTimeImmutable;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\OutboxMessageId;
use Zandu\SharedKernel\Identity\SecurityAuditEntryId;
use Zandu\SharedKernel\Messaging\OutboxMessage;
use Zandu\SharedKernel\Messaging\OutboxRepository;
use Zandu\SharedKernel\SecurityAudit\ActorReference;
use Zandu\SharedKernel\SecurityAudit\AuditOutcome;
use Zandu\SharedKernel\SecurityAudit\ResourceReference;
use Zandu\SharedKernel\SecurityAudit\SafeAuditMetadata;
use Zandu\SharedKernel\SecurityAudit\SecurityAction;
use Zandu\SharedKernel\SecurityAudit\SecurityAuditEntry;
use Zandu\SharedKernel\SecurityAudit\SecurityAuditRepository;
use Zandu\SharedKernel\SecurityAudit\SecurityAuditTrail;

final readonly class TransactionalSecurityAuditTrail implements SecurityAuditTrail
{
    public function __construct(
        private SecurityAuditRepository $audits,
        private OutboxRepository $outbox,
        private IdGenerator $idGenerator,
    ) {}

    public function recordSuccess(
        ActorContext $actorContext,
        SecurityAction $action,
        ResourceReference $target,
        SafeAuditMetadata $metadata,
        DateTimeImmutable $occurredAt,
        ?OrganizationId $organizationId = null,
    ): void {
        $tenantId = $organizationId ?? $actorContext->organizationId();
        $entryId = SecurityAuditEntryId::generate($this->idGenerator);
        $this->audits->append(new SecurityAuditEntry(
            $entryId,
            $tenantId,
            ActorReference::fromContext($actorContext),
            $action,
            $target,
            AuditOutcome::Success,
            null,
            $metadata,
            $actorContext->correlationId(),
            null,
            $actorContext->sessionId(),
            null,
            null,
            $occurredAt,
        ));
        $this->outbox->append(new OutboxMessage(
            OutboxMessageId::generate($this->idGenerator),
            $tenantId,
            'security.audit.recorded.v1',
            [
                'auditEntryId' => $entryId->toString(),
                'action' => $action->value,
                'targetType' => $target->type,
                'targetId' => $target->id,
                'outcome' => AuditOutcome::Success->value,
            ],
            $actorContext->correlationId(),
            null,
            $occurredAt,
        ));
    }
}
