<?php
declare(strict_types=1);
namespace Zandu\Modules\Inventory\Application\Contract;
use Zandu\SharedKernel\Identity\{OrganizationId,ProductId,StoreId};
interface StockAvailabilityProvider { public function provide(OrganizationId $organizationId, StoreId $storeId, ProductId $productId): StockAvailability; }
