<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Sales\Application;

use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Inventory\Application\Contract\{InventoryStockRestocker, RestockSaleReturn, StockRestockResult};
use Zandu\Modules\Organization\Application\Contract\{StoreBusinessContext, StoreBusinessContextProvider};
use Zandu\Modules\Sales\Application\{CompleteReturnSale, CompleteReturnSaleService, ReturnAmountCalculator};
use Zandu\Modules\Sales\Domain\{ReturnAmounts, ReturnSale, ReturnSaleLine, ReturnSaleRepository, ReturnSaleStatus, Sale, SaleLine, SaleLineCostSnapshot, SaleRepository, SalesRuleViolation};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Context\{ActorContext, ActorType};
use Zandu\SharedKernel\Identity\{ActorId, OrganizationId, ProductId, ProductPackagingId, ReturnSaleId, ReturnSaleLineId, SaleId, SaleLineId, StockId, StockMovementId, StoreId, UnitOfMeasureId};
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Money\{Currency, Money};
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class CompleteReturnSaleServiceTest extends TestCase
{
    public function testItCompletesAPartialReturnUsingTheStoreBusinessDate(): void
    {
        $sale = $this->completedSale();
        $candidate = $this->returnSale($sale, '1', '019a3300-0000-7000-8000-000000000020');
        $service = $this->service($sale, [$candidate]);

        $completed = $service(new CompleteReturnSale($candidate->id(), $this->actor()));

        self::assertSame(ReturnSaleStatus::Completed, $completed->status());
        self::assertSame('2026-08-29', $completed->businessDate());
        self::assertSame(3, $completed->version());
        self::assertSame('500.000000000000', $completed->lines()[0]->amounts()?->total()->amount()->toString());
    }

    public function testItRejectsAReturnWhoseSourceSaleIsNotCompleted(): void
    {
        $sale = $this->draftSale();
        $candidate = $this->returnSale($sale, '1', '019a3300-0000-7000-8000-000000000021');
        $service = $this->service($sale, [$candidate]);

        try {
            $service(new CompleteReturnSale($candidate->id(), $this->actor()));
            self::fail('A draft sale should not be returnable.');
        } catch (SalesRuleViolation $exception) {
            self::assertSame('SALE_NOT_RETURNABLE', $exception->errorCode());
        }
        self::assertSame(ReturnSaleStatus::Draft, $candidate->status());
    }

    public function testItRejectsCumulativeReturnsGreaterThanTheOriginalQuantity(): void
    {
        $sale = $this->completedSale();
        $existing = $this->returnSale($sale, '1.5', '019a3300-0000-7000-8000-000000000022');
        $existing->complete(
            $this->actor(),
            new DateTimeImmutable('2026-08-28T10:00:00Z'),
            '2026-08-28',
            $this->calculatedAmounts($existing, '0'),
        );
        $candidate = $this->returnSale($sale, '1', '019a3300-0000-7000-8000-000000000023');
        $service = $this->service($sale, [$existing, $candidate]);

        try {
            $service(new CompleteReturnSale($candidate->id(), $this->actor()));
            self::fail('Cumulative returns should not exceed the sold quantity.');
        } catch (SalesRuleViolation $exception) {
            self::assertSame('RETURN_QUANTITY_EXCEEDS_SOLD', $exception->errorCode());
        }
        self::assertSame(ReturnSaleStatus::Draft, $candidate->status());
    }

    public function testCancelledAndDraftReturnsDoNotConsumeTheReturnableQuantity(): void
    {
        $sale = $this->completedSale();
        $cancelled = $this->returnSale($sale, '2', '019a3300-0000-7000-8000-000000000024');
        $cancelled->cancel($this->actor(), new DateTimeImmutable());
        $otherDraft = $this->returnSale($sale, '2', '019a3300-0000-7000-8000-000000000025');
        $candidate = $this->returnSale($sale, '2', '019a3300-0000-7000-8000-000000000026');
        $service = $this->service($sale, [$cancelled, $otherDraft, $candidate]);

        $completed = $service(new CompleteReturnSale($candidate->id(), $this->actor()));

        self::assertSame(ReturnSaleStatus::Completed, $completed->status());
    }

    public function testItAllocatesTheExactRemainingAmountAcrossMultipleReturns(): void
    {
        $sale = $this->completedSale();
        $existing = $this->returnSale($sale, '0.5', '019a3300-0000-7000-8000-000000000030');
        $existing->complete(
            $this->actor(),
            new DateTimeImmutable('2026-08-28T10:00:00Z'),
            '2026-08-28',
            $this->calculatedAmounts($existing, '0'),
        );
        $candidate = $this->returnSale($sale, '1.5', '019a3300-0000-7000-8000-000000000031');

        $completed = $this->service($sale, [$existing, $candidate])(
            new CompleteReturnSale($candidate->id(), $this->actor()),
        );

        $existingAmounts = $existing->lines()[0]->amounts();
        $completedAmounts = $completed->lines()[0]->amounts();
        self::assertNotNull($existingAmounts);
        self::assertNotNull($completedAmounts);
        self::assertSame('250.000000000000', $existingAmounts->total()->amount()->toString());
        self::assertSame('750.000000000000', $completedAmounts->total()->amount()->toString());
        self::assertTrue($sale->lines()[0]->total()->equals(
            $existingAmounts->total()->add($completedAmounts->total()),
        ));
    }

    public function testItRestocksOnlyWithTheOriginalCostSnapshot(): void
    {
        $sale = $this->completedSale();
        $candidate = $this->returnSale($sale, '1', '019a3300-0000-7000-8000-000000000027', true, true);
        $inventory = $this->createMock(InventoryStockRestocker::class);
        $inventory->expects(self::once())->method('restockSaleReturn')->with(self::callback(static fn(RestockSaleReturn $request): bool => 1 === count($request->items)
            && '6.000000000000' === $request->items[0]['baseQuantity']->toString()
            && '400.000000000000' === $request->items[0]['originalUnitCost']->amount()->toString()))
            ->willReturn(new StockRestockResult($candidate->id(), false));

        $completed = $this->service($sale, [$candidate], $inventory)(new CompleteReturnSale($candidate->id(), $this->actor()));

        self::assertSame(ReturnSaleStatus::Completed, $completed->status());
    }

    public function testItRejectsRestockWithoutTheOriginalCostSnapshot(): void
    {
        $sale = $this->completedSale();
        $candidate = $this->returnSale($sale, '1', '019a3300-0000-7000-8000-000000000028', true, false);
        $inventory = $this->createMock(InventoryStockRestocker::class);
        $inventory->expects(self::never())->method('restockSaleReturn');

        try {
            $this->service($sale, [$candidate], $inventory)(new CompleteReturnSale($candidate->id(), $this->actor()));
            self::fail('A restock without original cost should be rejected.');
        } catch (SalesRuleViolation $exception) {
            self::assertSame('SALE_LINE_COST_SNAPSHOT_NOT_FOUND', $exception->errorCode());
        }
        self::assertSame(ReturnSaleStatus::Draft, $candidate->status());
    }

    public function testInventoryOrCostingFailureLeavesTheReturnUncompleted(): void
    {
        $sale = $this->completedSale();
        $candidate = $this->returnSale($sale, '1', '019a3300-0000-7000-8000-000000000029', true, true);
        $inventory = $this->createStub(InventoryStockRestocker::class);
        $inventory->method('restockSaleReturn')->willThrowException(new \RuntimeException('Injected return costing failure.'));

        try {
            $this->service($sale, [$candidate], $inventory)(new CompleteReturnSale($candidate->id(), $this->actor()));
            self::fail('A return costing failure should abort completion.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Injected return costing failure.', $exception->getMessage());
        }
        self::assertSame(ReturnSaleStatus::Draft, $candidate->status());
    }

    /** @param list<ReturnSale> $returns */
    private function service(Sale $sale, array $returns, ?InventoryStockRestocker $inventory = null): CompleteReturnSaleService
    {
        $sales = new class ($sale) implements SaleRepository {
            public int $lockCalls = 0;
            public function __construct(private Sale $sale) {}
            public function save(Sale $sale): void {}
            public function get(OrganizationId $organizationId, SaleId $saleId): Sale
            {
                return $this->sale;
            }
            public function getForUpdate(OrganizationId $organizationId, SaleId $saleId): Sale
            {
                ++$this->lockCalls;

                return $this->sale;
            }
        };
        $repository = new class ($returns) implements ReturnSaleRepository {
            /** @param list<ReturnSale> $returns */
            public function __construct(private array $returns) {}
            public function save(ReturnSale $returnSale): void {}
            public function get(OrganizationId $organizationId, ReturnSaleId $returnSaleId): ReturnSale
            {
                return $this->matching($returnSaleId);
            }
            public function getForUpdate(OrganizationId $organizationId, ReturnSaleId $returnSaleId): ReturnSale
            {
                return $this->matching($returnSaleId);
            }
            public function findBySale(OrganizationId $organizationId, SaleId $saleId): array
            {
                return array_values(array_filter($this->returns, fn(ReturnSale $return): bool => $return->saleId()->equals($saleId)));
            }
            private function matching(ReturnSaleId $id): ReturnSale
            {
                foreach ($this->returns as $return) {
                    if ($return->id()->equals($id)) {
                        return $return;
                    }
                }
                throw new LogicException('Return sale not found.');
            }
        };
        $transaction = new class implements TenantTransaction {
            public function transactional(OrganizationId $organizationId, callable $operation): mixed
            {
                return $operation();
            }
        };
        $stores = new class implements StoreBusinessContextProvider {
            public function provide(OrganizationId $organizationId, StoreId $storeId): StoreBusinessContext
            {
                return new StoreBusinessContext('Africa/Lagos', 'XAF');
            }
        };

        return new CompleteReturnSaleService(
            $transaction,
            $sales,
            $repository,
            new ReturnAmountCalculator(),
            $inventory ?? $this->createStub(InventoryStockRestocker::class),
            $stores,
            new FrozenClock(new DateTimeImmutable('2026-08-28T23:30:00Z')),
        );
    }

    /** @return array<string, ReturnAmounts> */
    private function calculatedAmounts(ReturnSale $return, string $previouslyReturnedBaseQuantity): array
    {
        $calculator = new ReturnAmountCalculator();
        $amounts = [];
        foreach ($return->lines() as $line) {
            $amounts[$line->id()->toString()] = $calculator->calculate(
                $line->originalLine(),
                $this->quantity($previouslyReturnedBaseQuantity),
                $line->baseReturnedQuantity(),
            );
        }

        return $amounts;
    }

    private function completedSale(): Sale
    {
        $sale = $this->draftSale();
        $sale->complete($this->actor(), new DateTimeImmutable('2026-08-28T09:00:00Z'), '2026-08-28');

        return $sale;
    }

    private function draftSale(): Sale
    {
        $currency = Currency::fromCode('XAF');
        $sale = Sale::create(
            $this->saleId(),
            $this->organizationId(),
            $this->storeId(),
            'XAF',
            Money::fromString('0', $currency, $this->decimals()),
            $this->actor(),
            new DateTimeImmutable('2026-08-28T08:00:00Z'),
        );
        $sale->addLine($this->saleLine($sale));

        return $sale;
    }

    private function returnSale(Sale $sale, string $quantity, string $id, bool $restock = false, bool $withCost = false): ReturnSale
    {
        $return = ReturnSale::create(
            ReturnSaleId::fromString($id, $this->uuids()),
            $this->organizationId(),
            $this->storeId(),
            $sale->id(),
            null,
            $this->actor(),
            new DateTimeImmutable('2026-08-28T10:00:00Z'),
        );
        $return->addLine(new ReturnSaleLine(
            ReturnSaleLineId::fromString(substr($id, 0, -2) . '3' . substr($id, -1), $this->uuids()),
            $sale->lines()[0],
            $withCost ? $this->costSnapshot($sale->lines()[0]->id()) : null,
            $this->quantity($quantity),
            $restock,
            null,
        ));

        return $return;
    }

    private function costSnapshot(SaleLineId $saleLineId): SaleLineCostSnapshot
    {
        return SaleLineCostSnapshot::capture(
            $this->organizationId(),
            $saleLineId,
            StockId::fromString('019a3300-0000-7000-8000-000000000011', $this->uuids()),
            StockMovementId::fromString('019a3300-0000-7000-8000-000000000012', $this->uuids()),
            $this->quantity('12'),
            Money::fromString('400', Currency::fromCode('XAF'), $this->decimals()),
            2,
            new DateTimeImmutable('2026-08-28T09:00:00Z'),
        );
    }

    private function saleLine(Sale $sale): SaleLine
    {
        $currency = Currency::fromCode('XAF');
        $zero = Money::fromString('0', $currency, $this->decimals());
        $total = Money::fromString('1000', $currency, $this->decimals());

        return new SaleLine(
            SaleLineId::fromString('019a3300-0000-7000-8000-000000000002', $this->uuids()),
            $sale->id(),
            ProductId::fromString('019a3300-0000-7000-8000-000000000003', $this->uuids()),
            ProductPackagingId::fromString('019a3300-0000-7000-8000-000000000004', $this->uuids()),
            'SKU',
            'Product',
            'PACK',
            'Pack',
            UnitOfMeasureId::fromString('019a3300-0000-7000-8000-000000000005', $this->uuids()),
            $this->quantity('2'),
            $this->quantity('6'),
            $this->quantity('12'),
            Money::fromString('500', $currency, $this->decimals()),
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
            ActorId::fromString('019a3300-0000-7000-8000-000000000006', $this->uuids()),
            $this->organizationId(),
            ActorType::User,
            CorrelationId::fromString('019a3300-0000-7000-8000-000000000007', $this->uuids()),
            new DateTimeImmutable('2026-08-28T08:00:00Z'),
        );
    }

    private function organizationId(): OrganizationId
    {
        return OrganizationId::fromString('019a3300-0000-7000-8000-000000000008', $this->uuids());
    }

    private function storeId(): StoreId
    {
        return StoreId::fromString('019a3300-0000-7000-8000-000000000009', $this->uuids());
    }

    private function saleId(): SaleId
    {
        return SaleId::fromString('019a3300-0000-7000-8000-000000000010', $this->uuids());
    }

    private function quantity(string $value): Quantity
    {
        return Quantity::fromString($value, $this->decimals());
    }

    private function uuids(): SymfonyUuidFactory
    {
        return new SymfonyUuidFactory();
    }

    private function decimals(): BrickDecimalFactory
    {
        return new BrickDecimalFactory();
    }
}
