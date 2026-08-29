<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Infrastructure\Persistence;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use LogicException;
use Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection\GoodsReceiptCorrection;
use Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection\GoodsReceiptCorrectionLine;
use Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection\GoodsReceiptCorrectionNotFound;
use Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection\GoodsReceiptCorrectionRepository;
use Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection\GoodsReceiptCorrectionStatus;
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\GoodsReceiptCorrectionId;
use Zandu\SharedKernel\Identity\GoodsReceiptId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\UuidFactory;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class DbalGoodsReceiptCorrectionRepository implements GoodsReceiptCorrectionRepository
{
    public function __construct(private Connection $connection, private UuidFactory $uuids, private DecimalFactory $decimals) {}

    public function save(GoodsReceiptCorrection $correction): void
    {
        $data = $this->data($correction);
        $exists = false !== $this->connection->fetchOne('SELECT 1 FROM purchasing.goods_receipt_correction WHERE organization_id = ? AND id = ?', [$data['organization_id'], $data['id']]);
        if (!$exists) {
            $this->connection->insert('purchasing.goods_receipt_correction', $data);
        } else {
            $affected = $this->connection->executeStatement(
                'UPDATE purchasing.goods_receipt_correction SET status = :status, posted_by = :posted_by, posted_at = :posted_at, version = :version WHERE organization_id = :organization_id AND id = :id AND version = :expected_version',
                [
                    'status' => $data['status'],
                    'posted_by' => $data['posted_by'],
                    'posted_at' => $data['posted_at'],
                    'version' => $data['version'],
                    'organization_id' => $data['organization_id'],
                    'id' => $data['id'],
                    'expected_version' => $correction->version() - 1,
                ],
            );
            if (1 !== $affected) {
                throw new LogicException('Goods receipt correction was modified concurrently.');
            }
        }
        if (GoodsReceiptCorrectionStatus::Draft !== $correction->status()) {
            return;
        }
        $this->connection->delete('purchasing.goods_receipt_correction_line', ['organization_id' => $correction->organizationId()->toString(), 'correction_id' => $correction->id()->toString()]);
        foreach ($correction->lines() as $line) {
            $this->connection->insert('purchasing.goods_receipt_correction_line', [
                'organization_id' => $correction->organizationId()->toString(),
                'correction_id' => $correction->id()->toString(),
                'product_id' => $line->productId()->toString(),
                'original_received_quantity' => $line->originalReceivedQuantity()->toString(),
                'current_effective_quantity' => $line->currentEffectiveQuantity()->toString(),
                'corrected_received_quantity' => $line->correctedReceivedQuantity()->toString(),
                'difference' => $line->difference()->toString(),
            ]);
        }
    }

    public function get(OrganizationId $organizationId, GoodsReceiptCorrectionId $correctionId): GoodsReceiptCorrection
    {
        return $this->find($organizationId, $correctionId) ?? throw GoodsReceiptCorrectionNotFound::withId($correctionId);
    }

    public function getForUpdate(OrganizationId $organizationId, GoodsReceiptCorrectionId $correctionId): GoodsReceiptCorrection
    {
        $found = $this->connection->fetchOne('SELECT id FROM purchasing.goods_receipt_correction WHERE organization_id = ? AND id = ? FOR UPDATE', [$organizationId->toString(), $correctionId->toString()]);
        if (false === $found) {
            throw GoodsReceiptCorrectionNotFound::withId($correctionId);
        }
        return $this->get($organizationId, $correctionId);
    }

    public function find(OrganizationId $organizationId, GoodsReceiptCorrectionId $correctionId): ?GoodsReceiptCorrection
    {
        $row = $this->connection->fetchAssociative('SELECT * FROM purchasing.goods_receipt_correction WHERE organization_id = ? AND id = ?', [$organizationId->toString(), $correctionId->toString()]);
        if (false === $row) {
            return null;
        }
        $lines = $this->connection->fetchAllAssociative('SELECT * FROM purchasing.goods_receipt_correction_line WHERE organization_id = ? AND correction_id = ? ORDER BY product_id', [$organizationId->toString(), $correctionId->toString()]);
        return GoodsReceiptCorrection::reconstitute(
            $correctionId,
            $organizationId,
            GoodsReceiptId::fromString((string) $row['goods_receipt_id'], $this->uuids),
            (string) $row['reason'],
            GoodsReceiptCorrectionStatus::from((string) $row['status']),
            ActorId::fromString((string) $row['created_by'], $this->uuids),
            new DateTimeImmutable((string) $row['created_at']),
            $this->actor($row['posted_by']),
            $this->date($row['posted_at']),
            (int) $row['version'],
            array_map(fn(array $line): GoodsReceiptCorrectionLine => new GoodsReceiptCorrectionLine($correctionId, ProductId::fromString((string) $line['product_id'], $this->uuids), $this->quantity($line['original_received_quantity']), $this->quantity($line['current_effective_quantity']), $this->quantity($line['corrected_received_quantity'])), $lines),
        );
    }

    public function postedDifferenceByProduct(OrganizationId $organizationId, GoodsReceiptId $goodsReceiptId): array
    {
        $rows = $this->connection->fetchAllAssociative("SELECT l.product_id, SUM(l.difference) AS difference FROM purchasing.goods_receipt_correction_line l JOIN purchasing.goods_receipt_correction c ON c.organization_id = l.organization_id AND c.id = l.correction_id WHERE c.organization_id = ? AND c.goods_receipt_id = ? AND c.status = 'POSTED' GROUP BY l.product_id", [$organizationId->toString(), $goodsReceiptId->toString()]);
        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['product_id']] = $this->quantity($row['difference']);
        }
        return $result;
    }

    /** @return array<string, mixed> */
    private function data(GoodsReceiptCorrection $correction): array
    {
        return ['id' => $correction->id()->toString(), 'organization_id' => $correction->organizationId()->toString(), 'goods_receipt_id' => $correction->goodsReceiptId()->toString(), 'reason' => $correction->reason(), 'status' => $correction->status()->value, 'created_by' => $correction->createdBy()->toString(), 'created_at' => $correction->createdAt()->format(DATE_ATOM), 'posted_by' => $correction->postedBy()?->toString(), 'posted_at' => $correction->postedAt()?->format(DATE_ATOM), 'version' => $correction->version()];
    }
    private function quantity(mixed $value): Quantity
    {
        return Quantity::fromString((string) $value, $this->decimals);
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
