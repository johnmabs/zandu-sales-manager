<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

final readonly class UnitOfMeasureView
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $code,
        public string $name,
        public string $dimension,
        public int $precision,
        public string $roundingMode,
        public string $status,
        public int $version,
    ) {}
}
