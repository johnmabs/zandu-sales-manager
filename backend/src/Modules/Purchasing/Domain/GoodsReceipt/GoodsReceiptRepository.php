<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Domain\GoodsReceipt;

use Zandu\SharedKernel\Identity\GoodsReceiptId;
use Zandu\SharedKernel\Identity\OrganizationId;

interface GoodsReceiptRepository
{
    public function save(GoodsReceipt $goodsReceipt): void;
    public function get(OrganizationId $organizationId, GoodsReceiptId $goodsReceiptId): GoodsReceipt;
    public function find(OrganizationId $organizationId, GoodsReceiptId $goodsReceiptId): ?GoodsReceipt;
}
