<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use InvalidArgumentException;
use Zandu\Modules\Inventory\Application\StockCountQueryService;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Identity\{StockCountId, UuidFactory};
use Zandu\SharedKernel\Tenancy\TenantTransaction;

/** @implements ProviderInterface<StockCountResource> */
final readonly class StockCountProvider implements ProviderInterface
{
    public function __construct(
        private CurrentActorProvider $actors,
        private UuidFactory $uuids,
        private TenantTransaction $transaction,
        private StockCountQueryService $stockCounts,
        private StockCountResourceMapper $mapper,
    ) {}

    /** @return StockCountResource|list<StockCountResource> */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): StockCountResource|array
    {
        $actor = $this->actors->resolve();

        return $this->transaction->transactional($actor->organizationId(), function () use ($actor, $operation, $uriVariables): StockCountResource|array {
            if ('stock_count_list' === $operation->getName()) {
                return array_map($this->mapper->map(...), $this->stockCounts->list($actor));
            }

            return $this->mapper->map($this->stockCounts->get($actor, $this->stockCountId($uriVariables['id'] ?? null)));
        });
    }

    private function stockCountId(mixed $value): StockCountId
    {
        if (!is_string($value) || '' === trim($value)) {
            throw new InvalidArgumentException('Stock count identifier is required.');
        }

        return StockCountId::fromString($value, $this->uuids);
    }
}
