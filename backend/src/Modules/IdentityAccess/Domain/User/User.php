<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Domain\User;

use DateTimeImmutable;
use LogicException;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\UserId;

final class User
{
    private function __construct(
        private readonly UserId $id,
        private readonly ActorId $actorId,
        private readonly UserEmail $email,
        private string $passwordHash,
        private UserStatus $status,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private int $version,
    ) {
        if ('' === trim($passwordHash)) {
            throw new LogicException('A password hash is required.');
        }
    }

    public static function register(
        UserId $id,
        ActorId $actorId,
        UserEmail $email,
        string $passwordHash,
        DateTimeImmutable $occurredAt,
    ): self {
        return new self($id, $actorId, $email, $passwordHash, UserStatus::Active, $occurredAt, $occurredAt, 1);
    }

    public static function reconstitute(
        UserId $id,
        ActorId $actorId,
        UserEmail $email,
        string $passwordHash,
        UserStatus $status,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        int $version,
    ): self {
        return new self($id, $actorId, $email, $passwordHash, $status, $createdAt, $updatedAt, $version);
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function actorId(): ActorId
    {
        return $this->actorId;
    }

    public function email(): UserEmail
    {
        return $this->email;
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    public function status(): UserStatus
    {
        return $this->status;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function version(): int
    {
        return $this->version;
    }
}
