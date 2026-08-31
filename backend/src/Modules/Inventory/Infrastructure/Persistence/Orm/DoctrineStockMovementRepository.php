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
    public function append(StockMovement $movement): void
    {
        $this->em->persist(StockMovementRecord::fromAggregate($movement));
        $this->em->flush();
    }
    public function appendOnce(StockMovement $movement): bool
    {
        $affected = $this->em->getConnection()->executeStatement(
            'INSERT INTO inventory.stock_movement (id, organization_id, store_id, product_id, stock_id, type, quantity, previous_quantity, resulting_quantity, source_type, source_reference_id, reason, performed_by, occurred_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON CONFLICT (organization_id, product_id, source_type, source_reference_id) WHERE source_reference_id IS NOT NULL DO NOTHING',
            [
                $movement->id()->toString(), $movement->organizationId()->toString(), $movement->storeId()->toString(), $movement->productId()->toString(), $movement->stockId()->toString(),
                $movement->type()->value, $movement->quantity()->toString(), $movement->previousQuantity()->toString(), $movement->resultingQuantity()->toString(),
                $movement->source()->type(), $movement->source()->referenceId(), $movement->reason(), $movement->performedBy()?->toString(), $movement->occurredAt()->format(DATE_ATOM),
            ],
        );

        return 1 === $affected;
    }
    public function findByStock(OrganizationId $organizationId, StockId $stockId): array
    {
        return array_map(fn(StockMovementRecord $r): StockMovement => $this->aggregate($r), $this->em->getRepository(StockMovementRecord::class)->findBy(['organizationId' => $organizationId->toString(),'stockId' => $stockId->toString()], ['occurredAt' => 'ASC']));
    }
    public function findByStore(OrganizationId $organizationId, StoreId $storeId): array
    {
        return array_map(fn(StockMovementRecord $r): StockMovement => $this->aggregate($r), $this->em->getRepository(StockMovementRecord::class)->findBy(['organizationId' => $organizationId->toString(),'storeId' => $storeId->toString()], ['occurredAt' => 'ASC']));
    }
    public function findByProduct(OrganizationId $organizationId, StoreId $storeId, ProductId $productId): array
    {
        return array_map(fn(StockMovementRecord $r): StockMovement => $this->aggregate($r), $this->em->getRepository(StockMovementRecord::class)->findBy(['organizationId' => $organizationId->toString(),'storeId' => $storeId->toString(),'productId' => $productId->toString()], ['occurredAt' => 'ASC']));
    }
    private function aggregate(StockMovementRecord $r): StockMovement
    {
        $f = $this->uuids;
        $q = fn(string $v): Quantity => Quantity::fromString($v, $this->decimals);
        $referenceId = $r->sourceReferenceId();
        $source = match ($r->sourceType()) {
            'INITIALIZATION' => StockMovementSource::initialization(),
            'SALE' => StockMovementSource::sale(\Zandu\SharedKernel\Identity\SaleId::fromString((string) $referenceId, $this->uuids)),
            'RETURN' => StockMovementSource::saleReturn(\Zandu\SharedKernel\Identity\ReturnSaleId::fromString((string) $referenceId, $this->uuids)),
            'GOODS_RECEIPT' => StockMovementSource::goodsReceipt(\Zandu\SharedKernel\Identity\GoodsReceiptId::fromString((string) $referenceId, $this->uuids)),
            'GOODS_RECEIPT_CORRECTION' => StockMovementSource::goodsReceiptCorrection(\Zandu\SharedKernel\Identity\GoodsReceiptCorrectionId::fromString((string) $referenceId, $this->uuids)),
            'PURCHASE_RETURN' => StockMovementSource::purchaseReturn(\Zandu\SharedKernel\Identity\PurchaseReturnId::fromString((string) $referenceId, $this->uuids)),
            default => StockMovementSource::manualAdjustment(null === $referenceId ? null : $this->uuids->fromString($referenceId)),
        };
        return StockMovement::record(\Zandu\SharedKernel\Identity\StockMovementId::fromString($r->id(), $f), OrganizationId::fromString($r->organizationId(), $f), StoreId::fromString($r->storeId(), $f), ProductId::fromString($r->productId(), $f), StockId::fromString($r->stockId(), $f), StockMovementType::from($r->type()), new MovementQuantity($q($r->quantity())), new StockQuantity($q($r->previousQuantity())), $source, $r->reason(), null === $r->performedBy() ? null : ActorId::fromString($r->performedBy(), $f), $r->occurredAt());
    }
}
