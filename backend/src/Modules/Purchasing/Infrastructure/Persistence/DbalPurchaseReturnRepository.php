<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Infrastructure\Persistence;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use LogicException;
use Zandu\Modules\Purchasing\Domain\PurchaseReturn\{PurchaseReturn, PurchaseReturnLine, PurchaseReturnNotFound, PurchaseReturnRepository, PurchaseReturnStatus};
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Identity\{ActorId, GoodsReceiptId, GoodsReceiptLineId, OrganizationId, ProductId, PurchaseOrderId, PurchaseReturnId, PurchaseReturnLineId, StoreId, SupplierId, UuidFactory};
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class DbalPurchaseReturnRepository implements PurchaseReturnRepository
{
    public function __construct(private Connection $db, private UuidFactory $ids, private DecimalFactory $decimals) {}
    public function save(PurchaseReturn $return): void
    {
        $data = $this->data($return);
        $exists = false !== $this->db->fetchOne('SELECT 1 FROM purchasing.purchase_return WHERE organization_id=? AND id=?', [$data['organization_id'],$data['id']]);
        if (!$exists) {
            $this->db->insert('purchasing.purchase_return', $data);
        } else {
            $affected = $this->db->executeStatement('UPDATE purchasing.purchase_return SET status=:status,shipped_by=:shipped_by,shipped_at=:shipped_at,cancelled_by=:cancelled_by,cancelled_at=:cancelled_at,version=:version WHERE organization_id=:organization_id AND id=:id AND version=:expected_version', ['status' => $data['status'],'shipped_by' => $data['shipped_by'],'shipped_at' => $data['shipped_at'],'cancelled_by' => $data['cancelled_by'],'cancelled_at' => $data['cancelled_at'],'version' => $data['version'],'organization_id' => $data['organization_id'],'id' => $data['id'],'expected_version' => $return->version() - 1]);
            if (1 !== $affected) {
                throw new LogicException('Purchase return was modified concurrently.');
            }
        }
        if (PurchaseReturnStatus::Draft !== $return->status()) {
            return;
        }
        $this->db->delete('purchasing.purchase_return_line', ['organization_id' => $return->organizationId()->toString(),'purchase_return_id' => $return->id()->toString()]);
        foreach ($return->lines() as $line) {
            $this->db->insert('purchasing.purchase_return_line', ['id' => $line->id()->toString(),'organization_id' => $return->organizationId()->toString(),'purchase_return_id' => $return->id()->toString(),'product_id' => $line->productId()->toString(),'base_quantity' => $line->baseQuantity()->toString(),'goods_receipt_line_id' => $line->goodsReceiptLineId()?->toString()]);
        }
    }
    public function get(OrganizationId $organizationId, PurchaseReturnId $returnId): PurchaseReturn
    {
        return $this->find($organizationId, $returnId) ?? throw PurchaseReturnNotFound::withId($returnId);
    }
    public function getForUpdate(OrganizationId $organizationId, PurchaseReturnId $returnId): PurchaseReturn
    {
        if (false === $this->db->fetchOne('SELECT id FROM purchasing.purchase_return WHERE organization_id=? AND id=? FOR UPDATE', [$organizationId->toString(),$returnId->toString()])) {
            throw PurchaseReturnNotFound::withId($returnId);
        }
        return $this->get($organizationId, $returnId);
    }
    public function find(OrganizationId $organizationId, PurchaseReturnId $returnId): ?PurchaseReturn
    {
        $row = $this->db->fetchAssociative('SELECT * FROM purchasing.purchase_return WHERE organization_id=? AND id=?', [$organizationId->toString(),$returnId->toString()]);
        if (false === $row) {
            return null;
        }
        $lines = $this->db->fetchAllAssociative('SELECT * FROM purchasing.purchase_return_line WHERE organization_id=? AND purchase_return_id=? ORDER BY id', [$organizationId->toString(),$returnId->toString()]);
        return PurchaseReturn::reconstitute($returnId, $organizationId, StoreId::fromString((string) $row['source_store_id'], $this->ids), SupplierId::fromString((string) $row['supplier_id'], $this->ids), null === $row['goods_receipt_id'] ? null : GoodsReceiptId::fromString((string) $row['goods_receipt_id'], $this->ids), null === $row['purchase_order_id'] ? null : PurchaseOrderId::fromString((string) $row['purchase_order_id'], $this->ids), PurchaseReturnStatus::from((string) $row['status']), (string) $row['reason'], ActorId::fromString((string) $row['created_by'], $this->ids), new DateTimeImmutable((string) $row['created_at']), $this->actor($row['shipped_by']), $this->date($row['shipped_at']), $this->actor($row['cancelled_by']), $this->date($row['cancelled_at']), (int) $row['version'], array_map(fn(array $line): PurchaseReturnLine => new PurchaseReturnLine(PurchaseReturnLineId::fromString((string) $line['id'], $this->ids), $returnId, ProductId::fromString((string) $line['product_id'], $this->ids), $this->quantity($line['base_quantity']), null === $line['goods_receipt_line_id'] ? null : GoodsReceiptLineId::fromString((string) $line['goods_receipt_line_id'], $this->ids)), $lines));
    }
    public function shippedQuantityByProduct(OrganizationId $organizationId, GoodsReceiptId $goodsReceiptId): array
    {
        $rows = $this->db->fetchAllAssociative("SELECT l.product_id,SUM(l.base_quantity) quantity FROM purchasing.purchase_return_line l JOIN purchasing.purchase_return r ON r.organization_id=l.organization_id AND r.id=l.purchase_return_id WHERE r.organization_id=? AND r.goods_receipt_id=? AND r.status='SHIPPED' GROUP BY l.product_id", [$organizationId->toString(),$goodsReceiptId->toString()]);
        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['product_id']] = $this->quantity($row['quantity']);
        } return $result;
    }
    /** @return array<string,mixed> */ private function data(PurchaseReturn $r): array
    {
        return ['id' => $r->id()->toString(),'organization_id' => $r->organizationId()->toString(),'source_store_id' => $r->sourceStoreId()->toString(),'supplier_id' => $r->supplierId()->toString(),'goods_receipt_id' => $r->goodsReceiptId()?->toString(),'purchase_order_id' => $r->purchaseOrderId()?->toString(),'status' => $r->status()->value,'reason' => $r->reason(),'created_by' => $r->createdBy()->toString(),'created_at' => $r->createdAt()->format(DATE_ATOM),'shipped_by' => $r->shippedBy()?->toString(),'shipped_at' => $r->shippedAt()?->format(DATE_ATOM),'cancelled_by' => $r->cancelledBy()?->toString(),'cancelled_at' => $r->cancelledAt()?->format(DATE_ATOM),'version' => $r->version()];
    }
    private function quantity(mixed $v): Quantity
    {
        return Quantity::fromString((string) $v, $this->decimals);
    } private function actor(mixed $v): ?ActorId
    {
        return null === $v ? null : ActorId::fromString((string) $v, $this->ids);
    } private function date(mixed $v): ?DateTimeImmutable
    {
        return null === $v ? null : new DateTimeImmutable((string) $v);
    }
}
