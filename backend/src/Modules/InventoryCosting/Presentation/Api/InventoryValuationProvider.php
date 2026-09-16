<?php

declare(strict_types=1);

namespace Zandu\Modules\InventoryCosting\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use InvalidArgumentException;
use Zandu\Modules\InventoryCosting\Application\InventoryValuationQueryService;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Identity\{ProductId, StoreId, UuidFactory};
use Zandu\SharedKernel\Tenancy\TenantTransaction;

/** @implements ProviderInterface<InventoryValuationResource> */
final readonly class InventoryValuationProvider implements ProviderInterface
{
    public function __construct(
        private CurrentActorProvider $actors,
        private UuidFactory $uuids,
        private TenantTransaction $transaction,
        private InventoryValuationQueryService $queries,
        private InventoryValuationResourceFactory $resources,
    ) {}

    /** @return InventoryValuationResource|list<InventoryValuationResource> */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): InventoryValuationResource|array
    {
        $actor = $this->actors->resolve();
        $storeId = $this->storeId($uriVariables['storeId'] ?? null);

        return $this->transaction->transactional($actor->organizationId(), function () use ($actor, $operation, $storeId, $uriVariables): InventoryValuationResource|array {
            if ('inventory_valuation_list' === $operation->getName()) {
                return array_map($this->resources->fromView(...), $this->queries->list($actor, $storeId));
            }

            return $this->resources->fromView(
                $this->queries->get($actor, $storeId, $this->productId($uriVariables['productId'] ?? null)),
            );
        });
    }

    private function storeId(mixed $value): StoreId
    {
        if (!is_string($value) || '' === trim($value)) {
            throw new InvalidArgumentException('Store identifier is required.');
        }

        return StoreId::fromString($value, $this->uuids);
    }

    private function productId(mixed $value): ProductId
    {
        if (!is_string($value) || '' === trim($value)) {
            throw new InvalidArgumentException('Product identifier is required.');
        }

        return ProductId::fromString($value, $this->uuids);
    }
}
