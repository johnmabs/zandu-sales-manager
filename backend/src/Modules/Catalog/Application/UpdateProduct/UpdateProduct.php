<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application\UpdateProduct;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\CategoryId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\TaxCategoryId;
use Zandu\SharedKernel\Identity\UnitOfMeasureId;
use Zandu\SharedKernel\Versioning\ExpectedVersion;

final readonly class UpdateProduct
{
    public function __construct(
        public ProductId $productId,
        public string $productCode,
        public string $name,
        public ?string $description,
        public string $type,
        public UnitOfMeasureId $baseUnitId,
        public bool $inventoryTracked,
        public ?CategoryId $categoryId,
        public ?TaxCategoryId $taxCategoryId,
        public ExpectedVersion $expectedVersion,
        public ActorContext $actorContext,
    ) {}
}
