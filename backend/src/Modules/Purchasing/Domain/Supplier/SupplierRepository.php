<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Domain\Supplier;

use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\SupplierId;

interface SupplierRepository
{
    public function save(Supplier $supplier): void;

    /** @throws SupplierNotFound */
    public function get(OrganizationId $organizationId, SupplierId $supplierId): Supplier;

    public function find(OrganizationId $organizationId, SupplierId $supplierId): ?Supplier;

    /** @return list<Supplier> */
    public function findAll(OrganizationId $organizationId): array;
}
