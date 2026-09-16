<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use InvalidArgumentException;
use Zandu\Modules\Purchasing\Application\GoodsReceiptCorrectionQueryService;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Identity\{GoodsReceiptCorrectionId, UuidFactory};
use Zandu\SharedKernel\Tenancy\TenantTransaction;

/** @implements ProviderInterface<GoodsReceiptCorrectionResource> */
final readonly class GoodsReceiptCorrectionProvider implements ProviderInterface
{
    public function __construct(private CurrentActorProvider $actors, private UuidFactory $uuids, private TenantTransaction $transaction, private GoodsReceiptCorrectionQueryService $queries, private GoodsReceiptCorrectionResourceMapper $mapper) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): GoodsReceiptCorrectionResource
    {
        $actor = $this->actors->resolve();
        $id = $uriVariables['id'] ?? null;
        if (!is_string($id)) {
            throw new InvalidArgumentException('Goods receipt correction identifier is required.');
        }

        return $this->transaction->transactional($actor->organizationId(), fn(): GoodsReceiptCorrectionResource => $this->mapper->map($this->queries->get($actor, GoodsReceiptCorrectionId::fromString($id, $this->uuids))));
    }
}
