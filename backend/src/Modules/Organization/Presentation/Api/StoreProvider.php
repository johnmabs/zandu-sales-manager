<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use InvalidArgumentException;
use Zandu\Modules\Organization\Application\StoreQueryService;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\UuidFactory;
use Zandu\SharedKernel\Tenancy\TenantTransaction;

/** @implements ProviderInterface<StoreResource> */
final readonly class StoreProvider implements ProviderInterface
{
    public function __construct(
        private CurrentActorProvider $actors,
        private UuidFactory $uuidFactory,
        private TenantTransaction $transaction,
        private StoreQueryService $queries,
        private StoreResourceFactory $resources,
    ) {}

    /** @return StoreResource|list<StoreResource> */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): StoreResource|array
    {
        $actor = $this->actors->resolve();

        return $this->transaction->transactional($actor->organizationId(), function () use ($actor, $operation, $uriVariables): StoreResource|array {
            if ('store_list' === $operation->getName()) {
                return array_map($this->resources->fromView(...), $this->queries->list($actor));
            }

            $rawId = $uriVariables['id'] ?? null;
            if (!is_string($rawId)) {
                throw new InvalidArgumentException('Store identifier is required.');
            }

            return $this->resources->fromView($this->queries->get(StoreId::fromString($rawId, $this->uuidFactory), $actor));
        });
    }
}
