<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Infrastructure\Persistence;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use LogicException;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceipt;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptLine;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptNotFound;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptNumber;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptRepository;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptStatus;
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\GoodsReceiptId;
use Zandu\SharedKernel\Identity\GoodsReceiptLineId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Identity\PurchaseOrderId;
use Zandu\SharedKernel\Identity\PurchaseOrderLineId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\SupplierId;
use Zandu\SharedKernel\Identity\UuidFactory;
use Zandu\SharedKernel\Money\Currency;
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class DbalGoodsReceiptRepository implements GoodsReceiptRepository
{
    public function __construct(
        private Connection $connection,
        private UuidFactory $uuids,
        private DecimalFactory $decimals,
    ) {}

    public function save(GoodsReceipt $receipt): void
    {
        $data = $this->receiptData($receipt);
        $exists = false !== $this->connection->fetchOne(
            'SELECT 1 FROM purchasing.goods_receipt WHERE organization_id = ? AND id = ?',
            [$data['organization_id'], $data['id']],
        );
        if (!$exists) {
            $this->connection->insert('purchasing.goods_receipt', $data);
        } else {
            $affected = $this->connection->executeStatement(
                'UPDATE purchasing.goods_receipt SET status = :status, supplier_delivery_note = :supplier_delivery_note, notes = :notes, posted_by = :posted_by, posted_at = :posted_at, cancelled_by = :cancelled_by, cancelled_at = :cancelled_at, version = :version WHERE organization_id = :organization_id AND id = :id AND version = :expected_version',
                [
                    'status' => $data['status'],
                    'supplier_delivery_note' => $data['supplier_delivery_note'],
                    'notes' => $data['notes'],
                    'posted_by' => $data['posted_by'],
                    'posted_at' => $data['posted_at'],
                    'cancelled_by' => $data['cancelled_by'],
                    'cancelled_at' => $data['cancelled_at'],
                    'version' => $data['version'],
                    'organization_id' => $data['organization_id'],
                    'id' => $data['id'],
                    'expected_version' => $receipt->version() - 1,
                ],
            );
            if (1 !== $affected) {
                throw new LogicException('Goods receipt was modified concurrently.');
            }
        }

        if (GoodsReceiptStatus::Draft !== $receipt->status()) {
            return;
        }
        $this->connection->delete('purchasing.goods_receipt_line', [
            'organization_id' => $receipt->organizationId()->toString(),
            'goods_receipt_id' => $receipt->id()->toString(),
        ]);
        foreach ($receipt->lines() as $line) {
            $this->connection->insert('purchasing.goods_receipt_line', $this->lineData($receipt, $line));
        }
    }

    public function get(OrganizationId $organizationId, GoodsReceiptId $goodsReceiptId): GoodsReceipt
    {
        return $this->find($organizationId, $goodsReceiptId) ?? throw GoodsReceiptNotFound::withId($goodsReceiptId);
    }

    public function getForUpdate(OrganizationId $organizationId, GoodsReceiptId $goodsReceiptId): GoodsReceipt
    {
        $locked = $this->connection->fetchOne(
            'SELECT id FROM purchasing.goods_receipt WHERE organization_id = ? AND id = ? FOR UPDATE',
            [$organizationId->toString(), $goodsReceiptId->toString()],
        );
        if (false === $locked) {
            throw GoodsReceiptNotFound::withId($goodsReceiptId);
        }

        return $this->get($organizationId, $goodsReceiptId);
    }

    public function find(OrganizationId $organizationId, GoodsReceiptId $goodsReceiptId): ?GoodsReceipt
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM purchasing.goods_receipt WHERE organization_id = ? AND id = ?',
            [$organizationId->toString(), $goodsReceiptId->toString()],
        );
        if (false === $row) {
            return null;
        }
        $lineRows = $this->connection->fetchAllAssociative(
            'SELECT * FROM purchasing.goods_receipt_line WHERE organization_id = ? AND goods_receipt_id = ? ORDER BY id',
            [$organizationId->toString(), $goodsReceiptId->toString()],
        );

        return GoodsReceipt::reconstitute(
            $goodsReceiptId,
            $organizationId,
            StoreId::fromString((string) $row['store_id'], $this->uuids),
            SupplierId::fromString((string) $row['supplier_id'], $this->uuids),
            null === $row['purchase_order_id'] ? null : PurchaseOrderId::fromString((string) $row['purchase_order_id'], $this->uuids),
            GoodsReceiptNumber::fromString((string) $row['number']),
            GoodsReceiptStatus::from((string) $row['status']),
            null === $row['supplier_delivery_note'] ? null : (string) $row['supplier_delivery_note'],
            null === $row['notes'] ? null : (string) $row['notes'],
            ActorId::fromString((string) $row['created_by'], $this->uuids),
            new DateTimeImmutable((string) $row['created_at']),
            $this->actor($row['posted_by']),
            $this->date($row['posted_at']),
            $this->actor($row['cancelled_by']),
            $this->date($row['cancelled_at']),
            (int) $row['version'],
            array_map($this->line(...), $lineRows),
        );
    }

    public function hasDraftForStore(OrganizationId $organizationId, StoreId $storeId): bool
    {
        return false !== $this->connection->fetchOne("SELECT 1 FROM purchasing.goods_receipt WHERE organization_id=? AND store_id=? AND status='DRAFT' LIMIT 1", [$organizationId->toString(), $storeId->toString()]);
    }

    /** @return array<string, mixed> */
    private function receiptData(GoodsReceipt $receipt): array
    {
        return [
            'id' => $receipt->id()->toString(),
            'organization_id' => $receipt->organizationId()->toString(),
            'store_id' => $receipt->storeId()->toString(),
            'supplier_id' => $receipt->supplierId()->toString(),
            'purchase_order_id' => $receipt->purchaseOrderId()?->toString(),
            'number' => $receipt->number()->value(),
            'status' => $receipt->status()->value,
            'supplier_delivery_note' => $receipt->supplierDeliveryNote(),
            'notes' => $receipt->notes(),
            'created_by' => $receipt->createdBy()->toString(),
            'created_at' => $receipt->createdAt()->format(DATE_ATOM),
            'posted_by' => $receipt->postedBy()?->toString(),
            'posted_at' => $receipt->postedAt()?->format(DATE_ATOM),
            'cancelled_by' => $receipt->cancelledBy()?->toString(),
            'cancelled_at' => $receipt->cancelledAt()?->format(DATE_ATOM),
            'version' => $receipt->version(),
        ];
    }

    /** @return array<string, mixed> */
    private function lineData(GoodsReceipt $receipt, GoodsReceiptLine $line): array
    {
        return [
            'id' => $line->id()->toString(),
            'organization_id' => $receipt->organizationId()->toString(),
            'goods_receipt_id' => $receipt->id()->toString(),
            'product_id' => $line->productId()->toString(),
            'product_packaging_id' => $line->productPackagingId()?->toString(),
            'entered_received_quantity' => $line->enteredReceivedQuantity()->toString(),
            'conversion_factor_snapshot' => $line->conversionFactorSnapshot()->toString(),
            'received_base_quantity' => $line->receivedBaseQuantity()->toString(),
            'actual_unit_cost' => $line->actualUnitCost()?->amount()->toString(),
            'inventory_unit_cost' => $line->inventoryUnitCost()->amount()->toString(),
            'currency' => $line->inventoryUnitCost()->currency()->code(),
            'purchase_order_id' => $receipt->purchaseOrderId()?->toString(),
            'purchase_order_line_id' => $line->purchaseOrderLineId()?->toString(),
        ];
    }

    /** @param array<string, mixed> $row */
    private function line(array $row): GoodsReceiptLine
    {
        $currency = Currency::fromCode((string) $row['currency']);
        $quantity = fn(string $field): Quantity => Quantity::fromString((string) $row[$field], $this->decimals);

        return new GoodsReceiptLine(
            GoodsReceiptLineId::fromString((string) $row['id'], $this->uuids),
            GoodsReceiptId::fromString((string) $row['goods_receipt_id'], $this->uuids),
            ProductId::fromString((string) $row['product_id'], $this->uuids),
            null === $row['product_packaging_id'] ? null : ProductPackagingId::fromString((string) $row['product_packaging_id'], $this->uuids),
            $quantity('entered_received_quantity'),
            $quantity('conversion_factor_snapshot'),
            $quantity('received_base_quantity'),
            null === $row['actual_unit_cost'] ? null : $this->money((string) $row['actual_unit_cost'], $currency),
            $this->money((string) $row['inventory_unit_cost'], $currency),
            null === $row['purchase_order_line_id'] ? null : PurchaseOrderLineId::fromString((string) $row['purchase_order_line_id'], $this->uuids),
        );
    }

    private function money(string $amount, Currency $currency): Money
    {
        return Money::fromString($amount, $currency, $this->decimals);
    }

    private function actor(mixed $value): ?ActorId
    {
        return null === $value ? null : ActorId::fromString((string) $value, $this->uuids);
    }

    private function date(mixed $value): ?DateTimeImmutable
    {
        return null === $value ? null : new DateTimeImmutable((string) $value);
    }
}
