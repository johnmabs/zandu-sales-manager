<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

use Zandu\Modules\Catalog\Domain\Product\ProductCode;
use Zandu\Modules\Catalog\Domain\Product\ProductRepository;
use Zandu\Modules\Catalog\Domain\Product\ProductStatus;
use Zandu\Modules\Catalog\Domain\Product\ProductType;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\CategoryId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\UuidFactory;

final readonly class ProductQueryService
{
    public function __construct(
        private ProductRepository $products,
        private TenantProductLoader $loader,
        private AuthorizationService $authorization,
        private ProductViewFactory $views,
        private UuidFactory $uuidFactory,
    ) {}

    /** @return list<ProductView> */
    public function list(ActorContext $actor, ?string $status, ?string $type, ?string $categoryId, ?string $productCode, ?string $search): array
    {
        $organizationId = $actor->organizationId();
        $this->authorization->authorize($actor, PermissionCode::ProductRead, ResourceScope::organization($organizationId));

        return array_map(
            $this->views->fromAggregate(...),
            $this->products->findAll(
                $organizationId,
                null !== $status ? ProductStatus::from(strtoupper($status)) : null,
                null !== $type ? ProductType::from(strtoupper($type)) : null,
                null !== $categoryId ? CategoryId::fromString($categoryId, $this->uuidFactory) : null,
                null !== $productCode ? ProductCode::fromString($productCode) : null,
                $search,
            ),
        );
    }

    public function get(ProductId $id, ActorContext $actor): ProductView
    {
        $organizationId = $actor->organizationId();
        $this->authorization->authorize($actor, PermissionCode::ProductRead, ResourceScope::organization($organizationId));

        return $this->views->fromAggregate($this->loader->get($id, $actor));
    }
}
