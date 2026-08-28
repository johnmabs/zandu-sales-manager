<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Sales\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Inventory\Application\Contract\{CostedStockConsumption, StockConsumptionResult};
use Zandu\Modules\Sales\Application\SaleLineCostSnapshotService;
use Zandu\Modules\Sales\Domain\{Sale, SaleLine, SaleLineCostSnapshot, SaleLineCostSnapshotRepository, SalesRuleViolation};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Context\{ActorContext, ActorType};
use Zandu\SharedKernel\Identity\{ActorId, OrganizationId, ProductId, ProductPackagingId, SaleId, SaleLineId, StockId, StockMovementId, StoreId, UnitOfMeasureId};
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Money\{Currency, Money};
use Zandu\SharedKernel\Quantity\Quantity;

final class SaleLineCostSnapshotServiceTest extends TestCase
{
    private SymfonyUuidFactory $uuids;
    private BrickDecimalFactory $decimals;

    protected function setUp(): void
    {
        $this->uuids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
    }

    public function testItCapturesOneImmutableCostSnapshotPerTrackedSaleLine(): void
    {
        $sale = $this->sale();
        $sale->addLine($this->line($sale, '0198f902-1111-7111-8111-111111111111', '0198f903-1111-7111-8111-111111111111', '1'));
        $sale->addLine($this->line($sale, '0198f904-1111-7111-8111-111111111111', '0198f903-1111-7111-8111-111111111111', '2'));
        $sale->addLine($this->line($sale, '0198f905-1111-7111-8111-111111111111', '0198f906-1111-7111-8111-111111111111', '1'));
        $repository = new RecordingSaleLineCostSnapshotRepository();
        $service = new SaleLineCostSnapshotService($repository);

        $service->capture($sale, $this->consumption($sale, '3'));

        self::assertCount(2, $repository->snapshots);
        self::assertSame('1', $repository->snapshots[0]->quantity()->toString());
        self::assertSame('400.000000000000', $repository->snapshots[0]->unitCost()->amount()->toString());
        self::assertSame('400.000000', $repository->snapshots[0]->totalCost()->amount()->toString());
        self::assertSame('2', $repository->snapshots[1]->quantity()->toString());
        self::assertSame('800.000000', $repository->snapshots[1]->totalCost()->amount()->toString());
        self::assertSame(2, $repository->snapshots[1]->valuationVersion());
        self::assertSame('0198f909-1111-7111-8111-111111111111', $repository->snapshots[1]->stockMovementId()->toString());
    }

    public function testItRejectsAConsumptionQuantityMismatchBeforeAppending(): void
    {
        $sale = $this->sale();
        $sale->addLine($this->line($sale, '0198f902-1111-7111-8111-111111111111', '0198f903-1111-7111-8111-111111111111', '1'));
        $repository = new RecordingSaleLineCostSnapshotRepository();

        try {
            (new SaleLineCostSnapshotService($repository))->capture($sale, $this->consumption($sale, '2'));
            self::fail('Mismatched stock consumption should be rejected.');
        } catch (SalesRuleViolation $violation) {
            self::assertSame('SALE_COST_SNAPSHOT_MISMATCH', $violation->errorCode());
        }

        self::assertSame([], $repository->snapshots);
    }

    private function consumption(Sale $sale, string $quantity): StockConsumptionResult
    {
        $product = ProductId::fromString('0198f903-1111-7111-8111-111111111111', $this->uuids);

        return new StockConsumptionResult($sale->id(), false, [new CostedStockConsumption(
            $product,
            StockId::fromString('0198f908-1111-7111-8111-111111111111', $this->uuids),
            StockMovementId::fromString('0198f909-1111-7111-8111-111111111111', $this->uuids),
            $this->quantity($quantity),
            $this->money('400'),
            $this->money((string) (400 * (int) $quantity)),
            2,
            new DateTimeImmutable('2026-08-28T08:00:00+01:00'),
        )]);
    }

    private function sale(): Sale
    {
        return Sale::create(
            SaleId::fromString('0198f901-1111-7111-8111-111111111111', $this->uuids),
            $this->organization(),
            StoreId::fromString('0198f907-1111-7111-8111-111111111111', $this->uuids),
            'XAF',
            $this->money('0'),
            $this->actor(),
            new DateTimeImmutable('2026-08-28T07:00:00Z'),
        );
    }

    private function line(Sale $sale, string $lineId, string $productId, string $quantity): SaleLine
    {
        $value = $this->quantity($quantity);
        $zero = $this->money('0');
        $total = $this->money((string) (1000 * (int) $quantity));

        return new SaleLine(
            SaleLineId::fromString($lineId, $this->uuids),
            $sale->id(),
            ProductId::fromString($productId, $this->uuids),
            ProductPackagingId::fromString('0198f90a-1111-7111-8111-111111111111', $this->uuids),
            'SKU',
            'Product',
            'EA',
            'Each',
            UnitOfMeasureId::fromString('0198f90b-1111-7111-8111-111111111111', $this->uuids),
            $value,
            $this->quantity('1'),
            $value,
            $this->money('1000'),
            null,
            null,
            $zero,
            $total,
            $zero,
            $total,
            $total,
        );
    }

    private function actor(): ActorContext
    {
        return new ActorContext(
            ActorId::fromString('0198f90c-1111-7111-8111-111111111111', $this->uuids),
            $this->organization(),
            ActorType::User,
            CorrelationId::fromString('0198f90d-1111-7111-8111-111111111111', $this->uuids),
            new DateTimeImmutable('2026-08-28T07:00:00Z'),
        );
    }

    private function organization(): OrganizationId
    {
        return OrganizationId::fromString('0198f90e-1111-7111-8111-111111111111', $this->uuids);
    }

    private function quantity(string $value): Quantity
    {
        return Quantity::fromString($value, $this->decimals);
    }

    private function money(string $value): Money
    {
        return Money::fromString($value, Currency::fromCode('XAF'), $this->decimals);
    }
}

final class RecordingSaleLineCostSnapshotRepository implements SaleLineCostSnapshotRepository
{
    /** @var list<SaleLineCostSnapshot> */
    public array $snapshots = [];

    public function append(SaleLineCostSnapshot $snapshot): void
    {
        $this->snapshots[] = $snapshot;
    }

    public function findBySaleLine(OrganizationId $organizationId, SaleLineId $saleLineId): ?SaleLineCostSnapshot
    {
        return null;
    }

    public function findBySale(OrganizationId $organizationId, SaleId $saleId): array
    {
        return [];
    }
}
