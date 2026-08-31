<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\CancelPurchaseReturn;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Purchasing\Domain\PurchaseReturn\PurchaseReturn;
use Zandu\Modules\Purchasing\Domain\PurchaseReturn\PurchaseReturnRepository;
use Zandu\Modules\Purchasing\Domain\PurchaseReturn\PurchaseReturnStatus;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\OutboxMessageId;
use Zandu\SharedKernel\Messaging\OutboxMessage;
use Zandu\SharedKernel\Messaging\OutboxRepository;
use Zandu\SharedKernel\SecurityAudit\ResourceReference;
use Zandu\SharedKernel\SecurityAudit\SafeAuditMetadata;
use Zandu\SharedKernel\SecurityAudit\SecurityAction;
use Zandu\SharedKernel\SecurityAudit\SecurityAuditTrail;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class CancelPurchaseReturnHandler
{
    public function __construct(
        private PurchaseReturnRepository $purchaseReturns,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $operationalGuard,
        private SecurityAuditTrail $audit,
        private OutboxRepository $outbox,
        private IdGenerator $ids,
        private Clock $clock,
    ) {}

    public function __invoke(CancelPurchaseReturn $command): PurchaseReturn
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): PurchaseReturn {
            $return = $this->purchaseReturns->getForUpdate($organizationId, $command->purchaseReturnId);
            $this->authorization->authorize(
                $command->actorContext,
                PermissionCode::PurchaseReturnCancel,
                ResourceScope::store($organizationId, $return->sourceStoreId()),
            );
            if (PurchaseReturnStatus::Cancelled === $return->status()) {
                return $return;
            }

            $this->operationalGuard->assertStore($command->actorContext, $return->sourceStoreId());
            $now = $this->clock->now();
            $return->cancel($command->actorContext->actorId(), $now);
            $this->purchaseReturns->save($return);
            $this->audit->recordSuccess(
                $command->actorContext,
                SecurityAction::PurchaseReturnCancelled,
                ResourceReference::for('purchase_return', $return->id()),
                SafeAuditMetadata::fromArray(['reason' => $return->reason()]),
                $now,
            );
            $this->outbox->append(new OutboxMessage(
                OutboxMessageId::generate($this->ids),
                $organizationId,
                'purchasing.purchase_return_cancelled.v1',
                ['purchaseReturnId' => $return->id()->toString()],
                $command->actorContext->correlationId(),
                $command->actorContext->causationId(),
                $now,
            ));

            return $return;
        });
    }
}
