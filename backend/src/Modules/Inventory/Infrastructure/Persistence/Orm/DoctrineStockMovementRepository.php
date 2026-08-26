<?php
declare(strict_types=1);
namespace Zandu\Modules\Inventory\Infrastructure\Persistence\Orm;
use Doctrine\ORM\EntityManagerInterface;
use Zandu\Modules\Inventory\Domain\Stock\{MovementQuantity,StockQuantity};
use Zandu\Modules\Inventory\Domain\StockMovement\{StockMovement,StockMovementRepository,StockMovementSource,StockMovementType};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\SharedKernel\Identity\{ActorId,OrganizationId,ProductId,StockId,StoreId,UuidFactory};
use Zandu\SharedKernel\Quantity\Quantity;
final readonly class DoctrineStockMovementRepository implements StockMovementRepository
{
    public function __construct(private EntityManagerInterface $em, private UuidFactory $uuids, private BrickDecimalFactory $decimals) {}
    public function append(StockMovement $movement): void { $this->em->persist(StockMovementRecord::fromAggregate($movement)); $this->em->flush(); }
    public function findByStock(OrganizationId $organizationId, StockId $stockId): array { return array_map(fn(StockMovementRecord $r): StockMovement => $this->aggregate($r), $this->em->getRepository(StockMovementRecord::class)->findBy(['organizationId'=>$organizationId->toString(),'stockId'=>$stockId->toString()], ['occurredAt'=>'ASC'])); }
    private function aggregate(StockMovementRecord $r): StockMovement { $f=$this->uuids; $q=fn(string $v): Quantity=>Quantity::fromString($v,$this->decimals); $source='INITIALIZATION'===$r->sourceType()?StockMovementSource::initialization():StockMovementSource::manualAdjustment(null===$r->sourceReferenceId()?null:ProductId::fromString($r->sourceReferenceId(),$f)); return StockMovement::record(\Zandu\Modules\Inventory\Domain\StockMovement\StockMovementId::fromString($r->id(),$f),OrganizationId::fromString($r->organizationId(),$f),StoreId::fromString($r->storeId(),$f),ProductId::fromString($r->productId(),$f),StockId::fromString($r->stockId(),$f),StockMovementType::from($r->type()),new MovementQuantity($q($r->quantity())),new StockQuantity($q($r->previousQuantity())),$source,$r->reason(),null===$r->performedBy()?null:ActorId::fromString($r->performedBy(),$f),$r->occurredAt()); }
}
