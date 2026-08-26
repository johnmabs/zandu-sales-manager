<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Application;

use Zandu\Modules\Catalog\Application\Contract\SaleablePackagingSnapshotProvider;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListRepository;
use Zandu\Modules\Pricing\Domain\ProductPrice\ProductPrice;
use Zandu\Modules\Pricing\Domain\ProductPrice\ProductPriceRepository;
use Zandu\Modules\Pricing\Domain\ProductPrice\ProductPriceTarget;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\ProductPriceId;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class CreateProductPriceHandler
{
    public function __construct(
        private ProductPriceRepository $prices,
        private PriceListRepository $lists,
        private SaleablePackagingSnapshotProvider $packagings,
        private IdGenerator $ids,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $guard,
    ) {}

    public function __invoke(CreateProductPrice $command): ProductPrice
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): ProductPrice {
            $this->authorization->authorize($command->actorContext, PermissionCode::ProductPriceCreate, ResourceScope::organization($organizationId));
            $this->guard->assertTenant($command->actorContext);
            $priceList = $this->lists->get($organizationId, $command->priceListId);
            $this->packagings->provide($organizationId, $command->productId, $command->packagingId);
            $price = ProductPrice::createActive(ProductPriceId::generate($this->ids), $priceList, new ProductPriceTarget($organizationId, $command->productId, $command->packagingId), $command->amount, $command->validFrom, $command->validTo, $command->actorContext->actorId(), $this->clock->now());
            $this->prices->save($price);

            return $price;
        });
    }
}
