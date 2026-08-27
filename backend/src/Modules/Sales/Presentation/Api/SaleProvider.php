<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use InvalidArgumentException;
use Zandu\Modules\Sales\Application\SaleQueryService;
use Zandu\Modules\Sales\Domain\{SaleStatus,SalesRuleViolation};
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

        return $this->transaction->transactional($actor->organizationId(), function () use ($actor, $saleId, $operation): SaleResource {
            $sale = $this->sales->get($actor, $saleId);
            if ('sale_receipt' === $operation->getName() && SaleStatus::Completed !== $sale->status()) {
                throw SalesRuleViolation::with('SALE_NOT_COMPLETED', 'A receipt is only available for a completed sale.');
            }

            return $this->mapper->map($sale);
        });
    }
}
