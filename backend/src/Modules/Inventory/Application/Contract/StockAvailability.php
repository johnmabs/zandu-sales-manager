<?php
declare(strict_types=1);
namespace Zandu\Modules\Inventory\Application\Contract;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Quantity\Quantity;
final readonly class StockAvailability { public function __construct(public ProductId $productId, public Quantity $quantityOnHand, public int $stockVersion, public bool $initialized) {} }
