<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application;

use Zandu\Modules\Inventory\Domain\Stock\StockRepository;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\SharedKernel\Access\{PermissionCode,ResourceScope};
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{OrganizationId,ProductId,StoreId};

final readonly class StockQueryService
{
    public function __construct(private StockRepository $stocks, private AuthorizationService $authorization) {}
    public function list(ActorContext $actor, StoreId $storeId): array { $this->authorization->authorize($actor, PermissionCode::InventoryRead, ResourceScope::store($actor->organizationId(), $storeId)); return array_map($this->view(...), $this->stocks->findByStore($actor->organizationId(), $storeId)); }
    public function get(ActorContext $actor, StoreId $storeId, ProductId $productId): array { $this->authorization->authorize($actor, PermissionCode::InventoryRead, ResourceScope::store($actor->organizationId(), $storeId)); return $this->view($this->stocks->get($actor->organizationId(), $storeId, $productId)); }
    private function view(\Zandu\Modules\Inventory\Domain\Stock\Stock $stock): array { return ['id'=>$stock->id()->toString(),'organizationId'=>$stock->organizationId()->toString(),'storeId'=>$stock->storeId()->toString(),'productId'=>$stock->productId()->toString(),'quantityOnHand'=>$stock->quantityOnHand()->toString(),'initialized'=>$stock->initialized(),'version'=>$stock->version()]; }
}
