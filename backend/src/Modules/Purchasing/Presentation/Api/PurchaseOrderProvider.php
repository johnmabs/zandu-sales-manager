<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use InvalidArgumentException;
use Zandu\Modules\Purchasing\Application\PurchaseOrderQueryService;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Identity\{PurchaseOrderId, UuidFactory};
use Zandu\SharedKernel\Tenancy\TenantTransaction;

/** @implements ProviderInterface<PurchaseOrderResource> */
final readonly class PurchaseOrderProvider implements ProviderInterface
{
    public function __construct(private CurrentActorProvider $actors, private UuidFactory $uuids, private TenantTransaction $transaction, private PurchaseOrderQueryService $queries, private PurchaseOrderResourceMapper $mapper) {}

    /** @return PurchaseOrderResource|list<PurchaseOrderResource> */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): PurchaseOrderResource|array
    {
        $actor = $this->actors->resolve();

        return $this->transaction->transactional($actor->organizationId(), function () use ($actor, $operation, $uriVariables): PurchaseOrderResource|array {
            if ('purchase_order_list' === $operation->getName()) {
                return array_map($this->mapper->map(...), $this->queries->list($actor));
            }
            $id = $uriVariables['id'] ?? null;
            if (!is_string($id)) {
                throw new InvalidArgumentException('Purchase order identifier is required.');
            }

            return $this->mapper->map($this->queries->get($actor, PurchaseOrderId::fromString($id, $this->uuids)));
        });
    }
}
