<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use InvalidArgumentException;
use Zandu\Modules\Catalog\Application\UnitOfMeasureQueryService;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Identity\{UnitOfMeasureId, UuidFactory};
use Zandu\SharedKernel\Tenancy\TenantTransaction;

/** @implements ProviderInterface<UnitOfMeasureResource> */
final readonly class UnitOfMeasureProvider implements ProviderInterface
{
    public function __construct(
        private CurrentActorProvider $actors,
        private UuidFactory $uuids,
        private TenantTransaction $transaction,
        private UnitOfMeasureQueryService $queries,
        private UnitOfMeasureResourceFactory $resources,
    ) {}

    /** @return UnitOfMeasureResource|list<UnitOfMeasureResource> */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): UnitOfMeasureResource|array
    {
        $actor = $this->actors->resolve();

        return $this->transaction->transactional($actor->organizationId(), function () use ($actor, $operation, $uriVariables): UnitOfMeasureResource|array {
            if ('unit_of_measure_list' === $operation->getName()) {
                return array_map($this->resources->fromView(...), $this->queries->list($actor));
            }

            $rawId = $uriVariables['id'] ?? null;
            if (!is_string($rawId) || '' === trim($rawId)) {
                throw new InvalidArgumentException('Unit of measure identifier is required.');
            }

            return $this->resources->fromView(
                $this->queries->get(UnitOfMeasureId::fromString($rawId, $this->uuids), $actor),
            );
        });
    }
}
