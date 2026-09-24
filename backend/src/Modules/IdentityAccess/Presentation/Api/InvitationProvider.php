<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Zandu\Modules\IdentityAccess\Application\InvitationQueryService;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Tenancy\TenantTransaction;

/** @implements ProviderInterface<InvitationResource> */
final readonly class InvitationProvider implements ProviderInterface
{
    public function __construct(
        private CurrentActorProvider $actors,
        private TenantTransaction $transaction,
        private InvitationQueryService $queries,
        private InvitationResourceFactory $resources,
    ) {}

    /** @return list<InvitationResource> */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $actor = $this->actors->resolve();

        return $this->transaction->transactional(
            $actor->organizationId(),
            fn(): array => array_map($this->resources->fromView(...), $this->queries->list($actor)),
        );
    }
}
