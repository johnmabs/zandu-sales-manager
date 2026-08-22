<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application;

use Zandu\Modules\Organization\Domain\Store\Store;

final readonly class StoreViewFactory
{
    public function fromAggregate(Store $store): StoreView
    {
        return new StoreView(
            $store->id()->toString(),
            $store->organizationId()->toString(),
            $store->code()->value(),
            $store->name()->value(),
            $store->status()->value,
            $store->address()?->value(),
            $store->timeZone()->value(),
            $store->currency()->code(),
            $store->locale()->value(),
            $store->updatedAt()->format(DATE_ATOM),
            $store->version(),
        );
    }
}
