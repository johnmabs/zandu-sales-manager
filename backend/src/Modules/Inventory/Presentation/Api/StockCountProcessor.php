<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use Zandu\Modules\Inventory\Application\CancelStockCount\{CancelStockCount, CancelStockCountHandler};
use Zandu\Modules\Inventory\Application\CreateStockCount\{CreateStockCount, CreateStockCountHandler};
use Zandu\Modules\Inventory\Application\FinalizeStockCount\{FinalizeStockCount, FinalizeStockCountHandler};
use Zandu\Modules\Inventory\Application\ReconcileStockCount\StockCountCostAssignment;
use Zandu\Modules\Inventory\Application\RecordStockCount\{RecordStockCount, RecordStockCountBatch, RecordStockCountHandler, StockCountEntry};
use Zandu\Modules\Inventory\Application\StartStockCount\{StartStockCount, StartStockCountHandler};
use Zandu\Modules\Inventory\Application\StockCountQueryService;
use Zandu\SharedKernel\Context\{ActorContext, CurrentActorProvider};
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Identity\{ProductId, StockCountId, StoreId, UuidFactory};
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\SharedKernel\Tenancy\TenantTransaction;

/** @implements ProcessorInterface<mixed, StockCountResource> */
final readonly class StockCountProcessor implements ProcessorInterface
{
    public function __construct(
        private CurrentActorProvider $actors,
        private UuidFactory $uuids,
        private DecimalFactory $decimals,
        private TenantTransaction $transaction,
        private CreateStockCountHandler $create,
        private StartStockCountHandler $start,
        private RecordStockCountHandler $record,
        private FinalizeStockCountHandler $finalize,
        private CancelStockCountHandler $cancel,
        private StockCountQueryService $stockCounts,
        private StockCountResourceMapper $mapper,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): StockCountResource
    {
        $actor = $this->actors->resolve();
        $name = $operation->getName();
        if ('stock_count_create' === $name && $data instanceof StockCountCreateInput) {
            $stockCount = ($this->create)(CreateStockCount::fromStrings(
                $this->storeId($uriVariables['storeId'] ?? null),
                $data->scopeType,
                $this->productIds($data->productIds),
                $actor,
                $data->mode,
            ));

            return $this->view($actor, $stockCount->id());
        }

        $stockCountId = $this->stockCountId($uriVariables['id'] ?? null);
        if ('stock_count_start' === $name) {
            ($this->start)(new StartStockCount($stockCountId, $actor));
        } elseif ('stock_count_record' === $name && $data instanceof StockCountRecordInput) {
            ($this->record)(new RecordStockCount(
                $stockCountId,
                ProductId::fromString($data->productId, $this->uuids),
                $this->quantity($data->countedQuantity),
                $data->expectedLineVersion,
                $actor,
            ));
        } elseif ('stock_count_record_batch' === $name && $data instanceof StockCountRecordBatchInput) {
            $this->record->batch(new RecordStockCountBatch($stockCountId, $this->entries($data->entries), $actor));
        } elseif ('stock_count_finalize' === $name && $data instanceof StockCountFinalizationInput) {
            ($this->finalize)(new FinalizeStockCount($stockCountId, $actor, $data->batchSize, $this->costAssignments($data->costAssignments)));
        } elseif ('stock_count_cancel' === $name) {
            ($this->cancel)(new CancelStockCount($stockCountId, $actor));
        } else {
            throw new InvalidArgumentException('Unsupported stock count operation or payload.');
        }

        return $this->view($actor, $stockCountId);
    }

    private function view(ActorContext $actor, StockCountId $stockCountId): StockCountResource
    {
        return $this->transaction->transactional(
            $actor->organizationId(),
            fn(): StockCountResource => $this->mapper->map($this->stockCounts->get($actor, $stockCountId)),
        );
    }

    /** @param list<string> $values
     * @return list<ProductId>
     */
    private function productIds(array $values): array
    {
        return array_map(fn(string $value): ProductId => ProductId::fromString($value, $this->uuids), $values);
    }

    /**
     * @param list<array<string, mixed>> $values
     * @return list<StockCountEntry>
     */
    private function entries(array $values): array
    {
        $entries = [];
        foreach ($values as $entry) {
            if (!isset($entry['productId'], $entry['countedQuantity'], $entry['expectedLineVersion'])
                || !is_string($entry['productId'])
                || !is_string($entry['countedQuantity'])
                || !is_int($entry['expectedLineVersion'])) {
                throw new InvalidArgumentException('Each stock count entry requires productId, countedQuantity and expectedLineVersion.');
            }
            $entries[] = new StockCountEntry(
                ProductId::fromString($entry['productId'], $this->uuids),
                $this->quantity($entry['countedQuantity']),
                $entry['expectedLineVersion'],
            );
        }

        return $entries;
    }

    /**
     * @param list<array<string, mixed>> $values
     * @return list<StockCountCostAssignment>
     */
    private function costAssignments(array $values): array
    {
        $assignments = [];
        foreach ($values as $assignment) {
            if (!isset($assignment['productId'], $assignment['manualUnitCost'], $assignment['reason'])
                || !is_string($assignment['productId'])
                || !is_string($assignment['manualUnitCost'])
                || !is_string($assignment['reason'])) {
                throw new InvalidArgumentException('Each stock count cost assignment requires productId, manualUnitCost and reason.');
            }
            $assignments[] = new StockCountCostAssignment(
                ProductId::fromString($assignment['productId'], $this->uuids),
                $this->decimals->fromString($assignment['manualUnitCost']),
                $assignment['reason'],
            );
        }

        return $assignments;
    }

    private function stockCountId(mixed $value): StockCountId
    {
        if (!is_string($value) || '' === trim($value)) {
            throw new InvalidArgumentException('Stock count identifier is required.');
        }

        return StockCountId::fromString($value, $this->uuids);
    }

    private function storeId(mixed $value): StoreId
    {
        if (!is_string($value) || '' === trim($value)) {
            throw new InvalidArgumentException('Store identifier is required.');
        }

        return StoreId::fromString($value, $this->uuids);
    }

    private function quantity(string $value): Quantity
    {
        return Quantity::fromString($value, $this->decimals);
    }
}
