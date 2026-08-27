<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Infrastructure\Persistence;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use LogicException;
use Zandu\Modules\Sales\Domain\{Sale,SaleLine,SaleRepository,SaleStatus};
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Identity\{ActorId,OrganizationId,ProductId,ProductPackagingId,SaleId,SaleLineId,StoreId,UnitOfMeasureId,UuidFactory};
use Zandu\SharedKernel\Money\{Currency,Money};
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class DbalSaleRepository implements SaleRepository
{
    public function __construct(
        private Connection $connection,
        private UuidFactory $uuids,
        private DecimalFactory $decimals,
    ) {}

    public function save(Sale $sale): void
    {
        $exists = false !== $this->connection->fetchOne(
            'SELECT 1 FROM sales.sale WHERE organization_id = ? AND id = ?',
            [$sale->organizationId()->toString(), $sale->id()->toString()],
        );
        $data = $this->saleData($sale);

        if (!$exists) {
            $this->connection->insert('sales.sale', $data);
        } else {
            $affected = $this->connection->executeStatement(
                'UPDATE sales.sale SET status = :status, subtotal = :subtotal, discount_total = :discount_total, tax_total = :tax_total, total = :total, business_date = :business_date, completed_by = :completed_by, completed_at = :completed_at, cancelled_by = :cancelled_by, cancelled_at = :cancelled_at, version = :version WHERE organization_id = :organization_id AND id = :id AND version = :expected_version',
                [
                    'status' => $data['status'], 'subtotal' => $data['subtotal'], 'discount_total' => $data['discount_total'], 'tax_total' => $data['tax_total'], 'total' => $data['total'],
                    'business_date' => $data['business_date'], 'completed_by' => $data['completed_by'], 'completed_at' => $data['completed_at'], 'cancelled_by' => $data['cancelled_by'], 'cancelled_at' => $data['cancelled_at'], 'version' => $data['version'],
                    'organization_id' => $data['organization_id'], 'id' => $data['id'], 'expected_version' => $sale->version() - 1,
                ],
            );
            if (1 !== $affected) {
                throw new LogicException('Sale was modified concurrently.');
            }
        }

        $this->connection->delete('sales.sale_line', [
            'organization_id' => $sale->organizationId()->toString(),
            'sale_id' => $sale->id()->toString(),
        ]);
        foreach ($sale->lines() as $index => $line) {
            $this->connection->executeStatement(
                'INSERT INTO sales.sale_line (id, organization_id, sale_id, line_number, product_id, product_packaging_id, product_code_snapshot, product_name_snapshot, packaging_code_snapshot, packaging_name_snapshot, unit_id_snapshot, entered_quantity, conversion_factor_snapshot, base_quantity, unit_price, price_list_id, product_price_id, discount_amount, taxable_amount, tax_amount, subtotal, total, source_versions) VALUES (:id, :organization_id, :sale_id, :line_number, :product_id, :product_packaging_id, :product_code_snapshot, :product_name_snapshot, :packaging_code_snapshot, :packaging_name_snapshot, :unit_id_snapshot, :entered_quantity, :conversion_factor_snapshot, :base_quantity, :unit_price, :price_list_id, :product_price_id, :discount_amount, :taxable_amount, :tax_amount, :subtotal, :total, CAST(:source_versions AS JSONB))',
                $this->lineData($sale, $line, $index + 1),
            );
        }
    }

    public function get(OrganizationId $organizationId, SaleId $saleId): Sale
    {
        return $this->load($organizationId, $saleId, false);
    }

    public function getForUpdate(OrganizationId $organizationId, SaleId $saleId): Sale
    {
        return $this->load($organizationId, $saleId, true);
    }

    private function load(OrganizationId $organizationId, SaleId $saleId, bool $forUpdate): Sale
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM sales.sale WHERE organization_id = ? AND id = ?' . ($forUpdate ? ' FOR UPDATE' : ''),
            [$organizationId->toString(), $saleId->toString()],
        );
        if (false === $row) {
            throw new LogicException('Sale not found.');
        }
        $lineRows = $this->connection->fetchAllAssociative(
            'SELECT * FROM sales.sale_line WHERE organization_id = ? AND sale_id = ? ORDER BY line_number',
            [$organizationId->toString(), $saleId->toString()],
        );
        $currency = Currency::fromCode((string) $row['currency']);

        return Sale::reconstitute(
            $saleId,
            $organizationId,
            StoreId::fromString((string) $row['store_id'], $this->uuids),
            SaleStatus::from((string) $row['status']),
            $currency->code(),
            $this->money((string) $row['subtotal'], $currency),
            $this->money((string) $row['discount_total'], $currency),
            $this->money((string) $row['tax_total'], $currency),
            $this->money((string) $row['total'], $currency),
            null === $row['business_date'] ? null : (string) $row['business_date'],
            ActorId::fromString((string) $row['created_by'], $this->uuids),
            new DateTimeImmutable((string) $row['created_at']),
            $this->actor($row['completed_by'] ?? null),
            $this->date($row['completed_at'] ?? null),
            $this->actor($row['cancelled_by'] ?? null),
            $this->date($row['cancelled_at'] ?? null),
            (int) $row['version'],
            array_map(fn(array $line): SaleLine => $this->line($line, $currency), $lineRows),
        );
    }

    /** @return array<string,mixed> */
    private function saleData(Sale $sale): array
    {
        return [
            'id' => $sale->id()->toString(),
            'organization_id' => $sale->organizationId()->toString(),
            'store_id' => $sale->storeId()->toString(),
            'status' => $sale->status()->value,
            'currency' => $sale->currency(),
            'subtotal' => $sale->subtotal()->amount()->toString(),
            'discount_total' => $sale->discountTotal()->amount()->toString(),
            'tax_total' => $sale->taxTotal()->amount()->toString(),
            'total' => $sale->total()->amount()->toString(),
            'business_date' => $sale->businessDate(),
            'created_by' => $sale->createdBy()->toString(),
            'created_at' => $sale->createdAt()->format(DATE_ATOM),
            'completed_by' => $sale->completedBy()?->toString(),
            'completed_at' => $sale->completedAt()?->format(DATE_ATOM),
            'cancelled_by' => $sale->cancelledBy()?->toString(),
            'cancelled_at' => $sale->cancelledAt()?->format(DATE_ATOM),
            'version' => $sale->version(),
        ];
    }

    /** @return array<string,mixed> */
    private function lineData(Sale $sale, SaleLine $line, int $lineNumber): array
    {
        return [
            'id' => $line->id()->toString(), 'organization_id' => $sale->organizationId()->toString(), 'sale_id' => $sale->id()->toString(), 'line_number' => $lineNumber,
            'product_id' => $line->productId()->toString(), 'product_packaging_id' => $line->productPackagingId()->toString(), 'product_code_snapshot' => $line->productCodeSnapshot(), 'product_name_snapshot' => $line->productNameSnapshot(),
            'packaging_code_snapshot' => $line->packagingCodeSnapshot(), 'packaging_name_snapshot' => $line->packagingNameSnapshot(), 'unit_id_snapshot' => $line->unitIdSnapshot()->toString(),
            'entered_quantity' => $line->enteredQuantity()->toString(), 'conversion_factor_snapshot' => $line->conversionFactorSnapshot()->toString(), 'base_quantity' => $line->baseQuantity()->toString(),
            'unit_price' => $line->unitPrice()->amount()->toString(), 'price_list_id' => $line->priceListId(), 'product_price_id' => $line->productPriceId(), 'discount_amount' => $line->discountAmount()->amount()->toString(),
            'taxable_amount' => $line->taxableAmount()->amount()->toString(), 'tax_amount' => $line->taxAmount()->amount()->toString(), 'subtotal' => $line->subtotal()->amount()->toString(), 'total' => $line->total()->amount()->toString(),
            'source_versions' => json_encode($line->sourceVersions(), JSON_THROW_ON_ERROR),
        ];
    }

    /** @param array<string,mixed> $row */
    private function line(array $row, Currency $currency): SaleLine
    {
        $quantity = fn(string $field): Quantity => Quantity::fromString((string) $row[$field], $this->decimals);
        $money = fn(string $field): Money => $this->money((string) $row[$field], $currency);
        $versions = json_decode((string) $row['source_versions'], true, flags: JSON_THROW_ON_ERROR);

        return new SaleLine(
            SaleLineId::fromString((string) $row['id'], $this->uuids),
            SaleId::fromString((string) $row['sale_id'], $this->uuids),
            ProductId::fromString((string) $row['product_id'], $this->uuids),
            ProductPackagingId::fromString((string) $row['product_packaging_id'], $this->uuids),
            null === $row['product_code_snapshot'] ? null : (string) $row['product_code_snapshot'],
            null === $row['product_name_snapshot'] ? null : (string) $row['product_name_snapshot'],
            (string) $row['packaging_code_snapshot'],
            null === $row['packaging_name_snapshot'] ? null : (string) $row['packaging_name_snapshot'],
            UnitOfMeasureId::fromString((string) $row['unit_id_snapshot'], $this->uuids),
            $quantity('entered_quantity'),
            $quantity('conversion_factor_snapshot'),
            $quantity('base_quantity'),
            $money('unit_price'),
            null === $row['price_list_id'] ? null : (string) $row['price_list_id'],
            null === $row['product_price_id'] ? null : (string) $row['product_price_id'],
            $money('discount_amount'),
            $money('taxable_amount'),
            $money('tax_amount'),
            $money('subtotal'),
            $money('total'),
            is_array($versions) ? $versions : [],
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
