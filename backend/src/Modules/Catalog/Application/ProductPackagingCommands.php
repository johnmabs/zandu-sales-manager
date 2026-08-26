<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

/** Marker for Symfony's PSR-4 service discovery of this grouped command file. */
final class ProductPackagingCommands {}

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Identity\UnitOfMeasureId;

final readonly class CreateProductPackaging
{
    public function __construct(public ProductId $productId, public string $code, public string $name, public UnitOfMeasureId $unitId, public string $conversionFactor, public int $precision, public string $minimumQuantity, public string $quantityIncrement, public bool $allowedForSale, public bool $allowedForPurchase, public ActorContext $actorContext) {}
}
final readonly class UpdateProductPackaging
{
    public function __construct(public ProductPackagingId $packagingId, public string $name, public string $minimumQuantity, public string $quantityIncrement, public bool $allowedForSale, public bool $allowedForPurchase, public ActorContext $actorContext) {}
}
final readonly class DeactivateProductPackaging
{
    public function __construct(public ProductPackagingId $packagingId, public ActorContext $actorContext) {}
}
final readonly class ArchiveProductPackaging
{
    public function __construct(public ProductPackagingId $packagingId, public ActorContext $actorContext) {}
}
