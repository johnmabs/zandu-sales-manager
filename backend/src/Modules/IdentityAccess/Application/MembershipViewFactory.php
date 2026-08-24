<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application;

use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembership;

final readonly class MembershipViewFactory
{
    public function fromAggregate(OrganizationMembership $membership): MembershipView
    {
        $assignments = array_map(static fn($assignment): array => [
            'assignmentId' => $assignment->roleId()->toString(),
            'roleId' => $assignment->roleId()->toString(),
            'scopeType' => $assignment->scope()->type()->value,
            'storeIds' => array_map(static fn($storeId): string => $storeId->toString(), $assignment->scope()->storeIds()),
            'assignedAt' => $assignment->assignedAt()->format(DATE_ATOM),
            'expiresAt' => $assignment->expiresAt()?->format(DATE_ATOM),
        ], $membership->roleAssignments());

        return new MembershipView(
            $membership->id()->toString(),
            $membership->organizationId()->toString(),
            $membership->userId()->toString(),
            $membership->status()->value,
            $assignments,
            $membership->authorizationVersion(),
            $membership->createdAt()->format(DATE_ATOM),
            $membership->updatedAt()->format(DATE_ATOM),
            $membership->version(),
        );
    }
}
