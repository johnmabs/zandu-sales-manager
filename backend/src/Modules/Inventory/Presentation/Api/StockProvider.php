<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use InvalidArgumentException;
use Zandu\Modules\Inventory\Application\StockQueryService;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Identity\{ProductId,StoreId,UuidFactory};
use Zandu\SharedKernel\Tenancy\TenantTransaction;

/** @implements ProviderInterface<StockResource> */
final readonly class StockProvider implements ProviderInterface
{
    public function __construct(
        private CurrentActorProvider $actors,
        private UuidFactory $uuids,
        private TenantTransaction $transaction,
        private StockQueryService $stocks,
    ) {}

    /** @return StockResource|list<StockResource> */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): StockResource|array
    {
        $actor = $this->actors->resolve();
        $storeId = $this->parseStore($uriVariables['storeId'] ?? null);

        return $this->transaction->transactional($actor->organizationId(), function () use ($actor, $operation, $storeId, $uriVariables): StockResource|array {
            if ('stock_list' === $operation->getName()) {
                return array_map($this->resource(...), $this->stocks->list($actor, $storeId));
            }
            $productId = $this->parseProduct($uriVariables['productId'] ?? null);
            return $this->resource($this->stocks->get($actor, $storeId, $productId));
        });
    }

    /** @param array<string,mixed> $stock */
    private function resource(array $stock): StockResource
    {
        return new StockResource($stock['id'], $stock['organizationId'], $stock['storeId'], $stock['productId'], $stock['quantityOnHand'], $stock['initialized'], $stock['version']);
    }

    private function parseStore(mixed $value): StoreId
    {
        if (!is_string($value) || '' === trim($value)) {
            throw new InvalidArgumentException('Store identifier is required.');
        }
        return StoreId::fromString($value, $this->uuids);
    }

    private function parseProduct(mixed $value): ProductId
    {
        if (!is_string($value) || '' === trim($value)) {
            throw new InvalidArgumentException('Product identifier is required.');
        }
        return ProductId::fromString($value, $this->uuids);
    }
}
