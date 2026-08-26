<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use InvalidArgumentException;
use Zandu\Modules\Catalog\Application\ProductPackagingQueryService;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Identity\UuidFactory;
use Zandu\SharedKernel\Tenancy\TenantTransaction;

/** @implements ProviderInterface<ProductPackagingResource> */
final readonly class ProductPackagingProvider implements ProviderInterface
{
    public function __construct(private CurrentActorProvider $actors, private UuidFactory $uuidFactory, private TenantTransaction $transaction, private ProductPackagingQueryService $queries, private ProductPackagingResourceFactory $resources) {}

    /** @return ProductPackagingResource|list<ProductPackagingResource> */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ProductPackagingResource|array
    {
        $actor = $this->actors->resolve();
        return $this->transaction->transactional($actor->organizationId(), function () use ($actor, $operation, $uriVariables): ProductPackagingResource|array {
            $rawProductId = $uriVariables['productId'] ?? null;
            if (!is_string($rawProductId)) {
                throw new InvalidArgumentException('Product identifier is required.');
            }
            $productId = ProductId::fromString($rawProductId, $this->uuidFactory);
            if ('packaging_list' === $operation->getName()) {
                return array_map($this->resources->fromView(...), $this->queries->list($productId, $actor));
            }
            $rawId = $uriVariables['id'] ?? null;
            if (!is_string($rawId)) {
                throw new InvalidArgumentException('Packaging identifier is required.');
            }
            return $this->resources->fromView($this->queries->get(ProductPackagingId::fromString($rawId, $this->uuidFactory), $actor));
        });
    }
}
