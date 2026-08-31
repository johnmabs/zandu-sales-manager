<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\CreateStockTransfer;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Inventory\Domain\StockTransfer\{StockTransfer, StockTransferRepository};
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\SharedKernel\Access\{PermissionCode, ResourceScope};
use Zandu\SharedKernel\Identity\{IdGenerator, StockTransferId};
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class CreateStockTransferHandler
{
    public function __construct(private StockTransferRepository $transfers, private IdGenerator $ids, private Clock $clock, private TenantTransaction $transaction, private AuthorizationService $authorization, private OperationalGuard $guard) {}

    public function __invoke(CreateStockTransfer $command): StockTransfer
    {
        $organizationId = $command->actorContext->organizationId();
        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): StockTransfer {
            $this->authorization->authorize($command->actorContext, PermissionCode::StockTransferCreate, ResourceScope::store($organizationId, $command->sourceStoreId));
            $this->authorization->authorize($command->actorContext, PermissionCode::StockTransferCreate, ResourceScope::store($organizationId, $command->destinationStoreId));
            $this->guard->assertStore($command->actorContext, $command->sourceStoreId);
            $this->guard->assertStore($command->actorContext, $command->destinationStoreId);
            $transfer = StockTransfer::create(StockTransferId::generate($this->ids), $organizationId, $command->sourceStoreId, $command->destinationStoreId, $command->actorContext->actorId(), $this->clock->now());
            $this->transfers->save($transfer);
            return $transfer;
        });
    }
}
