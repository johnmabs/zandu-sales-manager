<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use InvalidArgumentException;
use Zandu\Modules\Purchasing\Application\SupplierQueryService;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Identity\{SupplierId, UuidFactory};
use Zandu\SharedKernel\Tenancy\TenantTransaction;

/** @implements ProviderInterface<SupplierResource> */
final readonly class SupplierProvider implements ProviderInterface
{
    public function __construct(private CurrentActorProvider $actors, private UuidFactory $uuids, private TenantTransaction $transaction, private SupplierQueryService $queries, private SupplierResourceMapper $mapper) {}

    /** @return SupplierResource|list<SupplierResource> */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SupplierResource|array
    {
        $actor = $this->actors->resolve();

        return $this->transaction->transactional($actor->organizationId(), function () use ($actor, $operation, $uriVariables): SupplierResource|array {
            if ('supplier_list' === $operation->getName()) {
                return array_map($this->mapper->map(...), $this->queries->list($actor));
            }
            $id = $uriVariables['id'] ?? null;
            if (!is_string($id)) {
                throw new InvalidArgumentException('Supplier identifier is required.');
            }

            return $this->mapper->map($this->queries->get($actor, SupplierId::fromString($id, $this->uuids)));
        });
    }
}
