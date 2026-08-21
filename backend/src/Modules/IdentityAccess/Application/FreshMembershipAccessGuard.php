<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application;

use LogicException;
use Zandu\Modules\IdentityAccess\Application\Contract\MembershipAccessGuard;
use Zandu\Modules\IdentityAccess\Domain\Membership\MembershipStatus;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembershipRepository;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Tenancy\TenantTransaction;

final readonly class FreshMembershipAccessGuard implements MembershipAccessGuard
{
    public function __construct(private OrganizationMembershipRepository $memberships, private TenantTransaction $transaction) {}

    public function assertFreshActiveMembership(ActorContext $actorContext): void
    {
        $userId = $actorContext->userId();
        $claimedVersion = $actorContext->authorizationVersion();
        if (null === $userId || null === $claimedVersion) {
            throw new LogicException('A membership authorization version is required.');
        }
        $organizationId = $actorContext->organizationId();
        $this->transaction->transactional($organizationId, function () use ($organizationId, $userId, $claimedVersion): void {
            $membership = $this->memberships->findByUser($organizationId, $userId);
            if (null === $membership || MembershipStatus::Active !== $membership->status()
                || $membership->authorizationVersion() !== $claimedVersion) {
                throw new LogicException('Membership access is inactive or its authorization version is stale.');
            }
        });
    }
}
