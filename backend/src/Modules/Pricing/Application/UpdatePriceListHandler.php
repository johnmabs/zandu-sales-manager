<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Application;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Pricing\Domain\PriceList\PriceList;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListCode;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListName;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListPriority;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListRepository;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class UpdatePriceListHandler
{
    public function __construct(
        private PriceListRepository $lists,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $guard,
    ) {}

    public function __invoke(UpdatePriceList $command): PriceList
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): PriceList {
            $this->authorization->authorize($command->actorContext, PermissionCode::PriceListUpdate, ResourceScope::organization($organizationId));
            $this->guard->assertTenant($command->actorContext);
            $priceList = $this->lists->get($organizationId, $command->priceListId);
            $priceList->update(PriceListCode::fromString($command->code), PriceListName::fromString($command->name), $priceList->currency(), $command->validFrom, $command->validTo, PriceListPriority::fromInt($command->priority), $command->actorContext->actorId(), $this->clock->now());
            $this->lists->save($priceList);

            return $priceList;
        });
    }
}
