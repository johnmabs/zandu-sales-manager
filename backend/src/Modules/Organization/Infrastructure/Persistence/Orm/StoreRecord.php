<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Infrastructure\Persistence\Orm;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Zandu\Modules\Organization\Domain\Store\Store;

#[ORM\Entity]
#[ORM\Table(name: 'stores', schema: 'organization')]
#[ORM\UniqueConstraint(name: 'store_code_tenant_unique', columns: ['organization_id', 'code'])]
final class StoreRecord
{
    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'guid')]
        private string $id,
        #[ORM\Column(type: 'guid')]
        private string $organizationId,
        #[ORM\Column(length: 32)]
        private string $code,
        #[ORM\Column(length: 160)]
        private string $name,
        #[ORM\Column(length: 32)]
        private string $status,
        #[ORM\Column(length: 500, nullable: true)]
        private ?string $address,
        #[ORM\Column(length: 64)]
        private string $timeZone,
        #[ORM\Column(length: 3)]
        private string $currency,
        #[ORM\Column(length: 16)]
        private string $locale,
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
        #[ORM\Column(length: 32, nullable: true)]
        private ?string $statusBeforeClosure,
        #[ORM\Column(type: 'guid', nullable: true)]
        private ?string $closureRequestedBy,
        #[ORM\Column(type: 'datetimetz_immutable', nullable: true)]
        private ?DateTimeImmutable $closureRequestedAt,
        #[ORM\Column(type: 'guid', nullable: true)]
        private ?string $closedBy,
        #[ORM\Column(type: 'datetimetz_immutable', nullable: true)]
        private ?DateTimeImmutable $closedAt,
        #[ORM\Version]
        #[ORM\Column(type: 'integer')]
        private int $version,
    ) {}

    public static function fromAggregate(Store $store): self
    {
        return new self(
            $store->id()->toString(),
            $store->organizationId()->toString(),
            $store->code()->value(),
            $store->name()->value(),
            $store->status()->value,
            $store->address()?->value(),
            $store->timeZone()->value(),
            $store->currency()->code(),
            $store->locale()->value(),
            $store->createdBy()->toString(),
            $store->createdAt(),
            $store->updatedBy()->toString(),
            $store->updatedAt(),
            $store->suspendedBy()?->toString(),
            $store->suspendedAt(),
            $store->statusBeforeClosure()?->value,
            $store->closureRequestedBy()?->toString(),
            $store->closureRequestedAt(),
            $store->closedBy()?->toString(),
            $store->closedAt(),
            $store->version(),
        );
    }

    public function synchronize(Store $store): void
    {
        $current = self::fromAggregate($store);
        $this->name = $current->name;
        $this->status = $current->status;
        $this->address = $current->address;
        $this->timeZone = $current->timeZone;
        $this->currency = $current->currency;
        $this->locale = $current->locale;
        $this->updatedBy = $current->updatedBy;
        $this->updatedAt = $current->updatedAt;
        $this->suspendedBy = $current->suspendedBy;
        $this->suspendedAt = $current->suspendedAt;
        $this->statusBeforeClosure = $current->statusBeforeClosure;
        $this->closureRequestedBy = $current->closureRequestedBy;
        $this->closureRequestedAt = $current->closureRequestedAt;
        $this->closedBy = $current->closedBy;
        $this->closedAt = $current->closedAt;
    }

    public function id(): string
    {
        return $this->id;
    }
    public function organizationId(): string
    {
        return $this->organizationId;
    }
    public function code(): string
    {
        return $this->code;
    }
    public function name(): string
    {
        return $this->name;
    }
    public function status(): string
    {
        return $this->status;
    }
    public function address(): ?string
    {
        return $this->address;
    }
    public function timeZone(): string
    {
        return $this->timeZone;
    }
    public function currency(): string
    {
        return $this->currency;
    }
    public function locale(): string
    {
        return $this->locale;
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
    public function statusBeforeClosure(): ?string
    {
        return $this->statusBeforeClosure;
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
