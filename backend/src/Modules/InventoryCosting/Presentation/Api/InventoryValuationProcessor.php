<?php

declare(strict_types=1);

namespace Zandu\Modules\InventoryCosting\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use Zandu\Modules\InventoryCosting\Application\InitializeStockValuation\{InitializeStockValuation, InitializeStockValuationHandler};
use Zandu\Modules\InventoryCosting\Application\{StockValuationView, StockValuationViewFactory};
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Identity\{ProductId, StoreId, UuidFactory};

/** @implements ProcessorInterface<mixed, InventoryValuationResource> */
final readonly class InventoryValuationProcessor implements ProcessorInterface
{
    public function __construct(
        private CurrentActorProvider $actors,
        private UuidFactory $uuids,
        private DecimalFactory $decimals,
        private InitializeStockValuationHandler $initialize,
        private StockValuationViewFactory $views,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): InventoryValuationResource
    {
        if ('inventory_valuation_initialize' !== $operation->getName() || !$data instanceof InitializeStockValuationInput) {
            throw new InvalidArgumentException('Valuation initialization input is required.');
        }

        $storeId = $this->identifier($uriVariables['storeId'] ?? null, StoreId::class);
        $productId = $this->identifier($uriVariables['productId'] ?? null, ProductId::class);
        $valuation = ($this->initialize)(new InitializeStockValuation(
            $storeId,
            $productId,
            $this->decimals->fromString($data->openingUnitCost),
            $data->reason,
            $this->actors->resolve(),
        ));

        return $this->resource($this->views->from($valuation));
    }

    /** @param class-string<StoreId|ProductId> $type */
    private function identifier(mixed $value, string $type): StoreId|ProductId
    {
        if (!is_string($value) || '' === $value) {
            throw new InvalidArgumentException('Valuation identifiers are required.');
        }

        return StoreId::class === $type
            ? StoreId::fromString($value, $this->uuids)
            : ProductId::fromString($value, $this->uuids);
    }

    private function resource(StockValuationView $valuation): InventoryValuationResource
    {
        return new InventoryValuationResource(
            $valuation->id,
            $valuation->organizationId,
            $valuation->storeId,
            $valuation->productId,
            $valuation->stockId,
            $valuation->quantityOnHand,
            $valuation->totalValue,
            $valuation->currency,
            $valuation->averageUnitCost,
            $valuation->version,
        );
    }
}
