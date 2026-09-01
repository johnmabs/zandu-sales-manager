<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Purchasing\Application;

use PHPUnit\Framework\TestCase;
use Zandu\Modules\Purchasing\Application\PurchasingStoreClosureBlockerProvider;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptRepository;
use Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection\GoodsReceiptCorrectionRepository;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderRepository;
use Zandu\Modules\Purchasing\Domain\PurchaseReturn\PurchaseReturnRepository;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\{OrganizationId, StoreId};

final class PurchasingStoreClosureBlockerProviderTest extends TestCase
{
    public function testItReportsEveryOpenPurchasingDocumentForTheStore(): void
    {
        $ids = new SymfonyUuidFactory();
        $organizationId = OrganizationId::fromString('019a0200-0000-7000-8000-000000000001', $ids);
        $storeId = StoreId::fromString('019a0200-0000-7000-8000-000000000002', $ids);
        $orders = $this->createMock(PurchaseOrderRepository::class);
        $orders->expects(self::once())->method('hasOpenForStore')->with($organizationId, $storeId)->willReturn(true);
        $receipts = $this->createMock(GoodsReceiptRepository::class);
        $receipts->expects(self::once())->method('hasDraftForStore')->with($organizationId, $storeId)->willReturn(true);
        $returns = $this->createMock(PurchaseReturnRepository::class);
        $returns->expects(self::once())->method('hasOpenForStore')->with($organizationId, $storeId)->willReturn(true);
        $corrections = $this->createMock(GoodsReceiptCorrectionRepository::class);
        $corrections->expects(self::once())->method('hasOpenForStore')->with($organizationId, $storeId)->willReturn(true);

        self::assertSame(
            ['OPEN_PURCHASE_ORDER', 'DRAFT_GOODS_RECEIPT', 'OPEN_PURCHASE_RETURN', 'OPEN_GOODS_RECEIPT_CORRECTION'],
            (new PurchasingStoreClosureBlockerProvider($orders, $receipts, $returns, $corrections))->blockers($organizationId, $storeId),
        );
    }
}
