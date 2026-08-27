<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\InventoryCosting\Domain;

use PHPUnit\Framework\TestCase;
use Zandu\Modules\InventoryCosting\Domain\InventoryCostingRuleViolation;
use Zandu\SharedKernel\Error\CodedDomainException;

final class InventoryCostingRuleViolationTest extends TestCase
{
    public function testItExposesAStableCodeAndPublicMessage(): void
    {
        $violation = InventoryCostingRuleViolation::with(
            'VALUATION_NOT_INITIALIZED',
            'Stock valuation must be initialized.',
        );

        self::assertInstanceOf(CodedDomainException::class, $violation);
        self::assertSame('VALUATION_NOT_INITIALIZED', $violation->errorCode());
        self::assertSame('Stock valuation must be initialized.', $violation->publicMessage());
        self::assertSame($violation->publicMessage(), $violation->getMessage());
    }
}
