<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use InvalidArgumentException;
use Zandu\Modules\Sales\Application\SaleQueryService;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Identity\{SaleId,UuidFactory};
use Zandu\SharedKernel\Tenancy\TenantTransaction;

/** @implements ProviderInterface<SaleResource> */
final readonly class SaleProvider implements ProviderInterface
{
    public function __construct(private CurrentActorProvider $actors, private UuidFactory $uuids, private TenantTransaction $transaction, private SaleQueryService $sales, private SaleResourceMapper $mapper) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SaleResource
    {
        $actor = $this->actors->resolve();
        $saleId = SaleId::fromString((string) ($uriVariables['id'] ?? throw new InvalidArgumentException('Sale identifier is required.')), $this->uuids);

        return $this->transaction->transactional($actor->organizationId(), fn(): SaleResource => $this->mapper->map($this->sales->get($actor, $saleId, 'sale_receipt' === $operation->getName())));
    }
}
