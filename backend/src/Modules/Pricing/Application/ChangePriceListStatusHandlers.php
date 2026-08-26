<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Application;

final class ChangePriceListStatusHandlers {}
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Pricing\Domain\PriceList\{PriceList,PriceListCode,PriceListName,PriceListPriority,PriceListRepository};
use Zandu\SharedKernel\Access\{PermissionCode,ResourceScope};
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\PriceListId;
use Zandu\SharedKernel\Money\Currency;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class CreatePriceListHandler
{
    public function __construct(private PriceListRepository $lists, private IdGenerator $ids, private Clock $clock, private TenantTransaction $tx, private AuthorizationService $auth, private OperationalGuard $guard) {} public function __invoke(CreatePriceList $c): PriceList
    {
        $o = $c->actorContext->organizationId();
        return $this->tx->transactional($o, function () use ($c, $o): PriceList {
            $this->auth->authorize($c->actorContext, PermissionCode::PriceListCreate, ResourceScope::organization($o));
            $this->guard->assertTenant($c->actorContext);
            $code = PriceListCode::fromString($c->code);
            if (null !== $this->lists->findByCode($o, $code)) {
                throw new \LogicException('Price list code already exists.');
            }$p = PriceList::createDraft(PriceListId::generate($this->ids), $o, $code, PriceListName::fromString($c->name), Currency::fromCode($c->currency), $c->validFrom, $c->validTo, PriceListPriority::fromInt($c->priority), $c->actorContext->actorId(), $this->clock->now());
            $this->lists->save($p);
            return $p;
        });
    }
}
final readonly class UpdatePriceListHandler
{
    public function __construct(private PriceListRepository $lists, private Clock $clock, private TenantTransaction $tx, private AuthorizationService $auth, private OperationalGuard $guard) {} public function __invoke(UpdatePriceList $c): PriceList
    {
        $o = $c->actorContext->organizationId();
        return $this->tx->transactional($o, function () use ($c, $o): PriceList {
            $this->auth->authorize($c->actorContext, PermissionCode::PriceListUpdate, ResourceScope::organization($o));
            $this->guard->assertTenant($c->actorContext);
            $p = $this->lists->get($o, $c->priceListId);
            $p->update(PriceListCode::fromString($c->code), PriceListName::fromString($c->name), $p->currency(), $c->validFrom, $c->validTo, PriceListPriority::fromInt($c->priority), $c->actorContext->actorId(), $this->clock->now());
            $this->lists->save($p);
            return $p;
        });
    }
}
final readonly class DeactivatePriceListHandler
{
    public function __construct(private PriceListRepository $lists, private Clock $clock, private TenantTransaction $tx, private AuthorizationService $auth, private OperationalGuard $guard) {}
    public function __invoke(DeactivatePriceList $c): PriceList
    {
        $o = $c->actorContext->organizationId();
        return $this->tx->transactional($o, function () use ($c, $o): PriceList {
            $this->auth->authorize($c->actorContext, PermissionCode::PriceListUpdate, ResourceScope::organization($o));
            $this->guard->assertTenant($c->actorContext);
            $p = $this->lists->get($o, $c->priceListId);
            $p->deactivate($c->actorContext->actorId(), $this->clock->now());
            $this->lists->save($p);
            return $p;
        });
    }
}
final readonly class ArchivePriceListHandler
{
    public function __construct(private PriceListRepository $lists, private Clock $clock, private TenantTransaction $tx, private AuthorizationService $auth, private OperationalGuard $guard) {}
    public function __invoke(ArchivePriceList $c): PriceList
    {
        $o = $c->actorContext->organizationId();
        return $this->tx->transactional($o, function () use ($c, $o): PriceList {
            $this->auth->authorize($c->actorContext, PermissionCode::PriceListArchive, ResourceScope::organization($o));
            $this->guard->assertTenant($c->actorContext);
            $p = $this->lists->get($o, $c->priceListId);
            $p->archive($c->actorContext->actorId(), $this->clock->now());
            $this->lists->save($p);
            return $p;
        });
    }
}
