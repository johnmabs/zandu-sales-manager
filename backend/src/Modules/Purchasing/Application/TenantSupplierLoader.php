<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application;

use Zandu\Modules\Purchasing\Domain\Supplier\Supplier;
use Zandu\Modules\Purchasing\Domain\Supplier\SupplierRepository;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\SupplierId;

final readonly class TenantSupplierLoader
{
    public function __construct(private SupplierRepository $suppliers) {}

    public function get(SupplierId $supplierId, ActorContext $actorContext): Supplier
    {
        return $this->suppliers->get($actorContext->organizationId(), $supplierId);
    }
}
