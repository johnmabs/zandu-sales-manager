<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Sales\Application;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Zandu\Modules\CashManagement\Application\Contract\{CashMovementRecorder,CashSalePaymentResult,RecordSalePayment};
use Zandu\Modules\Inventory\Application\Contract\{ConsumeStockForSale,InventoryStockConsumer,StockConsumptionResult};
use Zandu\Modules\Sales\Application\{CashPaymentService,CompleteSale,CompleteSaleService,InventoryConsumptionService};
use Zandu\Modules\Sales\Application\Contract\PaymentRecorder;
use Zandu\Modules\Sales\Domain\{Sale,SaleStatus};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Persistence\DoctrineTenantTransaction;
use Zandu\SharedKernel\Context\{ActorContext,ActorType};
use Zandu\SharedKernel\Identity\{ActorId,CashSessionId,OrganizationId,SaleId,StoreId};
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Money\{Currency,Money};
use Zandu\SharedKernel\Tenancy\TenantTransaction;

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
            public function recordCashSale(OrganizationId $organizationId, SaleId $saleId, Money $amount, ActorId $actorId): void
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
        $service = new CompleteSaleService($transaction, new InventoryConsumptionService($inventory), new CashPaymentService($cash), $payments);
        $this->expectException(RuntimeException::class);
        $service(new CompleteSale($sale, $this->money('10'), $this->id(CashSessionId::class), [], $this->actor(), new \DateTimeImmutable()));
        self::assertSame(SaleStatus::Draft, $sale->status());
    }

    private function sale(): Sale
    {
        $f = new SymfonyUuidFactory();
        return Sale::create($this->id(SaleId::class), $this->id(OrganizationId::class), $this->id(StoreId::class), 'XAF', $this->money('0'), $this->actor(), new \DateTimeImmutable());
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
