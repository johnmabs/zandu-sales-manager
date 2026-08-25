<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain;

use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\UnitOfMeasureId;

interface UnitOfMeasureRepository
{
    public function save(UnitOfMeasure $unit): void;

    /** @throws UnitOfMeasureNotFound */
    public function get(OrganizationId $organizationId, UnitOfMeasureId $unitId): UnitOfMeasure;

    public function find(OrganizationId $organizationId, UnitOfMeasureId $unitId): ?UnitOfMeasure;

    /** @return list<UnitOfMeasure> */
    public function findAll(OrganizationId $organizationId): array;

    public function codeExists(OrganizationId $organizationId, UnitOfMeasureCode $code): bool;
}
