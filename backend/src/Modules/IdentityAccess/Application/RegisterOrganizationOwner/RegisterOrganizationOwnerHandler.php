<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application\RegisterOrganizationOwner;

use InvalidArgumentException;
use LogicException;
use Zandu\Modules\IdentityAccess\Application\Contract\PasswordHasher;
use Zandu\Modules\IdentityAccess\Domain\User\User;
use Zandu\Modules\IdentityAccess\Domain\User\UserEmail;
use Zandu\Modules\IdentityAccess\Domain\User\UserRepository;
use Zandu\Modules\Organization\Application\Contract\InitialOrganizationOwnerProvisioner;
use Zandu\Modules\Organization\Application\Contract\OrganizationOnboardingProvisioner;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Context\ActorType;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\UserId;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class RegisterOrganizationOwnerHandler
{
    public function __construct(
        private UserRepository $users,
        private OrganizationOnboardingProvisioner $organizations,
        private InitialOrganizationOwnerProvisioner $initialOwner,
        private PasswordHasher $passwordHasher,
        private IdGenerator $idGenerator,
        private Clock $clock,
        private TenantTransaction $transaction,
    ) {}

    public function __invoke(RegisterOrganizationOwner $command): RegisteredOrganizationOwner
    {
        $email = UserEmail::fromString($command->email);
        if (null !== $this->users->findByEmail($email)) {
            throw new LogicException('A user account already exists for this email.');
        }
        if (strlen($command->password) < 12 || strlen($command->password) > 4096) {
            throw new InvalidArgumentException('Password must contain between 12 and 4096 characters.');
        }

        $userId = UserId::generate($this->idGenerator);
        $actorId = ActorId::generate($this->idGenerator);
        $organizationId = OrganizationId::generate($this->idGenerator);

        return $this->transaction->transactional($organizationId, function () use ($command, $email, $userId, $actorId, $organizationId): RegisteredOrganizationOwner {
            $now = $this->clock->now();
            $actorContext = new ActorContext($actorId, $organizationId, ActorType::User, $command->correlationId, $now, $userId, null, $email->value());
            $user = User::register($userId, $actorId, $email, $organizationId, $this->passwordHasher->hash($command->password), $now);
            $this->organizations->provision(
                $organizationId,
                $actorId,
                $command->organizationName,
                $command->countryCode,
                $command->defaultCurrency,
                $command->defaultTimeZone,
                $command->defaultLocale,
                $now,
            );
            $this->users->save($user);
            $this->initialOwner->provision($organizationId, $actorContext, $now);

            return new RegisteredOrganizationOwner($userId, $organizationId);
        });
    }
}
