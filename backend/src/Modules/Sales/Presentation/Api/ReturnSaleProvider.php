<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use InvalidArgumentException;
use Zandu\Modules\Sales\Application\ReturnSaleQueryService;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Identity\{ReturnSaleId, SaleId, UuidFactory};
use Zandu\SharedKernel\Tenancy\TenantTransaction;

/** @implements ProviderInterface<ReturnSaleResource> */
final readonly class ReturnSaleProvider implements ProviderInterface
{
    public function __construct(private CurrentActorProvider $actors, private UuidFactory $uuids, private TenantTransaction $transaction, private ReturnSaleQueryService $returns, private ReturnSaleResourceMapper $mapper) {}

    /** @return ReturnSaleResource|list<ReturnSaleResource> */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ReturnSaleResource|array
    {
        $actor = $this->actors->resolve();

        return $this->transaction->transactional($actor->organizationId(), function () use ($actor, $operation, $uriVariables): ReturnSaleResource|array {
            if ('return_sale_list_by_sale' === $operation->getName()) {
                $saleId = SaleId::fromString((string) ($uriVariables['saleId'] ?? throw new InvalidArgumentException('Sale identifier is required.')), $this->uuids);

                return array_map($this->mapper->map(...), $this->returns->findBySale($actor, $saleId));
            }
            $returnId = ReturnSaleId::fromString((string) ($uriVariables['id'] ?? throw new InvalidArgumentException('Return identifier is required.')), $this->uuids);

            return $this->mapper->map($this->returns->get($actor, $returnId));
        });
    }
}
