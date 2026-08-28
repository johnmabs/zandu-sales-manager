<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Infrastructure\Persistence\Orm;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Zandu\Modules\Purchasing\Domain\Supplier\Supplier;

#[ORM\Entity]
#[ORM\Table(name: 'supplier', schema: 'purchasing')]
#[ORM\UniqueConstraint(name: 'supplier_tenant_id_unique', columns: ['organization_id', 'id'])]
final class SupplierRecord
{
    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'guid')]
        private string $id,
        #[ORM\Column(type: 'guid')]
        private string $organizationId,
        #[ORM\Column(length: 160)]
        private string $name,
        #[ORM\Column(length: 64, nullable: true)]
        private ?string $phone,
        #[ORM\Column(length: 254, nullable: true)]
        private ?string $email,
        #[ORM\Column(length: 500, nullable: true)]
        private ?string $address,
        #[ORM\Column(type: 'text', nullable: true)]
        private ?string $notes,
        #[ORM\Column(length: 16)]
        private string $status,
        #[ORM\Column(type: 'datetimetz_immutable')]
        private DateTimeImmutable $createdAt,
        #[ORM\Column(type: 'guid')]
        private string $createdBy,
        #[ORM\Column(type: 'datetimetz_immutable', nullable: true)]
        private ?DateTimeImmutable $updatedAt,
        #[ORM\Column(type: 'guid', nullable: true)]
        private ?string $updatedBy,
        #[ORM\Version]
        #[ORM\Column(type: 'integer')]
        private int $version,
    ) {}

    public static function fromAggregate(Supplier $supplier): self
    {
        return new self(
            $supplier->id()->toString(),
            $supplier->organizationId()->toString(),
            $supplier->name()->value(),
            $supplier->phone(),
            $supplier->email(),
            $supplier->address(),
            $supplier->notes(),
            $supplier->status()->value,
            $supplier->createdAt(),
            $supplier->createdBy()->toString(),
            $supplier->updatedAt(),
            $supplier->updatedBy()?->toString(),
            $supplier->version(),
        );
    }

    public function synchronize(Supplier $supplier): void
    {
        $current = self::fromAggregate($supplier);
        $this->name = $current->name;
        $this->phone = $current->phone;
        $this->email = $current->email;
        $this->address = $current->address;
        $this->notes = $current->notes;
        $this->status = $current->status;
        $this->updatedAt = $current->updatedAt;
        $this->updatedBy = $current->updatedBy;
    }

    public function id(): string
    {
        return $this->id;
    }
    public function organizationId(): string
    {
        return $this->organizationId;
    }
    public function name(): string
    {
        return $this->name;
    }
    public function phone(): ?string
    {
        return $this->phone;
    }
    public function email(): ?string
    {
        return $this->email;
    }
    public function address(): ?string
    {
        return $this->address;
    }
    public function notes(): ?string
    {
        return $this->notes;
    }
    public function status(): string
    {
        return $this->status;
    }
    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function createdBy(): string
    {
        return $this->createdBy;
    }
    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }
    public function updatedBy(): ?string
    {
        return $this->updatedBy;
    }
    public function version(): int
    {
        return $this->version;
    }
}
