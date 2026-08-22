<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Presentation\Api;

use Zandu\Modules\Organization\Application\StoreClosureView;
use Zandu\Modules\Organization\Application\StoreView;

final readonly class StoreResourceFactory
{
    public function fromView(StoreView $store): StoreResource
    {
        return new StoreResource(
            $store->id,
            $store->organizationId,
            $store->code,
            $store->name,
            $store->status,
            $store->address,
            $store->timeZone,
            $store->currency,
            $store->locale,
            $store->updatedAt,
            $store->version,
        );
    }

    public function closureFromView(StoreClosureView $closure): StoreClosureResource
    {
        return new StoreClosureResource(
            $closure->id,
            $closure->storeId,
            $closure->status,
            $closure->reason,
            $closure->blockers,
            $closure->requestedAt,
            $closure->version,
        );
    }
}
