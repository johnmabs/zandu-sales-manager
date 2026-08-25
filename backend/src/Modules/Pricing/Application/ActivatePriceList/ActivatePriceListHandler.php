<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Application\ActivatePriceList;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Pricing\Domain\PriceList\PriceList;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListRepository;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\SecurityAudit\ResourceReference;
use Zandu\SharedKernel\SecurityAudit\SafeAuditMetadata;
use Zandu\SharedKernel\SecurityAudit\SecurityAction;
use Zandu\SharedKernel\SecurityAudit\SecurityAuditTrail;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class ActivatePriceListHandler
{
    public function __construct(
        private PriceListRepository $priceLists,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $operationalGuard,
        private SecurityAuditTrail $audit,
    ) {}

    public function __invoke(ActivatePriceList $command): PriceList
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): PriceList {
            $this->authorization->authorize($command->actorContext, PermissionCode::PriceListActivate, ResourceScope::organization($organizationId));
            $this->operationalGuard->assertTenant($command->actorContext);
            $priceList = $this->priceLists->get($organizationId, $command->priceListId);
            $now = $this->clock->now();
            $priceList->activate($command->actorContext->actorId(), $now);
            $this->priceLists->save($priceList);
            $this->audit->recordSuccess($command->actorContext, SecurityAction::PriceListActivated, ResourceReference::for('price_list', $priceList->id()), SafeAuditMetadata::empty(), $now);

            return $priceList;
        });
    }
}
