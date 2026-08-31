<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\StockTransferDraft;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Inventory\Domain\StockTransfer\{StockTransfer, StockTransferLine, StockTransferRepository};
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\SharedKernel\Access\{PermissionCode, ResourceScope};
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{IdGenerator, StockTransferId, StockTransferLineId};
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class StockTransferDraftHandler
{
    public function __construct(private StockTransferRepository $transfers, private IdGenerator $ids, private Clock $clock, private TenantTransaction $transaction, private AuthorizationService $authorization, private OperationalGuard $guard) {}
    public function add(AddStockTransferLine $c): StockTransfer
    {
        return $this->mutate($c->transferId, $c->actorContext, PermissionCode::StockTransferUpdate, fn(StockTransfer $t) => $t->addLine(new StockTransferLine(StockTransferLineId::generate($this->ids), $t->id(), $c->productId, $c->requestedQuantity)));
    }
    public function update(UpdateStockTransferLine $c): StockTransfer
    {
        return $this->mutate($c->transferId, $c->actorContext, PermissionCode::StockTransferUpdate, fn(StockTransfer $t) => $t->updateLine($c->lineId, $c->requestedQuantity));
    }
    public function remove(RemoveStockTransferLine $c): StockTransfer
    {
        return $this->mutate($c->transferId, $c->actorContext, PermissionCode::StockTransferUpdate, fn(StockTransfer $t) => $t->removeLine($c->lineId));
    }
    public function cancel(CancelStockTransfer $c): StockTransfer
    {
        return $this->mutate($c->transferId, $c->actorContext, PermissionCode::StockTransferCancel, fn(StockTransfer $t) => $t->cancel($c->actorContext->actorId(), $c->reason, $this->clock->now()));
    }
    /** @param callable(StockTransfer): void $operation */ private function mutate(StockTransferId $id, ActorContext $actor, PermissionCode $permission, callable $operation): StockTransfer
    {
        $org = $actor->organizationId();
        return $this->transaction->transactional($org, function () use ($id, $actor, $permission, $operation, $org): StockTransfer {
            $t = $this->transfers->getForUpdate($org, $id);
            $this->authorization->authorize($actor, $permission, ResourceScope::store($org, $t->sourceStoreId()));
            $this->guard->assertStore($actor, $t->sourceStoreId());
            $operation($t);
            $this->transfers->save($t);
            return $t;
        });
    }
}
