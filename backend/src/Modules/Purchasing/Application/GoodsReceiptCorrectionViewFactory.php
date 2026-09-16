<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application;

use Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection\GoodsReceiptCorrection;
use Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection\GoodsReceiptCorrectionLine;

final readonly class GoodsReceiptCorrectionViewFactory
{
    public function create(GoodsReceiptCorrection $correction): GoodsReceiptCorrectionView
    {
        return new GoodsReceiptCorrectionView(
            $correction->id()->toString(),
            $correction->goodsReceiptId()->toString(),
            $correction->reason(),
            $correction->status()->value,
            array_map(static fn(GoodsReceiptCorrectionLine $line): array => [
                'productId' => $line->productId()->toString(),
                'originalReceivedQuantity' => $line->originalReceivedQuantity()->toString(),
                'currentEffectiveQuantity' => $line->currentEffectiveQuantity()->toString(),
                'correctedReceivedQuantity' => $line->correctedReceivedQuantity()->toString(),
                'difference' => $line->difference()->toString(),
            ], $correction->lines()),
            $correction->createdAt()->format(DATE_ATOM),
            $correction->postedAt()?->format(DATE_ATOM),
            $correction->version(),
        );
    }
}
