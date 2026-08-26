<?php
declare(strict_types=1);
namespace Zandu\Modules\Inventory\Application\InitializeStock;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\Modules\Inventory\Domain\Stock\StockQuantity;
final readonly class InitializeStock { public function __construct(public StoreId $storeId, public ProductId $productId, public StockQuantity $quantity, public ActorContext $actorContext) {} }
