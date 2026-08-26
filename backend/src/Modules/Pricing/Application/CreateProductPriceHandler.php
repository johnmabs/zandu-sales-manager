<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Application;

use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackagingRepository;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListRepository;
use Zandu\Modules\Pricing\Domain\ProductPrice\{ProductPrice,ProductPriceRepository,ProductPriceTarget};
use Zandu\SharedKernel\Access\{PermissionCode,ResourceScope};
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\ProductPriceId;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class CreateProductPriceHandler
{
    public function __construct(private ProductPriceRepository $prices, private PriceListRepository $lists, private ProductPackagingRepository $packagings, private IdGenerator $ids, private Clock $clock, private TenantTransaction $tx, private AuthorizationService $auth, private OperationalGuard $guard) {} public function __invoke(CreateProductPrice $c): ProductPrice
    {
        $o = $c->actorContext->organizationId();
        return $this->tx->transactional($o, function () use ($c, $o): ProductPrice {
            $this->auth->authorize($c->actorContext, PermissionCode::ProductPriceCreate, ResourceScope::organization($o));
            $this->guard->assertTenant($c->actorContext);
            $list = $this->lists->get($o, $c->priceListId);
            $pack = $this->packagings->get($o, $c->packagingId);
            if (!$pack->productId()->equals($c->productId)) {
                throw new \LogicException('Packaging does not belong to product.');
            }$p = ProductPrice::createActive(ProductPriceId::generate($this->ids), $list, new ProductPriceTarget($o, $c->productId, $c->packagingId), $c->amount, $c->validFrom, $c->validTo, $c->actorContext->actorId(), $this->clock->now());
            $this->prices->save($p);
            return $p;
        });
    }
}
