<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Infrastructure\Persistence;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use LogicException;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrder;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderLine;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderNotFound;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderNumber;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderRepository;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderStatus;
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Identity\ActorId;
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

final readonly class DbalPurchaseOrderRepository implements PurchaseOrderRepository
{
    public function __construct(
        private Connection $connection,
        private UuidFactory $uuids,
        private DecimalFactory $decimals,
    ) {}

    public function save(PurchaseOrder $purchaseOrder): void
    {
        $data = $this->orderData($purchaseOrder);
        $exists = false !== $this->connection->fetchOne(
            'SELECT 1 FROM purchasing.purchase_order WHERE organization_id = ? AND id = ?',
            [$data['organization_id'], $data['id']],
        );
        if (!$exists) {
            $this->connection->insert('purchasing.purchase_order', $data);
        } else {
            $affected = $this->connection->executeStatement(
                'UPDATE purchasing.purchase_order SET status = :status, expected_total = :expected_total, confirmed_by = :confirmed_by, confirmed_at = :confirmed_at, closed_by = :closed_by, closed_at = :closed_at, closed_reason = :closed_reason, cancelled_by = :cancelled_by, cancelled_at = :cancelled_at, version = :version WHERE organization_id = :organization_id AND id = :id AND version = :expected_version',
                [
                    'status' => $data['status'],
                    'expected_total' => $data['expected_total'],
                    'confirmed_by' => $data['confirmed_by'],
                    'confirmed_at' => $data['confirmed_at'],
                    'closed_by' => $data['closed_by'],
                    'closed_at' => $data['closed_at'],
                    'closed_reason' => $data['closed_reason'],
                    'cancelled_by' => $data['cancelled_by'],
                    'cancelled_at' => $data['cancelled_at'],
                    'version' => $data['version'],
                    'organization_id' => $data['organization_id'],
                    'id' => $data['id'],
                    'expected_version' => $purchaseOrder->version() - 1,
                ],
            );
            if (1 !== $affected) {
                throw new LogicException('Purchase order was modified concurrently.');
            }
        }

        if (PurchaseOrderStatus::Draft === $purchaseOrder->status()) {
            $this->connection->delete('purchasing.purchase_order_line', [
                'organization_id' => $purchaseOrder->organizationId()->toString(),
                'purchase_order_id' => $purchaseOrder->id()->toString(),
            ]);
            foreach ($purchaseOrder->lines() as $line) {
                $this->connection->insert('purchasing.purchase_order_line', $this->lineData($purchaseOrder, $line));
            }

            return;
        }

        foreach ($purchaseOrder->lines() as $line) {
            $this->connection->executeStatement(
                'UPDATE purchasing.purchase_order_line SET received_quantity = ? WHERE organization_id = ? AND purchase_order_id = ? AND id = ?',
                [$line->receivedQuantity()->toString(), $purchaseOrder->organizationId()->toString(), $purchaseOrder->id()->toString(), $line->id()->toString()],
            );
        }
    }

    public function get(OrganizationId $organizationId, PurchaseOrderId $purchaseOrderId): PurchaseOrder
    {
        return $this->find($organizationId, $purchaseOrderId) ?? throw PurchaseOrderNotFound::withId($purchaseOrderId);
    }

    public function getForUpdate(OrganizationId $organizationId, PurchaseOrderId $purchaseOrderId): PurchaseOrder
    {
        $locked = $this->connection->fetchOne(
            'SELECT id FROM purchasing.purchase_order WHERE organization_id = ? AND id = ? FOR UPDATE',
            [$organizationId->toString(), $purchaseOrderId->toString()],
        );
        if (false === $locked) {
            throw PurchaseOrderNotFound::withId($purchaseOrderId);
        }

        return $this->get($organizationId, $purchaseOrderId);
    }

    public function find(OrganizationId $organizationId, PurchaseOrderId $purchaseOrderId): ?PurchaseOrder
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM purchasing.purchase_order WHERE organization_id = ? AND id = ?',
            [$organizationId->toString(), $purchaseOrderId->toString()],
        );
        if (false === $row) {
            return null;
        }
        $currency = Currency::fromCode((string) $row['currency']);
        $lineRows = $this->connection->fetchAllAssociative(
            'SELECT * FROM purchasing.purchase_order_line WHERE organization_id = ? AND purchase_order_id = ? ORDER BY id',
            [$organizationId->toString(), $purchaseOrderId->toString()],
        );

        return PurchaseOrder::reconstitute(
            $purchaseOrderId,
            $organizationId,
            StoreId::fromString((string) $row['destination_store_id'], $this->uuids),
            SupplierId::fromString((string) $row['supplier_id'], $this->uuids),
            PurchaseOrderNumber::fromString((string) $row['number']),
            PurchaseOrderStatus::from((string) $row['status']),
            $currency,
            $this->money((string) $row['expected_total'], $currency),
            ActorId::fromString((string) $row['created_by'], $this->uuids),
            new DateTimeImmutable((string) $row['created_at']),
            $this->actor($row['confirmed_by']),
            $this->date($row['confirmed_at']),
            $this->actor($row['closed_by']),
            $this->date($row['closed_at']),
            null === $row['closed_reason'] ? null : (string) $row['closed_reason'],
            $this->actor($row['cancelled_by']),
            $this->date($row['cancelled_at']),
            (int) $row['version'],
            array_map(fn(array $line): PurchaseOrderLine => $this->line($line, $currency), $lineRows),
        );
    }

    /** @return array<string, mixed> */
    private function orderData(PurchaseOrder $order): array
    {
        return [
            'id' => $order->id()->toString(),
            'organization_id' => $order->organizationId()->toString(),
            'destination_store_id' => $order->destinationStoreId()->toString(),
            'supplier_id' => $order->supplierId()->toString(),
            'number' => $order->number()->value(),
            'status' => $order->status()->value,
            'currency' => $order->currency()->code(),
            'expected_total' => $order->expectedTotal()->amount()->toString(),
            'created_by' => $order->createdBy()->toString(),
            'created_at' => $order->createdAt()->format(DATE_ATOM),
            'confirmed_by' => $order->confirmedBy()?->toString(),
            'confirmed_at' => $order->confirmedAt()?->format(DATE_ATOM),
            'closed_by' => $order->closedBy()?->toString(),
            'closed_at' => $order->closedAt()?->format(DATE_ATOM),
            'closed_reason' => $order->closedReason(),
            'cancelled_by' => $order->cancelledBy()?->toString(),
            'cancelled_at' => $order->cancelledAt()?->format(DATE_ATOM),
            'version' => $order->version(),
        ];
    }

    /** @return array<string, mixed> */
    private function lineData(PurchaseOrder $order, PurchaseOrderLine $line): array
    {
        return [
            'id' => $line->id()->toString(),
            'organization_id' => $order->organizationId()->toString(),
            'purchase_order_id' => $order->id()->toString(),
            'product_id' => $line->productId()->toString(),
            'product_packaging_id' => $line->productPackagingId()?->toString(),
            'entered_ordered_quantity' => $line->enteredOrderedQuantity()->toString(),
            'conversion_factor_snapshot' => $line->conversionFactorSnapshot()->toString(),
            'ordered_base_quantity' => $line->orderedBaseQuantity()->toString(),
            'unit_cost' => $line->unitCost()->amount()->toString(),
            'inventory_unit_cost' => $line->inventoryUnitCost()->amount()->toString(),
            'received_quantity' => $line->receivedQuantity()->toString(),
        ];
    }

    /** @param array<string, mixed> $row */
    private function line(array $row, Currency $currency): PurchaseOrderLine
    {
        $quantity = fn(string $field): Quantity => Quantity::fromString((string) $row[$field], $this->decimals);

        return new PurchaseOrderLine(
            PurchaseOrderLineId::fromString((string) $row['id'], $this->uuids),
            PurchaseOrderId::fromString((string) $row['purchase_order_id'], $this->uuids),
            ProductId::fromString((string) $row['product_id'], $this->uuids),
            null === $row['product_packaging_id'] ? null : ProductPackagingId::fromString((string) $row['product_packaging_id'], $this->uuids),
            $quantity('entered_ordered_quantity'),
            $quantity('conversion_factor_snapshot'),
            $quantity('ordered_base_quantity'),
            $this->money((string) $row['unit_cost'], $currency),
            $this->money((string) $row['inventory_unit_cost'], $currency),
            $quantity('received_quantity'),
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
