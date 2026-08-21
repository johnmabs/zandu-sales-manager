<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Infrastructure\Security;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Zandu\Modules\IdentityAccess\Domain\Membership\MembershipStatus;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembershipRepository;
use Zandu\Modules\IdentityAccess\Domain\User\UserEmail;
use Zandu\Modules\IdentityAccess\Domain\User\UserRepository;
use Zandu\Modules\IdentityAccess\Domain\User\UserStatus;
use Zandu\Platform\Auth\Security\AuthenticatedUser;
use Zandu\Platform\Auth\Security\BootstrapUserProvider;
use Zandu\SharedKernel\Tenancy\TenantTransaction;

/** @implements UserProviderInterface<AuthenticatedUser> */
final readonly class PersistentUserProvider implements UserProviderInterface
{
    public function __construct(
        private UserRepository $users,
        private OrganizationMembershipRepository $memberships,
        private TenantTransaction $transaction,
        private BootstrapUserProvider $bootstrap,
    ) {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->users->findByEmail(UserEmail::fromString($identifier));
        if (null === $user) {
            return $this->bootstrap->loadUserByIdentifier($identifier);
        }
        if (UserStatus::Active !== $user->status()) {
            throw $this->notFound($identifier);
        }

        $organizationId = $user->defaultOrganizationId();
        $membership = $this->transaction->transactional(
            $organizationId,
            fn() => $this->memberships->findByUser($organizationId, $user->id()),
        );
        if (null === $membership || MembershipStatus::Active !== $membership->status()) {
            throw $this->notFound($identifier);
        }

        return new AuthenticatedUser(
            $user->email()->value(),
            $user->passwordHash(),
            $user->actorId()->toString(),
            $organizationId->toString(),
            $user->id()->toString(),
            $membership->authorizationVersion(),
        );
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof AuthenticatedUser) {
            throw new UnsupportedUserException(sprintf('Unsupported user class "%s".', $user::class));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return AuthenticatedUser::class === $class || is_subclass_of($class, AuthenticatedUser::class);
    }

    private function notFound(string $identifier): UserNotFoundException
    {
        $exception = new UserNotFoundException();
        $exception->setUserIdentifier($identifier);

        return $exception;
    }
}
