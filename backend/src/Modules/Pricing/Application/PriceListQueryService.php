<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Application;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListRepository;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\PriceListId;
use Zandu\SharedKernel\Identity\UuidFactory;

final readonly class PriceListQueryService
{
    public function __construct(private PriceListRepository $lists, private AuthorizationService $authorization, private UuidFactory $uuids) {} /** @return list<PriceListView> */ public function list(ActorContext $actor): array
    {
        $this->authorization->authorize($actor, PermissionCode::PriceListRead, ResourceScope::organization($actor->organizationId()));
        return array_map($this->view(...), $this->lists->findAll($actor->organizationId()));
    } public function get(string $id, ActorContext $actor): PriceListView
    {
        $this->authorization->authorize($actor, PermissionCode::PriceListRead, ResourceScope::organization($actor->organizationId()));
        return $this->view($this->lists->get($actor->organizationId(), PriceListId::fromString($id, $this->uuids)));
    } private function view(\Zandu\Modules\Pricing\Domain\PriceList\PriceList $p): PriceListView
    {
        return new PriceListView($p->id()->toString(), $p->organizationId()->toString(), $p->code()->value(), $p->name()->value(), $p->currency()->code(), $p->status()->value, $p->scope()->value, $p->validFrom()?->format(DATE_ATOM), $p->validTo()?->format(DATE_ATOM), $p->priority()->value(), $p->createdAt()->format(DATE_ATOM), $p->version());
    }
}
