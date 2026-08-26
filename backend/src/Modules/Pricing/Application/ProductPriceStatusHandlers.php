<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Application;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Pricing\Domain\ProductPrice\{ProductPrice,ProductPriceRepository};
use Zandu\SharedKernel\Access\{PermissionCode,ResourceScope};
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final class ProductPriceStatusHandlers {}
final readonly class ActivateProductPriceHandler
{
    public function __construct(private ProductPriceRepository $prices, private Clock $clock, private TenantTransaction $tx, private AuthorizationService $auth, private OperationalGuard $guard) {} public function __invoke(ActivateProductPrice $c): ProductPrice
    {
        $o = $c->actor->organizationId();
        return $this->tx->transactional($o, function () use ($c, $o): ProductPrice {
            $this->auth->authorize($c->actor, PermissionCode::ProductPriceUpdate, ResourceScope::organization($o));
            $this->guard->assertTenant($c->actor);
            $p = $this->prices->get($o, $c->id);
            $p->activate($c->actor->actorId(), $this->clock->now());
            $this->prices->save($p);
            return $p;
        });
    }
}
final readonly class DeactivateProductPriceHandler
{
    public function __construct(private ProductPriceRepository $prices, private Clock $clock, private TenantTransaction $tx, private AuthorizationService $auth, private OperationalGuard $guard) {} public function __invoke(DeactivateProductPrice $c): ProductPrice
    {
        $o = $c->actor->organizationId();
        return $this->tx->transactional($o, function () use ($c, $o): ProductPrice {
            $this->auth->authorize($c->actor, PermissionCode::ProductPriceUpdate, ResourceScope::organization($o));
            $this->guard->assertTenant($c->actor);
            $p = $this->prices->get($o, $c->id);
            $p->deactivate($c->actor->actorId(), $this->clock->now());
            $this->prices->save($p);
            return $p;
        });
    }
}
final readonly class ArchiveProductPriceHandler
{
    public function __construct(private ProductPriceRepository $prices, private Clock $clock, private TenantTransaction $tx, private AuthorizationService $auth, private OperationalGuard $guard) {} public function __invoke(ArchiveProductPrice $c): ProductPrice
    {
        $o = $c->actor->organizationId();
        return $this->tx->transactional($o, function () use ($c, $o): ProductPrice {
            $this->auth->authorize($c->actor, PermissionCode::ProductPriceArchive, ResourceScope::organization($o));
            $this->guard->assertTenant($c->actor);
            $p = $this->prices->get($o, $c->id);
            $p->archive($c->actor->actorId(), $this->clock->now());
            $this->prices->save($p);
            return $p;
        });
    }
}
