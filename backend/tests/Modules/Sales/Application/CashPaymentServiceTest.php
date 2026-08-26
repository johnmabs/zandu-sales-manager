<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Sales\Application;

use PHPUnit\Framework\TestCase;
use Zandu\Modules\CashManagement\Application\Contract\{CashMovementRecorder,CashSalePaymentResult,RecordSalePayment};
use Zandu\Modules\Sales\Application\CashPaymentService;
use Zandu\Modules\Sales\Domain\Sale;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Context\{ActorContext,ActorType};
use Zandu\SharedKernel\Identity\{ActorId,CashSessionId,CorrelationId,OrganizationId,SaleId,StoreId};
use Zandu\SharedKernel\Messaging\CorrelationId as MessageCorrelationId;
use Zandu\SharedKernel\Money\{Currency,Money};

final class CashPaymentServiceTest extends TestCase
{
    public function testRecordsPaymentThroughCashContract(): void
    {
        $recorder = new class implements CashMovementRecorder {
            public ?RecordSalePayment $request = null;
            public function recordSalePayment(RecordSalePayment $request): CashSalePaymentResult
            {
                $this->request = $request;
                return new CashSalePaymentResult($request->saleId);
            }
        };
        $f = new SymfonyUuidFactory();
        $sale = Sale::create(SaleId::fromString('0198ed01-1111-7111-8111-111111111111', $f), OrganizationId::fromString('0198ed02-1111-7111-8111-111111111111', $f), StoreId::fromString('0198ed03-1111-7111-8111-111111111111', $f), 'XAF', Money::fromString('0', Currency::fromCode('XAF'), new BrickDecimalFactory()), new ActorContext(ActorId::fromString('0198ed04-1111-7111-8111-111111111111', $f), OrganizationId::fromString('0198ed02-1111-7111-8111-111111111111', $f), ActorType::User, MessageCorrelationId::fromString('0198ed05-1111-7111-8111-111111111111', $f), new \DateTimeImmutable()), new \DateTimeImmutable());
        $result = (new CashPaymentService($recorder))->record($sale, CashSessionId::fromString('0198ed06-1111-7111-8111-111111111111', $f), Money::fromString('10', Currency::fromCode('XAF'), new BrickDecimalFactory()));
        self::assertSame($sale->id()->toString(), $result->saleId->toString());
        self::assertSame($sale->storeId()->toString(), $recorder->request?->storeId->toString());
    }
}
