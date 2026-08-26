<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Application;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Pricing\Domain\ProductPrice\{ProductPrice,ProductPriceRepository};
use Zandu\SharedKernel\Access\{PermissionCode,ResourceScope};
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\ProductPriceId;
use Zandu\SharedKernel\Identity\UuidFactory;

final readonly class ProductPriceQueryService
{
    public function __construct(private ProductPriceRepository $prices, private AuthorizationService $auth, private UuidFactory $uuids) {} /** @return list<ProductPriceView> */ public function list(ActorContext $a): array
    {
        $this->auth->authorize($a, PermissionCode::ProductPriceRead, ResourceScope::organization($a->organizationId()));
        return array_map($this->view(...), $this->prices->findAll($a->organizationId()));
    } public function get(string $id, ActorContext $a): ProductPriceView
    {
        $this->auth->authorize($a, PermissionCode::ProductPriceRead, ResourceScope::organization($a->organizationId()));
        return $this->view($this->prices->get($a->organizationId(), ProductPriceId::fromString($id, $this->uuids)));
    } private function view(ProductPrice $p): ProductPriceView
    {
        return new ProductPriceView($p->id()->toString(), $p->organizationId()->toString(), $p->priceListId()->toString(), $p->productId()->toString(), $p->packagingId()->toString(), $p->amount()->amount()->toString(), $p->amount()->currency()->code(), $p->status()->value, $p->validFrom()?->format(DATE_ATOM), $p->validTo()?->format(DATE_ATOM), $p->createdAt()->format(DATE_ATOM), $p->version());
    }
}
