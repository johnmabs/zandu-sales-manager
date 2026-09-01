<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use InvalidArgumentException;
use Zandu\Modules\Inventory\Application\StockTransferQueryService;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Identity\{StockTransferId, UuidFactory};
use Zandu\SharedKernel\Tenancy\TenantTransaction;

/** @implements ProviderInterface<StockTransferResource> */
final readonly class StockTransferProvider implements ProviderInterface
{
    public function __construct(private CurrentActorProvider $actors, private UuidFactory $uuids, private TenantTransaction $transaction, private StockTransferQueryService $transfers, private StockTransferResourceMapper $mapper) {}

    /** @return StockTransferResource|list<StockTransferResource> */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): StockTransferResource|array
    {
        $actor = $this->actors->resolve();

        return $this->transaction->transactional($actor->organizationId(), function () use ($actor, $operation, $uriVariables): StockTransferResource|array {
            if ('stock_transfer_list' === $operation->getName()) {
                return array_map($this->mapper->map(...), $this->transfers->list($actor));
            }

            return $this->mapper->map($this->transfers->get($actor, $this->transferId($uriVariables['id'] ?? null)));
        });
    }

    private function transferId(mixed $value): StockTransferId
    {
        if (!is_string($value) || '' === trim($value)) {
            throw new InvalidArgumentException('Stock transfer identifier is required.');
        }

        return StockTransferId::fromString($value, $this->uuids);
    }
}
