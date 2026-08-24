<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Zandu\Modules\IdentityAccess\Application\RoleQueryService;
use Zandu\Modules\IdentityAccess\Application\RoleView;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Tenancy\TenantTransaction;

/** @implements ProviderInterface<RoleResource> */
final readonly class RoleProvider implements ProviderInterface
{
    public function __construct(
        private CurrentActorProvider $actors,
        private TenantTransaction $transaction,
        private RoleQueryService $roles,
    ) {}

    /** @return non-empty-list<RoleResource> */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $actor = $this->actors->resolve();

        return $this->transaction->transactional(
            $actor->organizationId(),
            fn(): array => array_map($this->resource(...), $this->roles->list($actor)),
        );
    }

    private function resource(RoleView $role): RoleResource
    {
        return new RoleResource(
            $role->id,
            $role->code,
            $role->type,
            $role->status,
            $role->name,
            $role->description,
            $role->permissions,
            $role->version,
        );
    }
}
