<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use InvalidArgumentException;
use Zandu\Modules\Purchasing\Application\PurchaseReturnQueryService;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Identity\PurchaseReturnId;
use Zandu\SharedKernel\Identity\UuidFactory;
use Zandu\SharedKernel\Tenancy\TenantTransaction;

/** @implements ProviderInterface<PurchaseReturnResource> */
final readonly class PurchaseReturnProvider implements ProviderInterface
{
    public function __construct(
        private CurrentActorProvider $actors,
        private UuidFactory $uuids,
        private TenantTransaction $transaction,
        private PurchaseReturnQueryService $returns,
        private PurchaseReturnResourceMapper $mapper,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): PurchaseReturnResource
    {
        $actor = $this->actors->resolve();
        $returnId = PurchaseReturnId::fromString((string) ($uriVariables['id'] ?? throw new InvalidArgumentException('Purchase return identifier is required.')), $this->uuids);

        return $this->transaction->transactional(
            $actor->organizationId(),
            fn(): PurchaseReturnResource => $this->mapper->map($this->returns->get($actor, $returnId)),
        );
    }
}
