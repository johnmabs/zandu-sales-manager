<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application\ExpireOrganizationInvitations;

use InvalidArgumentException;
use Zandu\Modules\IdentityAccess\Domain\Invitation\OrganizationInvitationRepository;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class ExpireOrganizationInvitationsHandler
{
    public function __construct(private OrganizationInvitationRepository $invitations, private Clock $clock, private TenantTransaction $transaction) {}

    public function __invoke(ExpireOrganizationInvitations $command): int
    {
        if ($command->limit < 1 || $command->limit > 1000) {
            throw new InvalidArgumentException('Expiration batch limit must be between 1 and 1000.');
        }
        $organizationId = $command->actorContext->organizationId();
        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): int {
            $now = $this->clock->now();
            $expired = 0;
            foreach ($this->invitations->findExpiredPending($organizationId, $now, $command->limit) as $invitation) {
                if ($invitation->expireWhenDue($now)) {
                    $this->invitations->save($invitation);
                    ++$expired;
                }
            }
            return $expired;
        });
    }
}
