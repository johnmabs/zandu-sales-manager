<?php
declare(strict_types=1);
namespace Zandu\Modules\Inventory\Presentation\Api;
use ApiPlatform\Metadata\{ApiResource,GetCollection};
#[ApiResource(operations:[new GetCollection(name:'stock_movement_list',uriTemplate:'/stores/{storeId}/stock-movements',provider:StockMovementProvider::class),new GetCollection(name:'stock_product_movement_list',uriTemplate:'/stores/{storeId}/stocks/{productId}/movements',provider:StockMovementProvider::class)])]
final readonly class StockMovementResource{public function __construct(public string $id,public string $storeId,public string $productId,public string $stockId,public string $type,public string $quantity,public string $previousQuantity,public string $resultingQuantity,public string $source,public ?string $reason,public string $occurredAt){} }
