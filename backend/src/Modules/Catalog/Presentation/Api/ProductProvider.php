<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use InvalidArgumentException;
use Zandu\Modules\Catalog\Application\ProductQueryService;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\UuidFactory;
use Zandu\SharedKernel\Tenancy\TenantTransaction;

/** @implements ProviderInterface<ProductResource> */
final readonly class ProductProvider implements ProviderInterface
{
    public function __construct(
        private CurrentActorProvider $actors,
        private UuidFactory $uuidFactory,
        private TenantTransaction $transaction,
        private ProductQueryService $queries,
        private ProductResourceFactory $resources,
    ) {}

    /** @return ProductResource|list<ProductResource> */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ProductResource|array
    {
        $actor = $this->actors->resolve();

        return $this->transaction->transactional($actor->organizationId(), function () use ($actor, $operation, $uriVariables, $context): ProductResource|array {
            if ('product_list' === $operation->getName()) {
                $filters = $this->contextFilters($context);
                return array_map($this->resources->fromView(...), $this->queries->list($actor, $filters['status'], $filters['type'], $filters['categoryId'], $filters['productCode'], $filters['search']));
            }

            $rawId = $uriVariables['id'] ?? null;
            if (!is_string($rawId)) {
                throw new InvalidArgumentException('Product identifier is required.');
            }

            return $this->resources->fromView($this->queries->get(ProductId::fromString($rawId, $this->uuidFactory), $actor));
        });
    }

    /**
     * @param array<string,mixed> $context
     * @return array{status:?string,type:?string,categoryId:?string,productCode:?string,search:?string}
     */
    private function contextFilters(array $context): array
    {
        $filters = $context['filters'] ?? [];

        return is_array($filters) ? [
            'status' => is_string($filters['status'] ?? null) ? $filters['status'] : null,
            'type' => is_string($filters['type'] ?? null) ? $filters['type'] : null,
            'categoryId' => is_string($filters['categoryId'] ?? null) ? $filters['categoryId'] : null,
            'productCode' => is_string($filters['productCode'] ?? null) ? $filters['productCode'] : null,
            'search' => is_string($filters['search'] ?? null) ? $filters['search'] : null,
        ] : ['status' => null, 'type' => null, 'categoryId' => null, 'productCode' => null, 'search' => null];
    }
}
