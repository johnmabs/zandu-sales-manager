<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Domain\Invitation;

use DateTimeImmutable;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\OrganizationInvitationId;

interface OrganizationInvitationRepository
{
    public function save(OrganizationInvitation $invitation): void;
    public function get(OrganizationId $organizationId, OrganizationInvitationId $invitationId): OrganizationInvitation;
    public function getByTokenHash(OrganizationId $organizationId, string $tokenHash): OrganizationInvitation;
    public function pendingExists(OrganizationId $organizationId, InvitationEmail $email, DateTimeImmutable $now): bool;
    /** @return list<OrganizationInvitation> */
    public function findExpiredPending(OrganizationId $organizationId, DateTimeImmutable $now, int $limit): array;
}
