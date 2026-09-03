<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application;

use Zandu\Modules\Inventory\Domain\StockCount\{StockCount, StockCountLine, StockCountMode, StockCountStatus};

final readonly class StockCountViewFactory
{
    /** @param list<StockCountLine> $lines */
    public function create(StockCount $stockCount, array $lines): StockCountView
    {
        $revealExpected = StockCountMode::Guided === $stockCount->mode()
            || !in_array($stockCount->status(), [StockCountStatus::Draft, StockCountStatus::Open], true);

        return new StockCountView(
            $stockCount->id()->toString(),
            $stockCount->storeId()->toString(),
            $stockCount->status()->value,
            $stockCount->mode()->value,
            $stockCount->scopeType()->value,
            array_map(static fn($id): string => $id->toString(), $stockCount->requestedProductIds()),
            array_map(fn(StockCountLine $line): array => $this->line($line, $revealExpected), $lines),
            $stockCount->totalLineCount(),
            $stockCount->countedLineCount(),
            $stockCount->reconciledLineCount(),
            $stockCount->createdAt()->format(DATE_ATOM),
            $stockCount->startedAt()?->format(DATE_ATOM),
            $stockCount->finalizationStartedAt()?->format(DATE_ATOM),
            $stockCount->completedAt()?->format(DATE_ATOM),
            $stockCount->cancelledAt()?->format(DATE_ATOM),
            $stockCount->version(),
        );
    }

    /** @return array<string, int|string|null> */
    private function line(StockCountLine $line, bool $revealExpected): array
    {
        $view = [
            'id' => $line->id()->toString(),
            'productId' => $line->productId()->toString(),
            'countedQuantity' => $line->countedQuantity()?->toString(),
            'countedAt' => $line->countedAt()?->format(DATE_ATOM),
            'revision' => $line->revision(),
            'reconciliationStatus' => $line->reconciliationStatus()->value,
            'version' => $line->version(),
        ];
        if (!$revealExpected) {
            return $view;
        }

        $view['expectedQuantity'] = $line->expectedQuantity()->toString();
        $view['variance'] = $this->variance($line);

        return $view;
    }

    private function variance(StockCountLine $line): ?string
    {
        $counted = $line->countedQuantity();
        if (null === $counted) {
            return null;
        }
        $expected = $line->expectedQuantity();
        $comparison = $counted->compareTo($expected);

        return match (true) {
            0 === $comparison => '0',
            $comparison > 0 => $counted->subtract($expected)->toString(),
            default => '-' . $expected->subtract($counted)->toString(),
        };
    }
}
