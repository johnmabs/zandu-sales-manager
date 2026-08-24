<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use Zandu\Modules\IdentityAccess\Application\MembershipViewFactory;
use Zandu\Modules\IdentityAccess\Application\ReactivateOrganizationMembership\ReactivateOrganizationMembership;
use Zandu\Modules\IdentityAccess\Application\ReactivateOrganizationMembership\ReactivateOrganizationMembershipHandler;
use Zandu\Modules\IdentityAccess\Application\RevokeOrganizationMembership\RevokeOrganizationMembership;
use Zandu\Modules\IdentityAccess\Application\RevokeOrganizationMembership\RevokeOrganizationMembershipHandler;
use Zandu\Modules\IdentityAccess\Application\SuspendOrganizationMembership\SuspendOrganizationMembership;
use Zandu\Modules\IdentityAccess\Application\SuspendOrganizationMembership\SuspendOrganizationMembershipHandler;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Identity\OrganizationMembershipId;
use Zandu\SharedKernel\Identity\UuidFactory;

/** @implements ProcessorInterface<mixed, MembershipResource> */
final readonly class MembershipProcessor implements ProcessorInterface
{
    public function __construct(
        private CurrentActorProvider $actors,
        private UuidFactory $uuidFactory,
        private MembershipViewFactory $views,
        private MembershipResourceFactory $resources,
        private SuspendOrganizationMembershipHandler $suspend,
        private ReactivateOrganizationMembershipHandler $reactivate,
        private RevokeOrganizationMembershipHandler $revoke,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): MembershipResource
    {
        $rawId = $uriVariables['id'] ?? null;
        if (!is_string($rawId)) {
            throw new InvalidArgumentException('Membership identifier is required.');
        }
        $actor = $this->actors->resolve();
        $id = OrganizationMembershipId::fromString($rawId, $this->uuidFactory);
        $membership = match ($operation->getName()) {
            'member_suspend' => ($this->suspend)(new SuspendOrganizationMembership($id, $actor)),
            'member_reactivate' => ($this->reactivate)(new ReactivateOrganizationMembership($id, $actor)),
            'member_revoke' => ($this->revoke)(new RevokeOrganizationMembership($id, $actor)),
            default => throw new InvalidArgumentException('Unsupported membership operation.'),
        };

        return $this->resources->fromView($this->views->fromAggregate($membership));
    }
}
