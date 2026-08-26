<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use Zandu\Modules\Inventory\Application\AdjustStock\{AdjustStock,AdjustStockHandler};
use Zandu\Modules\Inventory\Application\InitializeStock\{InitializeStock,InitializeStockHandler};
use Zandu\Modules\Inventory\Application\StockQueryService;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Identity\{ProductId,StoreId,UuidFactory};
use Zandu\SharedKernel\Quantity\Quantity;

/** @implements ProcessorInterface<mixed, StockResource> */
final readonly class StockProcessor implements ProcessorInterface
{
    public function __construct(private CurrentActorProvider $actors, private UuidFactory $uuids, private DecimalFactory $decimals, private InitializeStockHandler $initialize, private AdjustStockHandler $adjust, private StockQueryService $queries) {}
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): StockResource
    {
        $actor = $this->actors->resolve();
        $store = $this->id($uriVariables['storeId'] ?? null, StoreId::class);
        $product = $this->id($uriVariables['productId'] ?? null, ProductId::class);
        if ('stock_initialize' === $operation->getName()) {
            if (!$data instanceof InitializeStockInput) {
                throw new InvalidArgumentException('Stock initialization input is required.');
            } $stock = ($this->initialize)(new InitializeStock($store, $product, Quantity::fromString($data->quantity, $this->decimals), $actor));
        } elseif ('stock_adjust' === $operation->getName()) {
            if (!$data instanceof AdjustStockInput) {
                throw new InvalidArgumentException('Stock adjustment input is required.');
            } ($this->adjust)(new AdjustStock($store, $product, Quantity::fromString($data->delta, $this->decimals), $data->reason, $actor));
            $view = $this->queries->get($actor, $store, $product);
            return new StockResource($view['id'], $view['organizationId'], $view['storeId'], $view['productId'], $view['quantityOnHand'], $view['initialized'], $view['version']);
        } else {
            throw new InvalidArgumentException('Unsupported stock operation.');
        }
        return new StockResource($stock->id()->toString(), $stock->organizationId()->toString(), $stock->storeId()->toString(), $stock->productId()->toString(), $stock->quantityOnHand()->toString(), $stock->initialized(), $stock->version());
    }
    private function id(mixed $value, string $type): StoreId|ProductId
    {
        if (!is_string($value) || '' === $value) {
            throw new InvalidArgumentException('Stock identifiers are required.');
        } return $type === StoreId::class ? StoreId::fromString($value, $this->uuids) : ProductId::fromString($value, $this->uuids);
    }
}
