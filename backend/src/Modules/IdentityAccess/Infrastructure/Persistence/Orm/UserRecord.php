<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Infrastructure\Persistence\Orm;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Zandu\Modules\IdentityAccess\Domain\User\User;

#[ORM\Entity]
#[ORM\Table(name: 'users', schema: 'identity_access')]
final class UserRecord
{
    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'guid')]
        private string $id,
        #[ORM\Column(type: 'guid', unique: true)]
        private string $actorId,
        #[ORM\Column(length: 254, unique: true)]
        private string $email,
        #[ORM\Column(length: 255)]
        private string $passwordHash,
        #[ORM\Column(length: 16)]
        private string $status,
        #[ORM\Column(type: 'datetimetz_immutable')]
        private DateTimeImmutable $createdAt,
        #[ORM\Column(type: 'datetimetz_immutable')]
        private DateTimeImmutable $updatedAt,
        #[ORM\Column]
        private int $version,
    ) {}

    public static function fromAggregate(User $user): self
    {
        return new self(
            $user->id()->toString(),
            $user->actorId()->toString(),
            $user->email()->value(),
            $user->passwordHash(),
            $user->status()->value,
            $user->createdAt(),
            $user->updatedAt(),
            $user->version(),
        );
    }

    public function id(): string
    {
        return $this->id;
    }
    public function actorId(): string
    {
        return $this->actorId;
    }
    public function email(): string
    {
        return $this->email;
    }
    public function passwordHash(): string
    {
        return $this->passwordHash;
    }
    public function status(): string
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
