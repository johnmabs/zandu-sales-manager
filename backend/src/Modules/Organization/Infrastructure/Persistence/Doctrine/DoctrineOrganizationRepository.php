<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Zandu\Modules\Organization\Domain\CountryCode;
use Zandu\Modules\Organization\Domain\Locale;
use Zandu\Modules\Organization\Domain\Organization;
use Zandu\Modules\Organization\Domain\OrganizationName;
use Zandu\Modules\Organization\Domain\OrganizationNotFound;
use Zandu\Modules\Organization\Domain\OrganizationRepository;
use Zandu\Modules\Organization\Domain\OrganizationStatus;
use Zandu\Modules\Organization\Domain\TimeZone;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\UuidFactory;
use Zandu\SharedKernel\Money\Currency;

final readonly class DoctrineOrganizationRepository implements OrganizationRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UuidFactory $uuidFactory,
    ) {}

    public function save(Organization $organization): void
    {
        $record = $this->entityManager->find(OrganizationRecord::class, $organization->id()->toString());

        if ($record instanceof OrganizationRecord) {
            $record->synchronize($organization);
        } else {
            $this->entityManager->persist(OrganizationRecord::fromAggregate($organization));
        }

        $this->entityManager->flush();
    }

    public function get(OrganizationId $id): Organization
    {
        return $this->find($id) ?? throw OrganizationNotFound::withId($id);
    }

    public function find(OrganizationId $id): ?Organization
    {
        $record = $this->entityManager->find(OrganizationRecord::class, $id->toString());

        return $record instanceof OrganizationRecord ? $this->toAggregate($record) : null;
    }

    private function toAggregate(OrganizationRecord $record): Organization
    {
        return Organization::reconstitute(
            OrganizationId::fromString($record->id(), $this->uuidFactory),
            OrganizationName::fromString($record->name()),
            OrganizationStatus::from($record->status()),
            CountryCode::fromString($record->countryCode()),
            Currency::fromCode($record->defaultCurrency()),
            TimeZone::fromString($record->defaultTimeZone()),
            Locale::fromString($record->defaultLocale()),
            ActorId::fromString($record->createdBy(), $this->uuidFactory),
            $record->createdAt(),
            ActorId::fromString($record->updatedBy(), $this->uuidFactory),
            $record->updatedAt(),
            null !== $record->suspendedBy() ? ActorId::fromString($record->suspendedBy(), $this->uuidFactory) : null,
            $record->suspendedAt(),
            null !== $record->closureRequestedBy() ? ActorId::fromString($record->closureRequestedBy(), $this->uuidFactory) : null,
            $record->closureRequestedAt(),
            null !== $record->closedBy() ? ActorId::fromString($record->closedBy(), $this->uuidFactory) : null,
            $record->closedAt(),
            $record->version(),
        );
    }
}
