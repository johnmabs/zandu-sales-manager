<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application;

use Zandu\Modules\Inventory\Domain\StockTransfer\{StockTransfer, StockTransferLine};

final readonly class StockTransferViewFactory
{
    public function create(StockTransfer $transfer): StockTransferView
    {
        return new StockTransferView(
            $transfer->id()->toString(),
            $transfer->sourceStoreId()->toString(),
            $transfer->destinationStoreId()->toString(),
            $transfer->status()->value,
            array_map($this->line(...), $transfer->lines()),
            $transfer->hasTransitDiscrepancy(),
            $transfer->createdAt()->format(DATE_ATOM),
            $transfer->shippedAt()?->format(DATE_ATOM),
            $transfer->receivedAt()?->format(DATE_ATOM),
            $transfer->cancellationReason(),
            $transfer->cancelledAt()?->format(DATE_ATOM),
            $transfer->version(),
        );
    }

    /** @return array{id:string,productId:string,requestedQuantity:string,shippedQuantity:?string,receivedQuantity:?string,transitDiscrepancy:?string} */
    private function line(StockTransferLine $line): array
    {
        return [
            'id' => $line->id()->toString(),
            'productId' => $line->productId()->toString(),
            'requestedQuantity' => $line->requestedQuantity()->toString(),
            'shippedQuantity' => $line->shippedQuantity()?->toString(),
            'receivedQuantity' => $line->receivedQuantity()?->toString(),
            'transitDiscrepancy' => $line->transitDiscrepancy()?->toString(),
        ];
    }
}
