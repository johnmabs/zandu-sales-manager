<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use InvalidArgumentException;
use Zandu\Modules\IdentityAccess\Application\MembershipQueryService;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Identity\OrganizationMembershipId;
use Zandu\SharedKernel\Identity\UuidFactory;
use Zandu\SharedKernel\Tenancy\TenantTransaction;

/** @implements ProviderInterface<MembershipResource> */
final readonly class MembershipProvider implements ProviderInterface
{
    public function __construct(
        private CurrentActorProvider $actors,
        private UuidFactory $uuidFactory,
        private TenantTransaction $transaction,
        private MembershipQueryService $queries,
        private MembershipResourceFactory $resources,
    ) {}

    /** @return MembershipResource|list<MembershipResource> */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): MembershipResource|array
    {
        $actor = $this->actors->resolve();

        return $this->transaction->transactional($actor->organizationId(), function () use ($actor, $operation, $uriVariables): MembershipResource|array {
            if ('member_list' === $operation->getName()) {
                return array_map($this->resources->fromView(...), $this->queries->list($actor));
            }

            $id = $uriVariables['id'] ?? null;
            if (!is_string($id)) {
                throw new InvalidArgumentException('Membership identifier is required.');
            }

            return $this->resources->fromView($this->queries->get(
                OrganizationMembershipId::fromString($id, $this->uuidFactory),
                $actor,
            ));
        });
    }
}
