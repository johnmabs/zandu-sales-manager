<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application;

use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Organization\Application\Contract\OperationalMode;
use Zandu\Modules\Organization\Domain\Organization;
use Zandu\Modules\Organization\Domain\OrganizationRepository;
use Zandu\Modules\Organization\Domain\Store\Store;
use Zandu\SharedKernel\Context\ActorContext;

final readonly class TenantOperationalGuard implements OperationalGuard
{
    public function __construct(
        private OrganizationRepository $organizations,
        private OrganizationOperationalGuard $organizationGuard,
        private StoreOperationalGuard $storeGuard,
    ) {}

    public function assertOrganization(Organization $organization, OperationalMode $mode = OperationalMode::Standard): void
    {
        $this->organizationGuard->assertAllows($organization, $mode);
    }

    public function assertStore(ActorContext $actorContext, Store $store, OperationalMode $mode = OperationalMode::Standard): void
    {
        $organization = $this->organizations->get($actorContext->organizationId());
        $this->organizationGuard->assertAllows($organization, $mode);
        $this->storeGuard->assertAllows($store, $mode);
    }
}
