<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application;

use LogicException;
use Zandu\Modules\Organization\Application\Contract\MemberInvitationPolicy;
use Zandu\Modules\Organization\Domain\OrganizationRepository;
use Zandu\Modules\Organization\Domain\OrganizationStatus;
use Zandu\Modules\Organization\Domain\Store\StoreRepository;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\StoreId;

final readonly class CreatorMemberInvitationPolicy implements MemberInvitationPolicy
{
    public function __construct(private OrganizationRepository $organizations, private StoreRepository $stores) {}

    public function assertCanInvite(ActorContext $actorContext, array $selectedStoreIds): void
    {
        $organizationId = $actorContext->organizationId();
        $organization = $this->organizations->get($organizationId);
        if (OrganizationStatus::Active !== $organization->status()) {
            throw new LogicException('Members can only be invited to an active organization.');
        }
        if (!$organization->createdBy()->equals($actorContext->actorId())) {
            throw new LogicException('The actor is not authorized to invite organization members.');
        }
        foreach ($selectedStoreIds as $storeId) {
            if (null === $this->stores->find($organizationId, $storeId)) {
                throw new LogicException(sprintf('Selected store "%s" does not belong to the organization.', $storeId->toString()));
            }
        }
    }
}
