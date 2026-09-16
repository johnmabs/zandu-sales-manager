<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Presentation\Api;

use ApiPlatform\Metadata\{ApiResource, Get, GetCollection};

#[ApiResource(operations: [
    new GetCollection(name: 'unit_of_measure_list', uriTemplate: '/units-of-measure', provider: UnitOfMeasureProvider::class),
    new Get(name: 'unit_of_measure_get', uriTemplate: '/units-of-measure/{id}', provider: UnitOfMeasureProvider::class),
])]
final readonly class UnitOfMeasureResource
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
