<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Sales\Domain\{ReturnSale, ReturnSaleLine, ReturnSaleRepository, SaleLineCostSnapshotRepository, SaleRepository};
use Zandu\SharedKernel\Access\{PermissionCode, ResourceScope};
use Zandu\SharedKernel\Identity\{IdGenerator, ReturnSaleLineId};
use Zandu\SharedKernel\SecurityAudit\{ResourceReference, SafeAuditMetadata, SecurityAction, SecurityAuditTrail};
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class AddReturnSaleLineHandler
{
    public function __construct(
        private ReturnSaleRepository $returns,
        private SaleRepository $sales,
        private SaleLineCostSnapshotRepository $costs,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $guard,
        private SecurityAuditTrail $audit,
        private ReturnSaleEventPublisher $events,
        private IdGenerator $ids,
        private Clock $clock,
    ) {}

    public function __invoke(AddReturnSaleLine $command): ReturnSale
    {
        return $this->transaction->transactional($command->actor->organizationId(), function () use ($command): ReturnSale {
            $return = $this->returns->getForUpdate($command->actor->organizationId(), $command->returnSaleId);
            $this->authorization->authorize($command->actor, PermissionCode::SaleReturnCreate, ResourceScope::store($return->organizationId(), $return->storeId()));
            $this->guard->assertStore($command->actor, $return->storeId());
            $sale = $this->sales->get($return->organizationId(), $return->saleId());
            $original = $sale->line($command->saleLineId);
            $return->addLine(new ReturnSaleLine(
                ReturnSaleLineId::generate($this->ids),
                $original,
                $this->costs->findBySaleLine($return->organizationId(), $original->id()),
                $command->quantity,
                $command->restock,
                $command->reason,
            ));
            $this->returns->save($return);
            $now = $this->clock->now();
            $this->audit->recordSuccess($command->actor, SecurityAction::SaleReturnLineAdded, ResourceReference::for('return_sale', $return->id()), SafeAuditMetadata::fromArray(['saleLineId' => $original->id()->toString(), 'restock' => $command->restock]), $now);
            $this->events->publish('sale_return_line_added', $return, $command->actor, ['saleLineId' => $original->id()->toString(), 'restock' => $command->restock]);

            return $return;
        });
    }
}
