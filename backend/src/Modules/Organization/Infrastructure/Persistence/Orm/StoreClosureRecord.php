<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Infrastructure\Persistence\Orm;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Zandu\Modules\Organization\Domain\StoreClosure\StoreClosure;

#[ORM\Entity]
#[ORM\Table(name: 'store_closures', schema: 'organization')]
final class StoreClosureRecord
{
    /** @param list<string> $blockers */
    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'guid')]
        private string $id,
        #[ORM\Column(type: 'guid')]
        private string $organizationId,
        #[ORM\Column(type: 'guid')]
        private string $storeId,
        #[ORM\Column(length: 32)]
        private string $status,
        #[ORM\Column(length: 1000)]
        private string $reason,
        #[ORM\Column(type: 'json')]
        private array $blockers,
        #[ORM\Column(type: 'guid')]
        private string $requestedBy,
        #[ORM\Column(type: 'datetimetz_immutable')]
        private DateTimeImmutable $requestedAt,
        #[ORM\Column(type: 'guid', nullable: true)]
        private ?string $completedBy,
        #[ORM\Column(type: 'datetimetz_immutable', nullable: true)]
        private ?DateTimeImmutable $completedAt,
        #[ORM\Column]
        private int $version,
    ) {}

    public static function fromAggregate(StoreClosure $closure): self
    {
        return new self(
            $closure->id()->toString(),
            $closure->organizationId()->toString(),
            $closure->storeId()->toString(),
            $closure->status()->value,
            $closure->reason(),
            $closure->blockers(),
            $closure->requestedBy()->toString(),
            $closure->requestedAt(),
            $closure->completedBy()?->toString(),
            $closure->completedAt(),
            $closure->version(),
        );
    }

    public function synchronize(StoreClosure $closure): void
    {
        $current = self::fromAggregate($closure);
        $this->status = $current->status;
        $this->blockers = $current->blockers;
        $this->completedBy = $current->completedBy;
        $this->completedAt = $current->completedAt;
        $this->version = $current->version;
    }

    public function id(): string
    {
        return $this->id;
    }
    public function organizationId(): string
    {
        return $this->organizationId;
    }
    public function storeId(): string
    {
        return $this->storeId;
    }
    public function status(): string
    {
        return $this->status;
    }
    public function reason(): string
    {
        return $this->reason;
    }
    /** @return list<string> */
    public function blockers(): array
    {
        return $this->blockers;
    }
    public function requestedBy(): string
    {
        return $this->requestedBy;
    }
    public function requestedAt(): DateTimeImmutable
    {
        return $this->requestedAt;
    }
    public function completedBy(): ?string
    {
        return $this->completedBy;
    }
    public function completedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }
    public function version(): int
    {
        return $this->version;
    }
}
