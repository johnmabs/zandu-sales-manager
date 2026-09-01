<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application;

use Zandu\Modules\Organization\Application\Contract\StoreClosureBlockerProvider;
use Zandu\SharedKernel\Identity\{OrganizationId, StoreId};

final readonly class CompositeStoreClosureBlockerProvider implements StoreClosureBlockerProvider
{
    /** @param iterable<StoreClosureBlockerProvider> $providers */
    public function __construct(private iterable $providers) {}

    public function blockers(OrganizationId $organizationId, StoreId $storeId): array
    {
        $blockers = [];
        foreach ($this->providers as $provider) {
            foreach ($provider->blockers($organizationId, $storeId) as $blocker) {
                $blockers[$blocker] = true;
            }
        }

        return array_keys($blockers);
    }
}
