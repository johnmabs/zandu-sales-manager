<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application;

use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Organization\Application\Contract\OperationalMode;
use Zandu\Modules\Organization\Domain\Organization;
use Zandu\Modules\Organization\Domain\OrganizationRepository;
use Zandu\Modules\Organization\Domain\Store\StoreRepository;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\StoreId;

final readonly class TenantOperationalGuard implements OperationalGuard
{
    public function __construct(
        private OrganizationRepository $organizations,
        private OrganizationOperationalGuard $organizationGuard,
        private StoreOperationalGuard $storeGuard,
        private StoreRepository $stores,
    ) {}

    public function assertTenant(ActorContext $actorContext, OperationalMode $mode = OperationalMode::Standard): void
    {
        $this->assertOrganization($this->organizations->get($actorContext->organizationId()), $mode);
    }

    private function assertOrganization(Organization $organization, OperationalMode $mode = OperationalMode::Standard): void
    {
        $this->organizationGuard->assertAllows($organization, $mode);
    }

    public function assertStore(ActorContext $actorContext, StoreId $storeId, OperationalMode $mode = OperationalMode::Standard): void
    {
        $this->assertTenant($actorContext, $mode);
        $this->storeGuard->assertAllows($this->stores->get($actorContext->organizationId(), $storeId), $mode);
    }
}
