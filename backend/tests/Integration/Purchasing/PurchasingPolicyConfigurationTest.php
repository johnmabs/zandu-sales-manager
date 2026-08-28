<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Purchasing;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zandu\Modules\Purchasing\Application\OverReceiptPolicy;
use Zandu\Modules\Purchasing\Application\PurchasingPolicy;

final class PurchasingPolicyConfigurationTest extends KernelTestCase
{
    public function testContainerExposesTheExplicitMvpBaseline(): void
    {
        self::bootKernel();
        $policy = self::getContainer()->get(PurchasingPolicy::class);

        self::assertInstanceOf(PurchasingPolicy::class, $policy);
        self::assertFalse($policy->purchaseOrderRequiredForReceipt());
        self::assertTrue($policy->allowsDirectReceipt());
        self::assertSame(OverReceiptPolicy::Forbidden, $policy->overReceiptPolicy());
    }
}
