<?php
declare(strict_types=1);
namespace Zandu\Modules\Inventory\Application\AdjustStock;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{ProductId,StoreId};
use Zandu\SharedKernel\Quantity\Quantity;
final readonly class AdjustStock { public function __construct(public StoreId $storeId, public ProductId $productId, public Quantity $delta, public string $reason, public ActorContext $actorContext) {} }
