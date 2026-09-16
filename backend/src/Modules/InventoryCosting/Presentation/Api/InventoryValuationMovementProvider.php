<?php

declare(strict_types=1);

namespace Zandu\Modules\InventoryCosting\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use InvalidArgumentException;
use Zandu\Modules\InventoryCosting\Application\{InventoryValuationQueryService, StockValuationMovementView};
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Identity\{ProductId, StoreId, UuidFactory};
use Zandu\SharedKernel\Tenancy\TenantTransaction;

/** @implements ProviderInterface<InventoryValuationMovementResource> */
final readonly class InventoryValuationMovementProvider implements ProviderInterface
{
    public function __construct(
        private CurrentActorProvider $actors,
        private UuidFactory $uuids,
        private TenantTransaction $transaction,
        private InventoryValuationQueryService $queries,
    ) {}

    /** @return list<InventoryValuationMovementResource> */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $actor = $this->actors->resolve();
        $storeId = $this->storeId($uriVariables['storeId'] ?? null);
        $productId = $this->productId($uriVariables['productId'] ?? null);

        return $this->transaction->transactional(
            $actor->organizationId(),
            fn(): array => array_map($this->resource(...), $this->queries->movements($actor, $storeId, $productId)),
        );
    }

    private function resource(StockValuationMovementView $movement): InventoryValuationMovementResource
    {
        return new InventoryValuationMovementResource(
            $movement->id,
            $movement->stockValuationId,
            $movement->organizationId,
            $movement->storeId,
            $movement->productId,
            $movement->stockId,
            $movement->stockMovementId,
            $movement->type,
            $movement->quantity,
            $movement->unitCost,
            $movement->value,
            $movement->previousTotalValue,
            $movement->resultingTotalValue,
            $movement->previousAverageCost,
            $movement->resultingAverageCost,
            $movement->currency,
            $movement->sourceType,
            $movement->sourceReferenceId,
            $movement->occurredAt,
            $movement->correlationId,
        );
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
