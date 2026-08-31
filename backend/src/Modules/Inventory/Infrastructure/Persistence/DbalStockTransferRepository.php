<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Infrastructure\Persistence;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use LogicException;
use Zandu\Modules\Inventory\Domain\StockTransfer\{StockTransfer, StockTransferLine, StockTransferRepository, StockTransferStatus};
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Identity\{ActorId, OrganizationId, ProductId, StockTransferId, StockTransferLineId, StoreId, UuidFactory};
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class DbalStockTransferRepository implements StockTransferRepository
{
    public function __construct(private Connection $db, private UuidFactory $ids, private DecimalFactory $decimals) {}

    public function save(StockTransfer $transfer): void
    {
        $data = $this->data($transfer);
        $exists = false !== $this->db->fetchOne('SELECT 1 FROM inventory.stock_transfer WHERE organization_id = ? AND id = ?', [$data['organization_id'], $data['id']]);
        if (!$exists) {
            $this->db->insert('inventory.stock_transfer', $data);
        } else {
            $affected = $this->db->executeStatement('UPDATE inventory.stock_transfer SET status=:status,shipped_by=:shipped_by,shipped_at=:shipped_at,received_by=:received_by,received_at=:received_at,cancellation_reason=:cancellation_reason,cancelled_by=:cancelled_by,cancelled_at=:cancelled_at,version=:version WHERE organization_id=:organization_id AND id=:id AND version=:expected_version', $data + ['expected_version' => $transfer->version() - 1]);
            if (1 !== $affected) {
                throw new LogicException('Stock transfer was modified concurrently.');
            }
        }
        if (StockTransferStatus::Draft === $transfer->status()) {
            $this->db->delete('inventory.stock_transfer_line', ['organization_id' => $data['organization_id'], 'stock_transfer_id' => $data['id']]);
            foreach ($transfer->lines() as $line) {
                $this->db->insert('inventory.stock_transfer_line', ['id' => $line->id()->toString(), 'organization_id' => $data['organization_id'], 'stock_transfer_id' => $data['id'], 'product_id' => $line->productId()->toString(), 'requested_quantity' => $line->requestedQuantity()->toString(), 'shipped_quantity' => $line->shippedQuantity()?->toString(), 'received_quantity' => $line->receivedQuantity()?->toString()]);
            }

            return;
        }
        foreach ($transfer->lines() as $line) {
            $affected = $this->db->update('inventory.stock_transfer_line', ['shipped_quantity' => $line->shippedQuantity()?->toString(), 'received_quantity' => $line->receivedQuantity()?->toString()], ['id' => $line->id()->toString(), 'organization_id' => $data['organization_id'], 'stock_transfer_id' => $data['id']]);
            if (1 !== $affected) {
                throw new LogicException('Stock transfer line was modified concurrently.');
            }
        }
    }

    public function get(OrganizationId $organizationId, StockTransferId $transferId): StockTransfer
    {
        return $this->find($organizationId, $transferId) ?? throw new LogicException('Stock transfer was not found.');
    }
    public function getForUpdate(OrganizationId $organizationId, StockTransferId $transferId): StockTransfer
    {
        if (false === $this->db->fetchOne('SELECT id FROM inventory.stock_transfer WHERE organization_id = ? AND id = ? FOR UPDATE', [$organizationId->toString(), $transferId->toString()])) {
            throw new LogicException('Stock transfer was not found.');
        }
        return $this->get($organizationId, $transferId);
    }
    public function find(OrganizationId $organizationId, StockTransferId $transferId): ?StockTransfer
    {
        $row = $this->db->fetchAssociative('SELECT * FROM inventory.stock_transfer WHERE organization_id = ? AND id = ?', [$organizationId->toString(), $transferId->toString()]);
        if (false === $row) {
            return null;
        }
        $lines = array_map(fn(array $line): StockTransferLine => new StockTransferLine(StockTransferLineId::fromString((string) $line['id'], $this->ids), $transferId, ProductId::fromString((string) $line['product_id'], $this->ids), $this->quantity($line['requested_quantity']), null === $line['shipped_quantity'] ? null : $this->quantity($line['shipped_quantity']), null === $line['received_quantity'] ? null : $this->quantity($line['received_quantity'])), $this->db->fetchAllAssociative('SELECT * FROM inventory.stock_transfer_line WHERE organization_id = ? AND stock_transfer_id = ? ORDER BY id', [$organizationId->toString(), $transferId->toString()]));
        return StockTransfer::reconstitute($transferId, $organizationId, StoreId::fromString((string) $row['source_store_id'], $this->ids), StoreId::fromString((string) $row['destination_store_id'], $this->ids), StockTransferStatus::from((string) $row['status']), ActorId::fromString((string) $row['created_by'], $this->ids), new DateTimeImmutable((string) $row['created_at']), $this->actor($row['shipped_by']), $this->date($row['shipped_at']), $this->actor($row['received_by']), $this->date($row['received_at']), null === $row['cancellation_reason'] ? null : (string) $row['cancellation_reason'], $this->actor($row['cancelled_by']), $this->date($row['cancelled_at']), (int) $row['version'], $lines);
    }
    /** @return array<string,mixed> */ private function data(StockTransfer $transfer): array
    {
        return ['id' => $transfer->id()->toString(), 'organization_id' => $transfer->organizationId()->toString(), 'source_store_id' => $transfer->sourceStoreId()->toString(), 'destination_store_id' => $transfer->destinationStoreId()->toString(), 'status' => $transfer->status()->value, 'created_by' => $transfer->createdBy()->toString(), 'created_at' => $transfer->createdAt()->format(DATE_ATOM), 'shipped_by' => $transfer->shippedBy()?->toString(), 'shipped_at' => $transfer->shippedAt()?->format(DATE_ATOM), 'received_by' => $transfer->receivedBy()?->toString(), 'received_at' => $transfer->receivedAt()?->format(DATE_ATOM), 'cancellation_reason' => $transfer->cancellationReason(), 'cancelled_by' => $transfer->cancelledBy()?->toString(), 'cancelled_at' => $transfer->cancelledAt()?->format(DATE_ATOM), 'version' => $transfer->version()];
    }
    private function quantity(mixed $value): Quantity
    {
        return Quantity::fromString((string) $value, $this->decimals);
    }
    private function actor(mixed $value): ?ActorId
    {
        return null === $value ? null : ActorId::fromString((string) $value, $this->ids);
    }
    private function date(mixed $value): ?DateTimeImmutable
    {
        return null === $value ? null : new DateTimeImmutable((string) $value);
    }
}
