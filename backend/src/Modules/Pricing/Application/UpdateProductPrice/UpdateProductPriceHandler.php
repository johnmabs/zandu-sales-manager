<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Application\UpdateProductPrice;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListRepository;
use Zandu\Modules\Pricing\Domain\ProductPrice\ProductPrice;
use Zandu\Modules\Pricing\Domain\ProductPrice\ProductPriceRepository;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\SecurityAudit\ResourceReference;
use Zandu\SharedKernel\SecurityAudit\SafeAuditMetadata;
use Zandu\SharedKernel\SecurityAudit\SecurityAction;
use Zandu\SharedKernel\SecurityAudit\SecurityAuditTrail;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class UpdateProductPriceHandler
{
    public function __construct(
        private ProductPriceRepository $productPrices,
        private PriceListRepository $priceLists,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $operationalGuard,
        private SecurityAuditTrail $audit,
    ) {}

    public function __invoke(UpdateProductPrice $command): ProductPrice
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): ProductPrice {
            $this->authorization->authorize($command->actorContext, PermissionCode::ProductPriceUpdate, ResourceScope::organization($organizationId));
            $this->operationalGuard->assertTenant($command->actorContext);
            $productPrice = $this->productPrices->get($organizationId, $command->productPriceId);
            $command->expectedVersion->assertMatches($productPrice);
            $priceList = $this->priceLists->get($organizationId, $productPrice->priceListId());
            $now = $this->clock->now();
            $productPrice->update($priceList, $command->amount, $command->validFrom, $command->validTo, $command->actorContext->actorId(), $now);
            $this->productPrices->save($productPrice);
            $this->audit->recordSuccess($command->actorContext, SecurityAction::ProductPriceUpdated, ResourceReference::for('product_price', $productPrice->id()), SafeAuditMetadata::empty(), $now);

            return $productPrice;
        });
    }
}
