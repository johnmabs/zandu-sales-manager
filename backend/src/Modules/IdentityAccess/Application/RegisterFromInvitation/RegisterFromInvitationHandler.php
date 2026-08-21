<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application\RegisterFromInvitation;

use InvalidArgumentException;
use LogicException;
use Zandu\Modules\IdentityAccess\Application\AcceptOrganizationInvitation\AcceptOrganizationInvitation;
use Zandu\Modules\IdentityAccess\Application\AcceptOrganizationInvitation\AcceptOrganizationInvitationHandler;
use Zandu\Modules\IdentityAccess\Application\Contract\InvitationTokenService;
use Zandu\Modules\IdentityAccess\Application\Contract\PasswordHasher;
use Zandu\Modules\IdentityAccess\Domain\Invitation\OrganizationInvitationNotFound;
use Zandu\Modules\IdentityAccess\Domain\Invitation\OrganizationInvitationRepository;
use Zandu\Modules\IdentityAccess\Domain\User\User;
use Zandu\Modules\IdentityAccess\Domain\User\UserEmail;
use Zandu\Modules\IdentityAccess\Domain\User\UserRepository;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Context\ActorType;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\UserId;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class RegisterFromInvitationHandler
{
    public function __construct(
        private UserRepository $users,
        private OrganizationInvitationRepository $invitations,
        private InvitationTokenService $tokens,
        private PasswordHasher $passwordHasher,
        private AcceptOrganizationInvitationHandler $acceptInvitation,
        private IdGenerator $idGenerator,
        private Clock $clock,
        private TenantTransaction $transaction,
    ) {}

    public function __invoke(RegisterFromInvitation $command): RegisteredFromInvitation
    {
        if (strlen($command->password) < 12 || strlen($command->password) > 4096) {
            throw new InvalidArgumentException('Password must contain between 12 and 4096 characters.');
        }
        $organizationId = $this->tokens->organizationId($command->token);

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): RegisteredFromInvitation {
            try {
                $invitation = $this->invitations->getByTokenHash($organizationId, $this->tokens->hash($command->token));
            } catch (OrganizationInvitationNotFound $exception) {
                throw new InvalidArgumentException('The invitation is invalid or inactive.', previous: $exception);
            }
            $email = UserEmail::fromString($invitation->email()->value());
            if (null !== $this->users->findByEmail($email)) {
                throw new LogicException('A user account already exists for this invitation email; sign in to accept it.');
            }

            $now = $this->clock->now();
            $userId = UserId::generate($this->idGenerator);
            $actorId = ActorId::generate($this->idGenerator);
            $this->users->save(User::register(
                $userId,
                $actorId,
                $email,
                $organizationId,
                $this->passwordHasher->hash($command->password),
                $now,
            ));
            $context = new ActorContext($actorId, $organizationId, ActorType::User, $command->correlationId, $now, $userId, null, $email->value());
            $this->acceptInvitation->handleInCurrentTenantTransaction(new AcceptOrganizationInvitation($command->token, $context), $organizationId);

            return new RegisteredFromInvitation($userId, $organizationId);
        });
    }
}
