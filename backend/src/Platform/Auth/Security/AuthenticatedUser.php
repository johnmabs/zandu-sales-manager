<?php

declare(strict_types=1);

namespace Zandu\Platform\Auth\Security;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class AuthenticatedUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private string $email,
        private string $passwordHash,
        private string $actorId,
        private string $organizationId,
        private string $userId,
        private int $authorizationVersion = 0,
    ) {}

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void {}

    public function actorId(): string
    {
        return $this->actorId;
    }

    public function organizationId(): string
    {
        return $this->organizationId;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function authorizationVersion(): int
    {
        return $this->authorizationVersion;
    }
}
