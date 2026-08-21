<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application\InviteOrganizationMember;

use DateTimeImmutable;
use Zandu\Modules\IdentityAccess\Domain\Invitation\IntendedRoleAssignment;
use Zandu\SharedKernel\Context\ActorContext;

final readonly class InviteOrganizationMember
{
    /** @param non-empty-list<IntendedRoleAssignment> $intendedRoleAssignments */
    public function __construct(
        public string $email,
        public array $intendedRoleAssignments,
        public ?DateTimeImmutable $expiresAt,
        public ActorContext $actorContext,
    ) {}
}
