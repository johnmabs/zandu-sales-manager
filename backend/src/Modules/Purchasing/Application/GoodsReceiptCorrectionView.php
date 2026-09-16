<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application;

final readonly class GoodsReceiptCorrectionView
{
    /** @param list<array<string, string>> $lines */
    public function __construct(
        public string $id,
        public string $goodsReceiptId,
        public string $reason,
        public string $status,
        public array $lines,
        public string $createdAt,
        public ?string $postedAt,
        public int $version,
    ) {}
}
