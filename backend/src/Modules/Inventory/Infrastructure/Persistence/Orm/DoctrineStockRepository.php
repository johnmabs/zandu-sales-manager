<?php
declare(strict_types=1);
namespace Zandu\Modules\Inventory\Infrastructure\Persistence\Orm;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Zandu\Modules\Inventory\Domain\Stock\{MovementQuantity,Stock,StockNotFound,StockRepository,StockQuantity};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\SharedKernel\Identity\{ActorId,OrganizationId,ProductId,StockId,StoreId,UuidFactory};
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class DoctrineStockRepository implements StockRepository
{
    public function __construct(private EntityManagerInterface $em, private UuidFactory $uuids, private BrickDecimalFactory $decimals) {}
    public function save(Stock $stock): void
    {
        $record = $this->em->find(StockRecord::class, $stock->id()->toString());
        if ($record instanceof StockRecord) {
            $expected = $stock->version() - 1;
            if ($record->version() !== $expected) throw OptimisticLockException::lockFailedVersionMismatch($record, $expected, $record->version());
            $record->synchronize($stock);
        } else $this->em->persist(StockRecord::fromAggregate($stock));
        $this->em->flush();
    }
    public function get(OrganizationId $organizationId, StoreId $storeId, ProductId $productId): Stock { return $this->find($organizationId, $storeId, $productId) ?? throw StockNotFound::forPosition($storeId, $productId); }
    public function find(OrganizationId $organizationId, StoreId $storeId, ProductId $productId): ?Stock { return $this->aggregate($this->em->getRepository(StockRecord::class)->findOneBy(['organizationId'=>$organizationId->toString(),'storeId'=>$storeId->toString(),'productId'=>$productId->toString()])); }
    public function getById(OrganizationId $organizationId, StockId $stockId): Stock { return $this->aggregate($this->em->getRepository(StockRecord::class)->findOneBy(['organizationId'=>$organizationId->toString(),'id'=>$stockId->toString()])) ?? throw StockNotFound::withId($stockId); }
    public function decreaseIfAvailable(OrganizationId $organizationId, StockId $stockId, MovementQuantity $quantity, int $expectedVersion): bool
    {
        $affected = $this->em->getConnection()->executeStatement('UPDATE inventory.stock SET quantity_on_hand = quantity_on_hand - :quantity, version = version + 1 WHERE organization_id = :organization AND id = :id AND version = :version AND quantity_on_hand >= :quantity', ['quantity'=>$quantity->toString(),'organization'=>$organizationId->toString(),'id'=>$stockId->toString(),'version'=>$expectedVersion]);
        return 1 === $affected;
    }
    private function aggregate(mixed $value): ?Stock { if (!$value instanceof StockRecord) return null; return Stock::reconstitute(StockId::fromString($value->id(),$this->uuids), OrganizationId::fromString($value->organizationId(),$this->uuids), StoreId::fromString($value->storeId(),$this->uuids), ProductId::fromString($value->productId(),$this->uuids), new StockQuantity(Quantity::fromString($value->quantityOnHand(),$this->decimals)), $value->initialized(), $value->initializedAt(), null === $value->initializedBy()?null:ActorId::fromString($value->initializedBy(),$this->uuids), $value->version()); }
}
