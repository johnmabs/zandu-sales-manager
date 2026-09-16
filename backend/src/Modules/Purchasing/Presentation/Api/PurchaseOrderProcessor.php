<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use Zandu\Modules\Purchasing\Application\AddPurchaseOrderLine\{AddPurchaseOrderLine, AddPurchaseOrderLineHandler};
use Zandu\Modules\Purchasing\Application\CancelPurchaseOrder\{CancelPurchaseOrder, CancelPurchaseOrderHandler};
use Zandu\Modules\Purchasing\Application\ClosePurchaseOrder\{ClosePurchaseOrder, ClosePurchaseOrderHandler};
use Zandu\Modules\Purchasing\Application\ConfirmPurchaseOrder\{ConfirmPurchaseOrder, ConfirmPurchaseOrderHandler};
use Zandu\Modules\Purchasing\Application\CreatePurchaseOrder\{CreatePurchaseOrder, CreatePurchaseOrderHandler};
use Zandu\Modules\Purchasing\Application\PurchaseOrderViewFactory;
use Zandu\Modules\Purchasing\Application\RemovePurchaseOrderLine\{RemovePurchaseOrderLine, RemovePurchaseOrderLineHandler};
use Zandu\Modules\Purchasing\Application\UpdatePurchaseOrderLine\{UpdatePurchaseOrderLine, UpdatePurchaseOrderLineHandler};
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Identity\{ProductId, ProductPackagingId, PurchaseOrderId, PurchaseOrderLineId, StoreId, SupplierId, UuidFactory};
use Zandu\SharedKernel\Money\{Currency, Money};
use Zandu\SharedKernel\Quantity\Quantity;

/** @implements ProcessorInterface<mixed, PurchaseOrderResource> */
final readonly class PurchaseOrderProcessor implements ProcessorInterface
{
    public function __construct(private CurrentActorProvider $actors, private UuidFactory $uuids, private DecimalFactory $decimals, private PurchaseOrderViewFactory $views, private PurchaseOrderResourceMapper $mapper, private CreatePurchaseOrderHandler $create, private AddPurchaseOrderLineHandler $addLine, private UpdatePurchaseOrderLineHandler $updateLine, private RemovePurchaseOrderLineHandler $removeLine, private ConfirmPurchaseOrderHandler $confirm, private CancelPurchaseOrderHandler $cancel, private ClosePurchaseOrderHandler $close) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PurchaseOrderResource
    {
        $actor = $this->actors->resolve();
        $name = $operation->getName();
        if ('purchase_order_create' === $name && $data instanceof PurchaseOrderCreateInput) {
            $storeId = StoreId::fromString($this->variable($uriVariables, 'storeId'), $this->uuids);
            return $this->mapper->map($this->views->create(($this->create)(new CreatePurchaseOrder($storeId, SupplierId::fromString($data->supplierId, $this->uuids), $data->number, $data->currency, $actor))));
        }
        $orderId = PurchaseOrderId::fromString($this->variable($uriVariables, 'id'), $this->uuids);
        if ('purchase_order_line_add' === $name && $data instanceof PurchaseOrderLineInput) {
            return $this->mapper->map($this->views->create(($this->addLine)(new AddPurchaseOrderLine($orderId, ProductId::fromString($data->productId, $this->uuids), $this->packaging($data->productPackagingId), $this->quantity($data->enteredQuantity), $this->money($data), $actor))));
        }
        if ('purchase_order_line_update' === $name && $data instanceof PurchaseOrderLineInput) {
            return $this->mapper->map($this->views->create(($this->updateLine)(new UpdatePurchaseOrderLine($orderId, PurchaseOrderLineId::fromString($this->variable($uriVariables, 'lineId'), $this->uuids), ProductId::fromString($data->productId, $this->uuids), $this->packaging($data->productPackagingId), $this->quantity($data->enteredQuantity), $this->money($data), $actor))));
        }
        $order = match ($name) {
            'purchase_order_line_remove' => ($this->removeLine)(new RemovePurchaseOrderLine($orderId, PurchaseOrderLineId::fromString($this->variable($uriVariables, 'lineId'), $this->uuids), $actor)),
            'purchase_order_confirm' => ($this->confirm)(new ConfirmPurchaseOrder($orderId, $actor)),
            'purchase_order_cancel' => ($this->cancel)(new CancelPurchaseOrder($orderId, $actor)),
            'purchase_order_close' => ($this->close)(new ClosePurchaseOrder($orderId, $data instanceof PurchaseOrderCloseInput ? $data->reason : null, $actor)),
            default => throw new InvalidArgumentException('Unsupported purchase order operation or payload.'),
        };

        return $this->mapper->map($this->views->create($order));
    }

    /** @param array<string, mixed> $variables */
    private function variable(array $variables, string $key): string
    {
        $value = $variables[$key] ?? null;
        if (!is_string($value)) {
            throw new InvalidArgumentException(sprintf('%s identifier is required.', $key));
        }

        return $value;
    }

    private function packaging(?string $id): ?ProductPackagingId
    {
        return null === $id ? null : ProductPackagingId::fromString($id, $this->uuids);
    }
    private function quantity(string $value): Quantity
    {
        return Quantity::fromString($value, $this->decimals);
    }
    private function money(PurchaseOrderLineInput $input): Money
    {
        return Money::fromString($input->unitCost, Currency::fromCode($input->currency), $this->decimals);
    }
}
