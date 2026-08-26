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

final readonly class ArchivePriceListHandler
{
    public function __construct(
        private PriceListRepository $lists,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $guard,
    ) {}

    public function __invoke(ArchivePriceList $command): PriceList
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): PriceList {
            $this->authorization->authorize($command->actorContext, PermissionCode::PriceListArchive, ResourceScope::organization($organizationId));
            $this->guard->assertTenant($command->actorContext);
            $priceList = $this->lists->get($organizationId, $command->priceListId);
            $priceList->archive($command->actorContext->actorId(), $this->clock->now());
            $this->lists->save($priceList);

            return $priceList;
        });
    }
}
