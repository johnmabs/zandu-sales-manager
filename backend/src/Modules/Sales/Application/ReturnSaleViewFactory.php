<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use Zandu\Modules\Sales\Domain\ReturnSale;

final readonly class ReturnSaleViewFactory
{
    public function create(ReturnSale $return): ReturnSaleView
    {
        return new ReturnSaleView(
            $return->id()->toString(),
            $return->saleId()->toString(),
            $return->storeId()->toString(),
            $return->status()->value,
            $return->reason(),
            array_map(static function ($line): array {
                $amounts = $line->amounts();

                return [
                    'id' => $line->id()->toString(),
                    'saleLineId' => $line->saleLineId()->toString(),
                    'productId' => $line->productId()->toString(),
                    'quantity' => $line->returnedQuantity()->toString(),
                    'baseQuantity' => $line->baseReturnedQuantity()->toString(),
                    'restock' => $line->restock(),
                    'reason' => $line->reason(),
                    'amount' => null === $amounts ? null : [
                        'amount' => $amounts->total()->amount()->toString(),
                        'currency' => $amounts->total()->currency()->code(),
                    ],
                    'taxAmount' => null === $amounts ? null : [
                        'amount' => $amounts->taxAmount()->amount()->toString(),
                        'currency' => $amounts->taxAmount()->currency()->code(),
                    ],
                ];
            }, $return->lines()),
            $return->businessDate(),
            $return->completedAt()?->format(DATE_ATOM),
            $return->cancelledAt()?->format(DATE_ATOM),
            $return->version(),
        );
    }
}
