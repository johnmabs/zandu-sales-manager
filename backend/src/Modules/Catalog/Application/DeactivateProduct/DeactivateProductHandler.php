<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application\DeactivateProduct;

use Zandu\Modules\Catalog\Application\TenantProductLoader;
use Zandu\Modules\Catalog\Domain\Product\Product;
use Zandu\Modules\Catalog\Domain\Product\ProductRepository;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class DeactivateProductHandler
{
    public function __construct(
        private TenantProductLoader $loader,
        private ProductRepository $products,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $operationalGuard,
    ) {}

    public function __invoke(DeactivateProduct $command): Product
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): Product {
            $this->authorization->authorize($command->actorContext, PermissionCode::ProductDeactivate, ResourceScope::organization($organizationId));
            $this->operationalGuard->assertTenant($command->actorContext);
            $product = $this->loader->get($command->productId, $command->actorContext);
            $product->deactivate($command->actorContext->actorId(), $this->clock->now());
            $this->products->save($product);

            return $product;
        });
    }
}
