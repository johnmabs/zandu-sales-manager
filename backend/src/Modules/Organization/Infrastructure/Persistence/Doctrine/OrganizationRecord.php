<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Infrastructure\Persistence\Doctrine;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Zandu\Modules\Organization\Domain\Organization;

#[ORM\Entity]
#[ORM\Table(name: 'organizations', schema: 'organization')]
final class OrganizationRecord
{
    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'guid')]
        private string $id,
        #[ORM\Column(length: 160)]
        private string $name,
        #[ORM\Column(length: 32)]
        private string $status,
        #[ORM\Column(length: 2)]
        private string $countryCode,
        #[ORM\Column(length: 3)]
        private string $defaultCurrency,
        #[ORM\Column(length: 64)]
        private string $defaultTimeZone,
        #[ORM\Column(length: 16)]
        private string $defaultLocale,
        #[ORM\Column(type: 'guid')]
        private string $createdBy,
        #[ORM\Column(type: 'datetimetz_immutable')]
        private DateTimeImmutable $createdAt,
        #[ORM\Column(type: 'guid')]
        private string $updatedBy,
        #[ORM\Column(type: 'datetimetz_immutable')]
        private DateTimeImmutable $updatedAt,
        #[ORM\Column(type: 'guid', nullable: true)]
        private ?string $suspendedBy,
        #[ORM\Column(type: 'datetimetz_immutable', nullable: true)]
        private ?DateTimeImmutable $suspendedAt,
        #[ORM\Column(type: 'guid', nullable: true)]
        private ?string $closureRequestedBy,
        #[ORM\Column(type: 'datetimetz_immutable', nullable: true)]
        private ?DateTimeImmutable $closureRequestedAt,
        #[ORM\Column(type: 'guid', nullable: true)]
        private ?string $closedBy,
        #[ORM\Column(type: 'datetimetz_immutable', nullable: true)]
        private ?DateTimeImmutable $closedAt,
        #[ORM\Column]
        private int $version,
    ) {}

    public static function fromAggregate(Organization $organization): self
    {
        return new self(
            $organization->id()->toString(),
            $organization->name()->value(),
            $organization->status()->value,
            $organization->countryCode()->value(),
            $organization->defaultCurrency()->code(),
            $organization->defaultTimeZone()->value(),
            $organization->defaultLocale()->value(),
            $organization->createdBy()->toString(),
            $organization->createdAt(),
            $organization->updatedBy()->toString(),
            $organization->updatedAt(),
            $organization->suspendedBy()?->toString(),
            $organization->suspendedAt(),
            $organization->closureRequestedBy()?->toString(),
            $organization->closureRequestedAt(),
            $organization->closedBy()?->toString(),
            $organization->closedAt(),
            $organization->version(),
        );
    }

    public function synchronize(Organization $organization): void
    {
        $current = self::fromAggregate($organization);
        $this->name = $current->name;
        $this->status = $current->status;
        $this->countryCode = $current->countryCode;
        $this->defaultCurrency = $current->defaultCurrency;
        $this->defaultTimeZone = $current->defaultTimeZone;
        $this->defaultLocale = $current->defaultLocale;
        $this->updatedBy = $current->updatedBy;
        $this->updatedAt = $current->updatedAt;
        $this->suspendedBy = $current->suspendedBy;
        $this->suspendedAt = $current->suspendedAt;
        $this->closureRequestedBy = $current->closureRequestedBy;
        $this->closureRequestedAt = $current->closureRequestedAt;
        $this->closedBy = $current->closedBy;
        $this->closedAt = $current->closedAt;
        $this->version = $current->version;
    }

    public function id(): string
    {
        return $this->id;
    }
    public function name(): string
    {
        return $this->name;
    }
    public function status(): string
    {
        return $this->status;
    }
    public function countryCode(): string
    {
        return $this->countryCode;
    }
    public function defaultCurrency(): string
    {
        return $this->defaultCurrency;
    }
    public function defaultTimeZone(): string
    {
        return $this->defaultTimeZone;
    }
    public function defaultLocale(): string
    {
        return $this->defaultLocale;
    }
    public function createdBy(): string
    {
        return $this->createdBy;
    }
    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function updatedBy(): string
    {
        return $this->updatedBy;
    }
    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
    public function suspendedBy(): ?string
    {
        return $this->suspendedBy;
    }
    public function suspendedAt(): ?DateTimeImmutable
    {
        return $this->suspendedAt;
    }
    public function closureRequestedBy(): ?string
    {
        return $this->closureRequestedBy;
    }
    public function closureRequestedAt(): ?DateTimeImmutable
    {
        return $this->closureRequestedAt;
    }
    public function closedBy(): ?string
    {
        return $this->closedBy;
    }
    public function closedAt(): ?DateTimeImmutable
    {
        return $this->closedAt;
    }
    public function version(): int
    {
        return $this->version;
    }
}
