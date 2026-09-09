<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Zandu\Modules\IdentityAccess\Application\CurrentOrganizationView;
use Zandu\Modules\IdentityAccess\Application\CurrentSessionQuery;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Tenancy\TenantTransaction;

/** @implements ProviderInterface<CurrentSessionResource> */
final readonly class CurrentSessionProvider implements ProviderInterface
{
    public function __construct(
        private CurrentActorProvider $actors,
        private TenantTransaction $transaction,
        private CurrentSessionQuery $query,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CurrentSessionResource
    {
        $actor = $this->actors->resolve();

        return $this->transaction->transactional($actor->organizationId(), function () use ($actor): CurrentSessionResource {
            $view = $this->query->get($actor);

            return new CurrentSessionResource(
                $view->id,
                $view->userId,
                $view->organizationId,
                $view->authorizationVersion,
                new EffectiveAccessResource(
                    $view->effectiveAccess->organizationId,
                    $view->effectiveAccess->authorizationVersion,
                    $view->effectiveAccess->permissions,
                    new EffectiveAccessScopeResource(
                        $view->effectiveAccess->scope['type'],
                        $view->effectiveAccess->scope['storeIds'] ?? [],
                    ),
                    $view->effectiveAccess->accessibleStoreIds,
                ),
                $view->email,
                array_map(
                    static fn(CurrentOrganizationView $organization): CurrentOrganizationResource => new CurrentOrganizationResource(
                        $organization->id,
                        $organization->name,
                        $organization->status,
                        $organization->defaultCurrency,
                        $organization->defaultTimeZone,
                        $organization->defaultLocale,
                    ),
                    $view->organizations,
                ),
            );
        });
    }
}
