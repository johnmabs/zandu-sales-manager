<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\InitializeStock;

use LogicException;
use Zandu\Modules\Catalog\Application\Contract\InventoryProductProvider;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Inventory\Domain\Stock\{MovementQuantity,StockQuantity};
use Zandu\Modules\Inventory\Domain\Stock\{Stock,StockRepository};
use Zandu\Modules\Inventory\Domain\StockMovement\{StockMovement,StockMovementRepository,StockMovementSource,StockMovementType};
use Zandu\Modules\Organization\Application\Contract\{OperationalGuard,OperationalMode};
use Zandu\SharedKernel\Access\{PermissionCode,ResourceScope};
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Identity\{IdGenerator,StockId,StockMovementId};
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\SharedKernel\SecurityAudit\{ResourceReference,SafeAuditMetadata,SecurityAction,SecurityAuditTrail};
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class InitializeStockHandler
{
    public function __construct(private StockRepository $stocks, private StockMovementRepository $movements, private InventoryProductProvider $products, private IdGenerator $ids, private Clock $clock, private TenantTransaction $transaction, private DecimalFactory $decimals, private OperationalGuard $operationalGuard, private AuthorizationService $authorization, private SecurityAuditTrail $audit) {}
    public function __invoke(InitializeStock $command): Stock
    {
        $organizationId = $command->actorContext->organizationId();
        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): Stock {
            $this->authorization->authorize($command->actorContext, PermissionCode::InventoryInitialize, ResourceScope::store($organizationId, $command->storeId));
            $descriptor = $this->products->provide($organizationId, $command->productId);
            $this->operationalGuard->assertStore($command->actorContext, $command->storeId, OperationalMode::Standard);
            if (!$descriptor->inventoryTracked() || 'PHYSICAL' !== $descriptor->productType()) {
                throw new LogicException('Product is not eligible for inventory.');
            }
            if (null !== $this->stocks->find($organizationId, $command->storeId, $command->productId)) {
                throw new LogicException('Stock is already initialized.');
            }
            $stock = Stock::create(StockId::generate($this->ids), $organizationId, $command->storeId, $command->productId, $this->zero());
            $now = $this->clock->now();
            $stock->initialize(new StockQuantity($command->quantity), $command->actorContext->actorId(), $now);
            $this->stocks->save($stock);
            $this->movements->append(StockMovement::record(StockMovementId::generate($this->ids), $organizationId, $command->storeId, $command->productId, $stock->id(), StockMovementType::InitialStock, new MovementQuantity($command->quantity), $this->zero(), StockMovementSource::initialization(), null, $command->actorContext->actorId(), $now));
            $this->audit->recordSuccess($command->actorContext, SecurityAction::StockInitialized, ResourceReference::for('stock', $stock->id()), SafeAuditMetadata::empty(), $now);
            return $stock;
        });
    }
    private function zero(): StockQuantity
    {
        return new StockQuantity(Quantity::fromString('0', $this->decimals));
    }
}
