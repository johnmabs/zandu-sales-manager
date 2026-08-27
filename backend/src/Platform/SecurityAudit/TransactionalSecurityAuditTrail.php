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
        $this->record($actorContext, $action, $target, AuditOutcome::Success, null, $metadata, $occurredAt, $organizationId);
    }

    public function recordDenied(
        ActorContext $actorContext,
        ResourceReference $target,
        string $reason,
        SafeAuditMetadata $metadata,
        DateTimeImmutable $occurredAt,
    ): void {
        $this->record($actorContext, SecurityAction::AuthorizationDenied, $target, AuditOutcome::Denied, $reason, $metadata, $occurredAt, null);
    }

    private function record(
        ActorContext $actorContext,
        SecurityAction $action,
        ResourceReference $target,
        AuditOutcome $outcome,
        ?string $reason,
        SafeAuditMetadata $metadata,
        DateTimeImmutable $occurredAt,
        ?OrganizationId $organizationId,
    ): void {
        $tenantId = $organizationId ?? $actorContext->organizationId();
        $entryId = SecurityAuditEntryId::generate($this->idGenerator);
        $this->audits->append(new SecurityAuditEntry(
            $entryId,
            $tenantId,
            ActorReference::fromContext($actorContext),
            $action,
            $target,
            $outcome,
            $reason,
            $metadata,
            $actorContext->correlationId(),
            $actorContext->causationId(),
            $actorContext->sessionId(),
            null,
            null,
            $occurredAt,
        ));
        $this->outbox->append(new OutboxMessage(
            OutboxMessageId::generate($this->idGenerator),
            $tenantId,
            $this->integrationEventType($action),
            [
                'auditEntryId' => $entryId->toString(),
                'action' => $action->value,
                'targetType' => $target->type,
                'targetId' => $target->id,
                'outcome' => $outcome->value,
            ],
            $actorContext->correlationId(),
            $actorContext->causationId(),
            $occurredAt,
        ));
    }

    private function integrationEventType(SecurityAction $action): string
    {
        return match ($action) {
            SecurityAction::OrganizationCreated => 'organization.created.v1',
            SecurityAction::OrganizationUpdated => 'organization.updated.v1',
            SecurityAction::OrganizationSuspended => 'organization.suspended.v1',
            SecurityAction::OrganizationReactivated => 'organization.reactivated.v1',
            SecurityAction::StoreCreated => 'organization.store_created.v1',
            SecurityAction::StoreUpdated => 'organization.store_updated.v1',
            SecurityAction::StoreSuspended => 'organization.store_suspended.v1',
            SecurityAction::StoreReactivated => 'organization.store_reactivated.v1',
            SecurityAction::MemberInvited => 'identity_access.member_invited.v1',
            SecurityAction::MemberSuspended => 'identity_access.member_suspended.v1',
            SecurityAction::MemberReactivated => 'identity_access.member_reactivated.v1',
            SecurityAction::MemberRevoked => 'identity_access.member_revoked.v1',
            SecurityAction::RoleAssigned => 'identity_access.role_assigned.v1',
            SecurityAction::RoleRemoved => 'identity_access.role_removed.v1',
            SecurityAction::OwnerAssigned => 'identity_access.owner_assigned.v1',
            SecurityAction::OwnerRemoved => 'identity_access.owner_removed.v1',
            SecurityAction::AuthorizationDenied => 'security.authorization_denied.v1',
            SecurityAction::ProductActivated => 'catalog.product_activated.v1',
            SecurityAction::ProductArchived => 'catalog.product_archived.v1',
            SecurityAction::ProductPriceUpdated => 'pricing.product_price_updated.v1',
            SecurityAction::PriceListActivated => 'pricing.price_list_activated.v1',
            SecurityAction::CategoryArchived => 'catalog.category_archived.v1',
            SecurityAction::StockInitialized => 'inventory.stock_initialized.v1',
            SecurityAction::StockAdjusted => 'inventory.stock_adjusted.v1',
            SecurityAction::StockValuationInitialized => 'inventory_costing.stock_valuation_initialized.v1',
            SecurityAction::CashRegisterArchived => 'cash.cash_register_archived.v1',
            SecurityAction::CashSessionOpened => 'cash.cash_session_opened.v1',
            SecurityAction::CashSessionClosed => 'cash.cash_session_closed.v1',
            SecurityAction::CashInRecorded => 'cash.cash_in_recorded.v1',
            SecurityAction::CashOutRecorded => 'cash.cash_out_recorded.v1',
            SecurityAction::CashWithdrawalRecorded => 'cash.cash_withdrawal_recorded.v1',
            SecurityAction::SaleCompleted => 'sales.sale_completed_audit.v1',
        };
    }
}
