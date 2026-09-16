<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use InvalidArgumentException;
use Zandu\Modules\Purchasing\Application\GoodsReceiptQueryService;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Identity\{GoodsReceiptId, UuidFactory};
use Zandu\SharedKernel\Tenancy\TenantTransaction;

/** @implements ProviderInterface<GoodsReceiptResource> */
final readonly class GoodsReceiptProvider implements ProviderInterface
{
    public function __construct(private CurrentActorProvider $actors, private UuidFactory $uuids, private TenantTransaction $transaction, private GoodsReceiptQueryService $queries, private GoodsReceiptResourceMapper $mapper) {}

    /** @return GoodsReceiptResource|list<GoodsReceiptResource> */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): GoodsReceiptResource|array
    {
        $actor = $this->actors->resolve();
        return $this->transaction->transactional($actor->organizationId(), function () use ($actor, $operation, $uriVariables): GoodsReceiptResource|array {
            if ('goods_receipt_list' === $operation->getName()) {
                return array_map($this->mapper->map(...), $this->queries->list($actor));
            }
            $id = $uriVariables['id'] ?? null;
            if (!is_string($id)) {
                throw new InvalidArgumentException('Goods receipt identifier is required.');
            }
            return $this->mapper->map($this->queries->get($actor, GoodsReceiptId::fromString($id, $this->uuids)));
        });
    }
}
