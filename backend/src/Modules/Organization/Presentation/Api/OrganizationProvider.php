<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use InvalidArgumentException;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\OrganizationViewFactory;
use Zandu\Modules\Organization\Application\TenantOrganizationLoader;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\UuidFactory;
use Zandu\SharedKernel\Tenancy\TenantTransaction;

/** @implements ProviderInterface<OrganizationResource> */
final readonly class OrganizationProvider implements ProviderInterface
{
    public function __construct(
        private CurrentActorProvider $actors,
        private UuidFactory $uuidFactory,
        private TenantTransaction $transaction,
        private TenantOrganizationLoader $organizations,
        private AuthorizationService $authorization,
        private OrganizationViewFactory $views,
        private OrganizationResourceFactory $resources,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): OrganizationResource
    {
        $actor = $this->actors->resolve();
        $rawId = $uriVariables['id'] ?? null;
        if (!is_string($rawId)) {
            throw new InvalidArgumentException('Organization identifier is required.');
        }
        $id = OrganizationId::fromString($rawId, $this->uuidFactory);

        return $this->transaction->transactional($actor->organizationId(), function () use ($actor, $id): OrganizationResource {
            $organization = $this->organizations->get($id, $actor);
            $this->authorization->authorize($actor, PermissionCode::OrganizationRead, ResourceScope::organization($id));

            return $this->resources->fromView($this->views->fromAggregate($organization));
        });
    }
}
