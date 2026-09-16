<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\RequestStack;
use Zandu\Modules\Inventory\Application\CreateStockTransfer\{CreateStockTransfer, CreateStockTransferHandler};
use Zandu\Modules\Inventory\Application\ReceiveStockTransfer\{ReceiveStockTransfer, ReceiveStockTransferHandler};
use Zandu\Modules\Inventory\Application\ShipStockTransfer\{ShipStockTransfer, ShipStockTransferHandler};
use Zandu\Modules\Inventory\Application\StockTransferDraft\{AddStockTransferLine, CancelStockTransfer, RemoveStockTransferLine, StockTransferDraftHandler, UpdateStockTransferLine};
use Zandu\Modules\Inventory\Application\StockTransferViewFactory;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Idempotency\IdempotencyKey;
use Zandu\SharedKernel\Identity\{ProductId, StockTransferId, StockTransferLineId, StoreId, UuidFactory};
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\SharedKernel\Versioning\ExpectedVersion;

/** @implements ProcessorInterface<mixed, StockTransferResource> */
final readonly class StockTransferProcessor implements ProcessorInterface
{
    public function __construct(
        private CurrentActorProvider $actors,
        private UuidFactory $uuids,
        private DecimalFactory $decimals,
        private RequestStack $requests,
        private CreateStockTransferHandler $create,
        private StockTransferDraftHandler $draft,
        private ShipStockTransferHandler $ship,
        private ReceiveStockTransferHandler $receive,
        private StockTransferViewFactory $views,
        private StockTransferResourceMapper $mapper,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): StockTransferResource
    {
        $actor = $this->actors->resolve();
        $name = $operation->getName();
        if ('stock_transfer_create' === $name && $data instanceof StockTransferCreateInput) {
            return $this->mapper->map($this->views->create(($this->create)(new CreateStockTransfer(
                StoreId::fromString($data->sourceStoreId, $this->uuids),
                StoreId::fromString($data->destinationStoreId, $this->uuids),
                $actor,
            ))));
        }

        $transferId = $this->transferId($uriVariables['id'] ?? null);
        if ('stock_transfer_line_add' === $name && $data instanceof StockTransferLineInput) {
            return $this->mapper->map($this->views->create($this->draft->add(new AddStockTransferLine($transferId, ProductId::fromString($data->productId, $this->uuids), $this->quantity($data->requestedQuantity), $actor))));
        }
        if ('stock_transfer_line_update' === $name && $data instanceof StockTransferLineUpdateInput) {
            return $this->mapper->map($this->views->create($this->draft->update(new UpdateStockTransferLine($transferId, $this->lineId($uriVariables['lineId'] ?? null), $this->quantity($data->requestedQuantity), ExpectedVersion::fromInt($data->expectedVersion), $actor))));
        }
        if ('stock_transfer_line_remove' === $name) {
            return $this->mapper->map($this->views->create($this->draft->remove(new RemoveStockTransferLine($transferId, $this->lineId($uriVariables['lineId'] ?? null), $actor))));
        }
        if ('stock_transfer_cancel' === $name && $data instanceof StockTransferCancelInput) {
            return $this->mapper->map($this->views->create($this->draft->cancel(new CancelStockTransfer($transferId, $data->reason, $actor))));
        }
        if ('stock_transfer_ship' === $name && $data instanceof StockTransferShipInput) {
            return $this->mapper->map($this->views->create(($this->ship)(new ShipStockTransfer($transferId, $this->quantities($data->lines, 'shippedQuantity'), $actor, $this->idempotencyKey()))));
        }
        if ('stock_transfer_receive' === $name && $data instanceof StockTransferReceiveInput) {
            return $this->mapper->map($this->views->create(($this->receive)(new ReceiveStockTransfer($transferId, $this->quantities($data->lines, 'receivedQuantity'), $actor, $this->idempotencyKey()))));
        }

        throw new InvalidArgumentException('Unsupported stock transfer operation or payload.');
    }

    /**
     * @param list<array<string, string>> $lines
     * @return array<string, Quantity>
     */
    private function quantities(array $lines, string $quantityField): array
    {
        $quantities = [];
        foreach ($lines as $line) {
            $lineId = $line['lineId'] ?? null;
            $quantity = $line[$quantityField] ?? null;
            if (!is_string($lineId) || '' === trim($lineId) || !is_string($quantity)) {
                throw new InvalidArgumentException(sprintf('Each transfer line requires lineId and %s strings.', $quantityField));
            }
            $parsedId = StockTransferLineId::fromString($lineId, $this->uuids)->toString();
            if (isset($quantities[$parsedId])) {
                throw new InvalidArgumentException('A stock transfer line can occur only once in the payload.');
            }
            $quantities[$parsedId] = $this->quantity($quantity);
        }

        return $quantities;
    }

    private function transferId(mixed $value): StockTransferId
    {
        if (!is_string($value) || '' === trim($value)) {
            throw new InvalidArgumentException('Stock transfer identifier is required.');
        }
        return StockTransferId::fromString($value, $this->uuids);
    }

    private function lineId(mixed $value): StockTransferLineId
    {
        if (!is_string($value) || '' === trim($value)) {
            throw new InvalidArgumentException('Stock transfer line identifier is required.');
        }
        return StockTransferLineId::fromString($value, $this->uuids);
    }

    private function quantity(string $value): Quantity
    {
        return Quantity::fromString($value, $this->decimals);
    }

    private function idempotencyKey(): string
    {
        $value = $this->requests->getCurrentRequest()?->headers->get('Idempotency-Key');
        if (null === $value) {
            throw new InvalidArgumentException('Idempotency-Key header is required.');
        }

        return IdempotencyKey::fromString($value)->toString();
    }
}
