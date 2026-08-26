<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\CashManagement\Application\Contract;

use PHPUnit\Framework\TestCase;
use Zandu\Modules\CashManagement\Application\Contract\{CashMovementRecorder,CashSalePaymentResult,RecordSalePayment};

final class CashMovementRecorderContractTest extends TestCase
{
    public function testContractIsVersionedAndExposesIdempotentResult(): void
    {
        self::assertTrue(method_exists(CashMovementRecorder::class, 'recordSalePayment'));
        self::assertSame(1, CashSalePaymentResult::CONTRACT_VERSION);
        $saleId = \Zandu\SharedKernel\Identity\SaleId::fromString('00000000-0000-7000-8000-000000000001', new \Zandu\Platform\Identity\SymfonyUuidFactory());
        $result = new CashSalePaymentResult($saleId, true);
        self::assertTrue($result->alreadyRecorded);
        self::assertSame($saleId, $result->saleId);
        self::assertSame(6, (new \ReflectionClass(RecordSalePayment::class))->getConstructor()->getNumberOfParameters());
    }
}
