<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Sales\Domain\{ReturnSale, ReturnSaleRepository};
use Zandu\SharedKernel\Access\{PermissionCode, ResourceScope};
use Zandu\SharedKernel\SecurityAudit\{ResourceReference, SafeAuditMetadata, SecurityAction, SecurityAuditTrail};
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class CancelReturnSaleHandler
{
    public function __construct(private ReturnSaleRepository $returns, private TenantTransaction $transaction, private AuthorizationService $authorization, private OperationalGuard $guard, private SecurityAuditTrail $audit, private ReturnSaleEventPublisher $events, private Clock $clock) {}

    public function __invoke(CancelReturnSale $command): ReturnSale
    {
        return $this->transaction->transactional($command->actor->organizationId(), function () use ($command): ReturnSale {
            $return = $this->returns->getForUpdate($command->actor->organizationId(), $command->returnSaleId);
            $this->authorization->authorize($command->actor, PermissionCode::SaleReturnCancel, ResourceScope::store($return->organizationId(), $return->storeId()));
            $this->guard->assertStore($command->actor, $return->storeId());
            $now = $this->clock->now();
            $return->cancel($command->actor, $now);
            $this->returns->save($return);
            $this->audit->recordSuccess($command->actor, SecurityAction::SaleReturnCancelled, ResourceReference::for('return_sale', $return->id()), SafeAuditMetadata::empty(), $now);
            $this->events->publish('sale_return_cancelled', $return, $command->actor);

            return $return;
        });
    }
}
