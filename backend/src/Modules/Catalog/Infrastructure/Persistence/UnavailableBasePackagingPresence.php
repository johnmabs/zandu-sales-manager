<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Infrastructure\Persistence;

use Zandu\Modules\Catalog\Application\Contract\BasePackagingPresence;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\UnitOfMeasureId;

/** Fail-closed adapter replaced by ProductPackaging persistence in Epic 2.5. */
final readonly class UnavailableBasePackagingPresence implements BasePackagingPresence
{
    public function exists(
        OrganizationId $organizationId,
        ProductId $productId,
        UnitOfMeasureId $baseUnitId,
    ): bool {
        return false;
    }
}
