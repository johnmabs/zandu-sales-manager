<?php
declare(strict_types=1);
namespace Zandu\Modules\Inventory\Application;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Inventory\Domain\StockMovement\StockMovementRepository;
use Zandu\SharedKernel\Access\{PermissionCode,ResourceScope};
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{ProductId,StoreId};
final readonly class StockMovementQueryService
{
 public function __construct(private StockMovementRepository $movements,private AuthorizationService $authorization){}
 public function list(ActorContext $actor,StoreId $storeId,?ProductId $productId=null):array{$this->authorization->authorize($actor,PermissionCode::StockMovementRead,ResourceScope::store($actor->organizationId(),$storeId));$items=null===$productId?$this->movements->findByStore($actor->organizationId(),$storeId):$this->movements->findByProduct($actor->organizationId(),$storeId,$productId);return array_map($this->view(...),$items);}
 private function view(\Zandu\Modules\Inventory\Domain\StockMovement\StockMovement $m):array{return ['id'=>$m->id()->toString(),'storeId'=>$m->storeId()->toString(),'productId'=>$m->productId()->toString(),'stockId'=>$m->stockId()->toString(),'type'=>$m->type()->value,'quantity'=>$m->quantity()->toString(),'previousQuantity'=>$m->previousQuantity()->toString(),'resultingQuantity'=>$m->resultingQuantity()->toString(),'source'=>$m->source()->type(),'reason'=>$m->reason(),'occurredAt'=>$m->occurredAt()->format(DATE_ATOM)];}
}
