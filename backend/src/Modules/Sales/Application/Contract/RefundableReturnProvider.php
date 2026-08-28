<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application\Contract;

use Zandu\SharedKernel\Identity\{OrganizationId, ReturnSaleId};

interface RefundableReturnProvider
{
    public function provide(OrganizationId $organizationId, ReturnSaleId $returnSaleId): RefundableReturn;
}
