<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Infrastructure\Persistence\Orm;

use Doctrine\ORM\EntityManagerInterface;
use Zandu\Modules\IdentityAccess\Domain\User\User;
use Zandu\Modules\IdentityAccess\Domain\User\UserEmail;
use Zandu\Modules\IdentityAccess\Domain\User\UserRepository;
use Zandu\Modules\IdentityAccess\Domain\User\UserStatus;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\UserId;
use Zandu\SharedKernel\Identity\UuidFactory;

final readonly class DoctrineUserRepository implements UserRepository
{
    public function __construct(private EntityManagerInterface $entityManager, private UuidFactory $uuidFactory) {}

    public function save(User $user): void
    {
        $this->entityManager->persist(UserRecord::fromAggregate($user));
        $this->entityManager->flush();
    }

    public function findByEmail(UserEmail $email): ?User
    {
        $record = $this->entityManager->getRepository(UserRecord::class)->findOneBy(['email' => $email->value()]);
        if (!$record instanceof UserRecord) {
            return null;
        }

        return User::reconstitute(
            UserId::fromString($record->id(), $this->uuidFactory),
            ActorId::fromString($record->actorId(), $this->uuidFactory),
            UserEmail::fromString($record->email()),
            OrganizationId::fromString($record->defaultOrganizationId(), $this->uuidFactory),
            $record->passwordHash(),
            UserStatus::from($record->status()),
            $record->createdAt(),
            $record->updatedAt(),
            $record->version(),
        );
    }
}
