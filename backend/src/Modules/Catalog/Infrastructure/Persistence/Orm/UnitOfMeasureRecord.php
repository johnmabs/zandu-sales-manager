<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Infrastructure\Persistence\Orm;

use Doctrine\ORM\Mapping as ORM;
use Zandu\Modules\Catalog\Domain\UnitOfMeasure;

#[ORM\Entity]
#[ORM\Table(name: 'units_of_measure', schema: 'catalog')]
#[ORM\UniqueConstraint(name: 'unit_of_measure_code_tenant_unique', columns: ['organization_id', 'code'])]
#[ORM\UniqueConstraint(name: 'unit_of_measure_tenant_id_unique', columns: ['organization_id', 'id'])]
final class UnitOfMeasureRecord
{
    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'guid')]
        private string $id,
        #[ORM\Column(type: 'guid')]
        private string $organizationId,
        #[ORM\Column(length: 32)]
        private string $code,
        #[ORM\Column(length: 100)]
        private string $name,
        #[ORM\Column(length: 16)]
        private string $dimension,
        #[ORM\Column]
        private int $precision,
        #[ORM\Column(length: 16)]
        private string $roundingMode,
        #[ORM\Column(length: 16)]
        private string $status,
        #[ORM\Version]
        #[ORM\Column(type: 'integer')]
        private int $version,
    ) {}

    public static function fromAggregate(UnitOfMeasure $unit): self
    {
        return new self(
            $unit->id()->toString(),
            $unit->organizationId()->toString(),
            $unit->code()->value(),
            $unit->name()->value(),
            $unit->dimension()->value,
            $unit->precision()->value(),
            $unit->roundingMode()->name,
            $unit->status()->value,
            $unit->version(),
        );
    }

    public function synchronize(UnitOfMeasure $unit): void
    {
        $current = self::fromAggregate($unit);
        $this->name = $current->name;
        $this->dimension = $current->dimension;
        $this->precision = $current->precision;
        $this->roundingMode = $current->roundingMode;
        $this->status = $current->status;
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

    public function dimension(): string
    {
        return $this->dimension;
    }

    public function precision(): int
    {
        return $this->precision;
    }

    public function roundingMode(): string
    {
        return $this->roundingMode;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function version(): int
    {
        return $this->version;
    }
}
