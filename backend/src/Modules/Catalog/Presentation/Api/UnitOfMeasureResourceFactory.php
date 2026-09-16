<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Presentation\Api;

use Zandu\Modules\Catalog\Application\UnitOfMeasureView;

final readonly class UnitOfMeasureResourceFactory
{
    public function fromView(UnitOfMeasureView $unit): UnitOfMeasureResource
    {
        return new UnitOfMeasureResource(
            $unit->id,
            $unit->organizationId,
            $unit->code,
            $unit->name,
            $unit->dimension,
            $unit->precision,
            $unit->roundingMode,
            $unit->status,
            $unit->version,
        );
    }
}
