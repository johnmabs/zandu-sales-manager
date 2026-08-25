<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application\CreateProduct;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\CategoryId;
use Zandu\SharedKernel\Identity\TaxCategoryId;
use Zandu\SharedKernel\Identity\UnitOfMeasureId;

final readonly class CreateProduct
{
    public function __construct(
        public string $productCode,
        public string $name,
        public ?string $description,
        public string $type,
        public UnitOfMeasureId $baseUnitId,
        public bool $inventoryTracked,
        public ?CategoryId $categoryId,
        public ?TaxCategoryId $taxCategoryId,
        public ActorContext $actorContext,
    ) {}
}
