<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Sales\Application;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Zandu\Modules\CashManagement\Application\Contract\{CashMovementRecorder,CashSalePaymentResult,RecordSalePayment};
use Zandu\Modules\Catalog\Application\Contract\{InventoryProductDescriptor, InventoryProductProvider};
use Zandu\Modules\Inventory\Application\Contract\{ConsumeStockForSale,InventoryStockConsumer,StockConsumptionResult};
use Zandu\Modules\Organization\Application\Contract\{StoreBusinessContext, StoreBusinessContextProvider};
use Zandu\Modules\Sales\Application\{CashPaymentService,CompleteSale,CompleteSaleService,InMemorySaleCompletionIdempotency,InventoryConsumptionService,SaleLineCostSnapshotService};
use Zandu\Modules\Sales\Application\Contract\PaymentRecorder;
use Zandu\Modules\Sales\Domain\{Sale,SaleLine,SaleLineCostSnapshot,SaleLineCostSnapshotRepository,SaleRepository,SaleStatus};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Identity\SymfonyUuidV7Generator;
use Zandu\Platform\Persistence\DoctrineTenantTransaction;
use Zandu\SharedKernel\Context\{ActorContext,ActorType};
use Zandu\SharedKernel\Identity\{ActorId,CashSessionId,OrganizationId,PaymentId,ProductId,ProductPackagingId,SaleId,SaleLineId,StoreId,UnitOfMeasureId};
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Messaging\{OutboxMessage, OutboxRepository};
use Zandu\SharedKernel\Money\{Currency,Money};
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;
use Zandu\Tests\Modules\Organization\Application\{AllowAllAuthorizationService, AllowAllOperationalGuard, RecordingSecurityAuditTrail};

final class CompleteSaleServiceTest extends TestCase
{
    public function testFailureRollsBackAndDoesNotCompleteSale(): void
    {
        $sale = $this->sale();
        $transaction = new class implements TenantTransaction {
            public function transactional(OrganizationId $organizationId, callable $operation): mixed
            {
                return $operation();
            }
        };
        $payments = new class implements PaymentRecorder {
            public function recordCashSale(OrganizationId $organizationId, SaleId $saleId, Money $amount, ActorId $actorId): PaymentId
            {
                throw new RuntimeException('payment failure');
            }
        };
        $cash = new class implements CashMovementRecorder {
            public function recordSalePayment(RecordSalePayment $request): CashSalePaymentResult
            {
                return new CashSalePaymentResult($request->saleId);
            }
        };
        $inventory = new class implements InventoryStockConsumer {
            public function consumeStockForSale(ConsumeStockForSale $request): StockConsumptionResult
            {
                return new StockConsumptionResult($request->saleId);
            }
        };
        $sales = new class ($sale) implements SaleRepository {
            public function __construct(private Sale $sale) {}
            public function save(Sale $sale): void {}
            public function get(OrganizationId $organizationId, SaleId $saleId): Sale
            {
                return $this->sale;
            }
            public function getForUpdate(OrganizationId $organizationId, SaleId $saleId): Sale
            {
                return $this->sale;
            }
        };
        $products = new class implements InventoryProductProvider {
            public function provide(OrganizationId $organizationId, ProductId $productId): InventoryProductDescriptor
            {
                return new InventoryProductDescriptor($productId, false, 'SERVICE', UnitOfMeasureId::fromString('0198ee09-1111-7111-8111-111111111111', new SymfonyUuidFactory()), 0);
            }
        };
        $stores = new class implements StoreBusinessContextProvider {
            public function provide(OrganizationId $organizationId, StoreId $storeId): StoreBusinessContext
            {
                return new StoreBusinessContext('Africa/Lagos', 'XAF');
            }
        };
        $outbox = new class implements OutboxRepository {
            public function append(OutboxMessage $message): void {}
        };
        $clock = new class implements Clock {
            public function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable('2026-08-27T23:30:00Z');
            }
        };
        $service = new CompleteSaleService(
            $transaction,
            $sales,
            new InventoryConsumptionService($inventory, $products),
            new SaleLineCostSnapshotService(new class implements SaleLineCostSnapshotRepository {
                public function append(SaleLineCostSnapshot $snapshot): void {}
                public function findBySaleLine(OrganizationId $organizationId, SaleLineId $saleLineId): ?SaleLineCostSnapshot
                {
                    return null;
                }
                public function findBySale(OrganizationId $organizationId, SaleId $saleId): array
                {
                    return [];
                }
            }),
            new CashPaymentService($cash),
            $payments,
            new InMemorySaleCompletionIdempotency(),
            new AllowAllAuthorizationService(),
            new AllowAllOperationalGuard(),
            $stores,
            new RecordingSecurityAuditTrail(),
            $outbox,
            new SymfonyUuidV7Generator(),
            $clock,
        );
        $this->expectException(RuntimeException::class);
        $service(new CompleteSale($sale->id(), $this->money('10'), $this->id(CashSessionId::class), $this->actor(), 'checkout-1'));
        self::assertSame(SaleStatus::Draft, $sale->status());
    }

    private function sale(): Sale
    {
        $f = new SymfonyUuidFactory();
        $sale = Sale::create($this->id(SaleId::class), $this->id(OrganizationId::class), $this->id(StoreId::class), 'XAF', $this->money('0'), $this->actor(), new \DateTimeImmutable());
        $quantity = Quantity::fromString('1', new BrickDecimalFactory());
        $sale->addLine(new SaleLine(
            SaleLineId::fromString('0198ee07-1111-7111-8111-111111111111', new SymfonyUuidFactory()),
            $sale->id(),
            ProductId::fromString('0198ee08-1111-7111-8111-111111111111', new SymfonyUuidFactory()),
            ProductPackagingId::fromString('0198ee0a-1111-7111-8111-111111111111', new SymfonyUuidFactory()),
            'P1',
            'Product',
            'UNIT',
            'Unit',
            UnitOfMeasureId::fromString('0198ee09-1111-7111-8111-111111111111', new SymfonyUuidFactory()),
            $quantity,
            $quantity,
            $quantity,
            $this->money('10'),
            null,
            null,
            $this->money('0'),
            $this->money('10'),
            $this->money('0'),
            $this->money('10'),
            $this->money('10'),
        ));

        return $sale;
    }
    private function money(string $amount): Money
    {
        return Money::fromString($amount, Currency::fromCode('XAF'), new BrickDecimalFactory());
    }
    private function actor(): ActorContext
    {
        $f = new SymfonyUuidFactory();
        return new ActorContext(ActorId::fromString('0198ee04-1111-7111-8111-111111111111', $f), $this->id(OrganizationId::class), ActorType::User, CorrelationId::fromString('0198ee05-1111-7111-8111-111111111111', $f), new \DateTimeImmutable());
    }
    private function id(string $type): mixed
    {
        $f = new SymfonyUuidFactory();
        $map = [SaleId::class => '0198ee01-1111-7111-8111-111111111111', OrganizationId::class => '0198ee02-1111-7111-8111-111111111111', StoreId::class => '0198ee03-1111-7111-8111-111111111111', CashSessionId::class => '0198ee06-1111-7111-8111-111111111111'];
        return $type::fromString($map[$type], $f);
    }
}
