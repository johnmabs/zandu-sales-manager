<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection;

use Zandu\SharedKernel\Identity\GoodsReceiptCorrectionId;
use Zandu\SharedKernel\Identity\GoodsReceiptId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Quantity\Quantity;

interface GoodsReceiptCorrectionRepository
{
    public function save(GoodsReceiptCorrection $correction): void;
    public function get(OrganizationId $organizationId, GoodsReceiptCorrectionId $correctionId): GoodsReceiptCorrection;
    public function getForUpdate(OrganizationId $organizationId, GoodsReceiptCorrectionId $correctionId): GoodsReceiptCorrection;
    public function find(OrganizationId $organizationId, GoodsReceiptCorrectionId $correctionId): ?GoodsReceiptCorrection;
    /** @return array<string, Quantity> keyed by ProductId string */
    public function postedDifferenceByProduct(OrganizationId $organizationId, GoodsReceiptId $goodsReceiptId): array;
    public function hasOpenForStore(OrganizationId $organizationId, StoreId $storeId): bool;
}
