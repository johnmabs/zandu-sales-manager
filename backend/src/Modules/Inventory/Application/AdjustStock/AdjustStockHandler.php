<?php
declare(strict_types=1);
namespace Zandu\Modules\Inventory\Application\AdjustStock;
use InvalidArgumentException;
use LogicException;
use Zandu\Modules\Inventory\Domain\Stock\{MovementQuantity,StockRepository};
use Zandu\Modules\Organization\Application\Contract\{OperationalGuard,OperationalMode};
use Zandu\Modules\Inventory\Domain\StockMovement\{StockMovement,StockMovementId,StockMovementRepository,StockMovementSource,StockMovementType};
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Identity\{IdGenerator};
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;
use Zandu\SharedKernel\SecurityAudit\{ResourceReference,SafeAuditMetadata,SecurityAction,SecurityAuditTrail};
final readonly class AdjustStockHandler
{
    public function __construct(private StockRepository $stocks, private StockMovementRepository $movements, private IdGenerator $ids, private Clock $clock, private TenantTransaction $transaction, private DecimalFactory $decimals, private OperationalGuard $operationalGuard, private SecurityAuditTrail $audit) {}
    public function __invoke(AdjustStock $command): void
    {
        if ('' === trim($command->reason)) throw new InvalidArgumentException('Adjustment reason is required.');
        $organizationId=$command->actorContext->organizationId();
        $this->transaction->transactional($organizationId, function() use($command,$organizationId): void {
            $stock=$this->stocks->get($organizationId,$command->storeId,$command->productId); $this->operationalGuard->assertStore($command->actorContext,$command->storeId,OperationalMode::Standard);
            if (!$stock->initialized()) throw new LogicException('Stock must be initialized before adjustment.');
            if ($command->delta->isZero()) throw new InvalidArgumentException('Adjustment delta cannot be zero.');
            $quantity=new MovementQuantity($command->delta->isNegative() ? new \Zandu\SharedKernel\Quantity\Quantity($this->decimals->fromString(ltrim($command->delta->toString(), '-'))) : $command->delta);
            $type=$command->delta->isNegative()?StockMovementType::AdjustmentOut:StockMovementType::AdjustmentIn;
            $previous=$stock->quantityOnHand(); $command->delta->isNegative()?$stock->decrease($quantity):$stock->increase($quantity); $now=$this->clock->now(); $this->stocks->save($stock);
            $this->movements->append(StockMovement::record(StockMovementId::generate($this->ids),$organizationId,$command->storeId,$command->productId,$stock->id(),$type,$quantity,$previous,StockMovementSource::manualAdjustment(),$command->reason,$command->actorContext->actorId(),$now));
            $this->audit->recordSuccess($command->actorContext, SecurityAction::StockAdjusted, ResourceReference::for('stock', $stock->id()), SafeAuditMetadata::empty(), $now);
        });
    }
}
