<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Purchasing\Application;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Purchasing\Application\ConfiguredPurchasingPolicy;
use Zandu\Modules\Purchasing\Application\OverReceiptPolicy;

final class ConfiguredPurchasingPolicyTest extends TestCase
{
    #[DataProvider('purchaseOrderRequirements')]
    public function testDirectReceiptIsTheInverseOfThePurchaseOrderRequirement(
        bool $purchaseOrderRequired,
        bool $directReceiptAllowed,
    ): void {
        $policy = new ConfiguredPurchasingPolicy($purchaseOrderRequired, ' forbidden ');

        self::assertSame($purchaseOrderRequired, $policy->purchaseOrderRequiredForReceipt());
        self::assertSame($directReceiptAllowed, $policy->allowsDirectReceipt());
        self::assertSame(OverReceiptPolicy::Forbidden, $policy->overReceiptPolicy());
    }

    /** @return iterable<string, array{bool, bool}> */
    public static function purchaseOrderRequirements(): iterable
    {
        yield 'MVP baseline allows direct receipt' => [false, true];
        yield 'strict deployment requires a purchase order' => [true, false];
    }

    public function testUnknownOverReceiptPolicyFailsExplicitly(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported over receipt policy');

        new ConfiguredPurchasingPolicy(false, 'ALLOW_ALL');
    }
}
