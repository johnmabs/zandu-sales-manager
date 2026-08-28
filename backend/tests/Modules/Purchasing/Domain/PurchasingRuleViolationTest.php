<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Purchasing\Domain;

use PHPUnit\Framework\TestCase;
use Zandu\Modules\Purchasing\Domain\PurchasingRuleViolation;
use Zandu\SharedKernel\Error\CodedDomainException;

final class PurchasingRuleViolationTest extends TestCase
{
    public function testItExposesAStableCodeAndPublicMessage(): void
    {
        $violation = PurchasingRuleViolation::with(
            'PURCHASE_ORDER_NOT_FOUND',
            'Purchase order not found.',
        );

        self::assertInstanceOf(CodedDomainException::class, $violation);
        self::assertSame('PURCHASE_ORDER_NOT_FOUND', $violation->errorCode());
        self::assertSame('Purchase order not found.', $violation->publicMessage());
        self::assertSame($violation->publicMessage(), $violation->getMessage());
    }
}
