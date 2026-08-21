<?php

declare(strict_types=1);

namespace Zandu\Platform\Auth\Security;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<AuthenticatedUser>
 */
final readonly class BootstrapUserProvider implements UserProviderInterface
{
    public function __construct(
        private string $email,
        private string $passwordHash,
        private string $actorId,
        private string $organizationId,
        private string $userId,
    ) {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        if (0 !== strcasecmp($identifier, $this->email)) {
            $exception = new UserNotFoundException();
            $exception->setUserIdentifier($identifier);

            throw $exception;
        }

        return $this->user();
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

    private function user(): AuthenticatedUser
    {
        return new AuthenticatedUser(
            $this->email,
            $this->passwordHash,
            $this->actorId,
            $this->organizationId,
            $this->userId,
        );
    }
}
