<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Application;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Pricing\Domain\PriceList\PriceList;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListRepository;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class DeactivatePriceListHandler
{
    public function __construct(
        private PriceListRepository $lists,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $guard,
    ) {}

    public function __invoke(DeactivatePriceList $command): PriceList
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): PriceList {
            $this->authorization->authorize($command->actorContext, PermissionCode::PriceListUpdate, ResourceScope::organization($organizationId));
            $this->guard->assertTenant($command->actorContext);
            $priceList = $this->lists->get($organizationId, $command->priceListId);
            $priceList->deactivate($command->actorContext->actorId(), $this->clock->now());
            $this->lists->save($priceList);

            return $priceList;
        });
    }
}
