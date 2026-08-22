<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application;

use Zandu\Modules\Organization\Domain\StoreClosure\StoreClosure;

final readonly class StoreClosureViewFactory
{
    public function fromAggregate(StoreClosure $closure): StoreClosureView
    {
        return new StoreClosureView(
            $closure->id()->toString(),
            $closure->storeId()->toString(),
            $closure->status()->value,
            $closure->reason(),
            $closure->blockers(),
            $closure->requestedAt()->format(DATE_ATOM),
            $closure->version(),
        );
    }
}
