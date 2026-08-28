<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Sales\Domain\{ReturnSale, ReturnSaleRepository, SaleRepository, SaleStatus, SalesRuleViolation};
use Zandu\SharedKernel\Access\{PermissionCode, ResourceScope};
use Zandu\SharedKernel\Identity\{IdGenerator, ReturnSaleId};
use Zandu\SharedKernel\SecurityAudit\{ResourceReference, SafeAuditMetadata, SecurityAction, SecurityAuditTrail};
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class CreateReturnSaleHandler
{
    public function __construct(
        private SaleRepository $sales,
        private ReturnSaleRepository $returns,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $guard,
        private SecurityAuditTrail $audit,
        private ReturnSaleEventPublisher $events,
        private IdGenerator $ids,
        private Clock $clock,
    ) {}

    public function __invoke(CreateReturnSale $command): ReturnSale
    {
        return $this->transaction->transactional($command->actor->organizationId(), function () use ($command): ReturnSale {
            $sale = $this->sales->get($command->actor->organizationId(), $command->saleId);
            if (SaleStatus::Completed !== $sale->status()) {
                throw SalesRuleViolation::with('SALE_NOT_RETURNABLE', 'Only a completed sale can be returned.');
            }
            $scope = ResourceScope::store($sale->organizationId(), $sale->storeId());
            $this->authorization->authorize($command->actor, PermissionCode::SaleReturnCreate, $scope);
            $this->guard->assertStore($command->actor, $sale->storeId());
            $now = $this->clock->now();
            $return = ReturnSale::create(ReturnSaleId::generate($this->ids), $sale->organizationId(), $sale->storeId(), $sale->id(), $command->reason, $command->actor, $now);
            $this->returns->save($return);
            $this->audit->recordSuccess($command->actor, SecurityAction::SaleReturnCreated, ResourceReference::for('return_sale', $return->id()), SafeAuditMetadata::fromArray(['saleId' => $sale->id()->toString()]), $now);
            $this->events->publish('sale_return_created', $return, $command->actor);

            return $return;
        });
    }
}
