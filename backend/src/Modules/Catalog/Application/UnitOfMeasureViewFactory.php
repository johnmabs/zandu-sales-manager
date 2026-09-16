<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

use Zandu\Modules\Catalog\Domain\UnitOfMeasure;
use Zandu\SharedKernel\Decimal\RoundingMode;

final readonly class UnitOfMeasureViewFactory
{
    public function fromAggregate(UnitOfMeasure $unit): UnitOfMeasureView
    {
        return new UnitOfMeasureView(
            $unit->id()->toString(),
            $unit->organizationId()->toString(),
            $unit->code()->value(),
            $unit->name()->value(),
            $unit->dimension()->value,
            $unit->precision()->value(),
            $this->roundingMode($unit->roundingMode()),
            $unit->status()->value,
            $unit->version(),
        );
    }

    private function roundingMode(RoundingMode $mode): string
    {
        return match ($mode) {
            RoundingMode::Unnecessary => 'UNNECESSARY',
            RoundingMode::Up => 'UP',
            RoundingMode::Down => 'DOWN',
            RoundingMode::Ceiling => 'CEILING',
            RoundingMode::Floor => 'FLOOR',
            RoundingMode::HalfUp => 'HALF_UP',
            RoundingMode::HalfDown => 'HALF_DOWN',
            RoundingMode::HalfEven => 'HALF_EVEN',
        };
    }
}
