<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application\UpdateProduct;

use Zandu\Modules\Catalog\Application\TenantCategoryLoader;
use Zandu\Modules\Catalog\Application\TenantProductLoader;
use Zandu\Modules\Catalog\Application\TenantUnitOfMeasureLoader;
use Zandu\Modules\Catalog\Domain\Product\Product;
use Zandu\Modules\Catalog\Domain\Product\ProductCode;
use Zandu\Modules\Catalog\Domain\Product\ProductCodeAlreadyExists;
use Zandu\Modules\Catalog\Domain\Product\ProductName;
use Zandu\Modules\Catalog\Domain\Product\ProductRepository;
use Zandu\Modules\Catalog\Domain\Product\ProductType;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class UpdateProductHandler
{
    public function __construct(
        private TenantProductLoader $loader,
        private ProductRepository $products,
        private TenantUnitOfMeasureLoader $units,
        private TenantCategoryLoader $categories,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $operationalGuard,
    ) {}

    public function __invoke(UpdateProduct $command): Product
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): Product {
            $this->authorization->authorize(
                $command->actorContext,
                PermissionCode::ProductUpdate,
                ResourceScope::organization($organizationId),
            );
            $this->operationalGuard->assertTenant($command->actorContext);

            $product = $this->loader->get($command->productId, $command->actorContext);
            $command->expectedVersion->assertMatches($product);
            $code = ProductCode::fromString($command->productCode);
            $sameCodeProduct = $this->products->findByCode($organizationId, $code);
            if (null !== $sameCodeProduct && !$sameCodeProduct->id()->equals($product->id())) {
                throw ProductCodeAlreadyExists::withCode($code);
            }

            $unit = $this->units->get($command->baseUnitId, $command->actorContext);
            $unit->ensureSelectable();
            $category = null !== $command->categoryId
                ? $this->categories->get($command->categoryId, $command->actorContext)
                : null;
            $category?->ensureSelectable();

            $product->updateProfile(
                $code,
                ProductName::fromString($command->name),
                $command->description,
                ProductType::from(strtoupper(trim($command->type))),
                $unit->id(),
                $command->inventoryTracked,
                $command->taxCategoryId,
                $category?->id(),
                $command->actorContext->actorId(),
                $this->clock->now(),
            );
            $this->products->save($product);

            return $product;
        });
    }
}
