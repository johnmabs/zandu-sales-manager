<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\RequestStack;
use Zandu\Modules\Purchasing\Application\CreateDirectGoodsReceipt\{CreateDirectGoodsReceipt, CreateDirectGoodsReceiptHandler, DirectGoodsReceiptLine};
use Zandu\Modules\Purchasing\Application\CreateLinkedGoodsReceipt\{CreateLinkedGoodsReceipt, CreateLinkedGoodsReceiptHandler, LinkedGoodsReceiptLine};
use Zandu\Modules\Purchasing\Application\GoodsReceiptDraft\{AddGoodsReceiptLine, CancelGoodsReceipt, GoodsReceiptDraftHandler, RemoveGoodsReceiptLine, UpdateGoodsReceiptLine};
use Zandu\Modules\Purchasing\Application\GoodsReceiptViewFactory;
use Zandu\Modules\Purchasing\Application\PostGoodsReceipt\{PostGoodsReceipt, PostGoodsReceiptHandler};
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Idempotency\IdempotencyKey;
use Zandu\SharedKernel\Identity\{GoodsReceiptId, GoodsReceiptLineId, ProductId, ProductPackagingId, PurchaseOrderId, PurchaseOrderLineId, StoreId, SupplierId, UuidFactory};
use Zandu\SharedKernel\Money\{Currency, Money};
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\SharedKernel\Versioning\ExpectedVersion;

/** @implements ProcessorInterface<mixed, GoodsReceiptResource> */
final readonly class GoodsReceiptProcessor implements ProcessorInterface
{
    public function __construct(private CurrentActorProvider $actors, private UuidFactory $uuids, private DecimalFactory $decimals, private RequestStack $requests, private CreateDirectGoodsReceiptHandler $createDirect, private CreateLinkedGoodsReceiptHandler $createLinked, private GoodsReceiptDraftHandler $draft, private PostGoodsReceiptHandler $post, private GoodsReceiptViewFactory $views, private GoodsReceiptResourceMapper $mapper) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): GoodsReceiptResource
    {
        $actor = $this->actors->resolve();
        $name = $operation->getName();
        if ('goods_receipt_create' === $name && $data instanceof GoodsReceiptCreateInput) {
            $storeId = StoreId::fromString($this->variable($uriVariables, 'storeId'), $this->uuids);
            if (null === $data->purchaseOrderId) {
                $receipt = ($this->createDirect)(new CreateDirectGoodsReceipt($storeId, SupplierId::fromString($this->requiredSupplierId($data), $this->uuids), $data->number, $data->supplierDeliveryNote, $data->notes, $this->directLines($data->lines), $actor));
            } else {
                $receipt = ($this->createLinked)(new CreateLinkedGoodsReceipt(PurchaseOrderId::fromString($data->purchaseOrderId, $this->uuids), $data->number, $data->supplierDeliveryNote, $data->notes, $this->linkedLines($data->lines), $actor, $storeId, null === $data->supplierId ? null : SupplierId::fromString($data->supplierId, $this->uuids)));
            }
            return $this->mapper->map($this->views->create($receipt));
        }

        $receiptId = GoodsReceiptId::fromString($this->variable($uriVariables, 'id'), $this->uuids);
        if ('goods_receipt_line_add' === $name && $data instanceof GoodsReceiptLineInput) {
            $receipt = $this->draft->add(new AddGoodsReceiptLine($receiptId, $this->product($data->productId), $this->packaging($data->productPackagingId), $this->orderLine($data->purchaseOrderLineId), $this->quantity($data->enteredReceivedQuantity), $this->money($data->unitCost, $data->currency), $actor));
            return $this->mapper->map($this->views->create($receipt));
        }
        if ('goods_receipt_line_update' === $name && $data instanceof GoodsReceiptLineUpdateInput) {
            $receipt = $this->draft->update(new UpdateGoodsReceiptLine($receiptId, GoodsReceiptLineId::fromString($this->variable($uriVariables, 'lineId'), $this->uuids), $this->product($data->productId), $this->packaging($data->productPackagingId), $this->orderLine($data->purchaseOrderLineId), $this->quantity($data->enteredReceivedQuantity), $this->money($data->unitCost, $data->currency), ExpectedVersion::fromInt($data->expectedVersion), $actor));
            return $this->mapper->map($this->views->create($receipt));
        }
        $receipt = match ($name) {
            'goods_receipt_line_remove' => $this->draft->remove(new RemoveGoodsReceiptLine($receiptId, GoodsReceiptLineId::fromString($this->variable($uriVariables, 'lineId'), $this->uuids), $actor)),
            'goods_receipt_cancel' => $this->draft->cancel(new CancelGoodsReceipt($receiptId, $actor)),
            'goods_receipt_post' => ($this->post)(new PostGoodsReceipt($receiptId, $actor, $this->idempotencyKey(), $data instanceof GoodsReceiptPostInput ? $data->overReceiptReason : null)),
            default => throw new InvalidArgumentException('Unsupported goods receipt operation or payload.'),
        };
        return $this->mapper->map($this->views->create($receipt));
    }

    /**
     * @param list<array<string, string|null>> $lines
     * @return list<DirectGoodsReceiptLine>
     */
    private function directLines(array $lines): array
    {
        return array_map(fn(array $line): DirectGoodsReceiptLine => new DirectGoodsReceiptLine(ProductId::fromString($this->field($line, 'productId'), $this->uuids), $this->packaging($line['productPackagingId'] ?? null), $this->quantity($this->field($line, 'enteredReceivedQuantity')), $this->money($this->field($line, 'unitCost'), $this->field($line, 'currency'))), $lines);
    }

    /**
     * @param list<array<string, string|null>> $lines
     * @return list<LinkedGoodsReceiptLine>
     */
    private function linkedLines(array $lines): array
    {
        return array_map(fn(array $line): LinkedGoodsReceiptLine => new LinkedGoodsReceiptLine(PurchaseOrderLineId::fromString($this->field($line, 'purchaseOrderLineId'), $this->uuids), $this->quantity($this->field($line, 'enteredReceivedQuantity')), $this->money($this->field($line, 'unitCost'), $this->field($line, 'currency'))), $lines);
    }

    /** @param array<string, mixed> $values */
    private function variable(array $values, string $key): string
    {
        $value = $values[$key] ?? null;
        return is_string($value) ? $value : throw new InvalidArgumentException(sprintf('%s identifier is required.', $key));
    }
    /** @param array<string, string|null> $values */
    private function field(array $values, string $key): string
    {
        $value = $values[$key] ?? null;
        return is_string($value) ? $value : throw new InvalidArgumentException(sprintf('%s is required.', $key));
    }
    private function product(?string $id): ?ProductId
    {
        return null === $id ? null : ProductId::fromString($id, $this->uuids);
    }
    private function packaging(?string $id): ?ProductPackagingId
    {
        return null === $id ? null : ProductPackagingId::fromString($id, $this->uuids);
    }
    private function orderLine(?string $id): ?PurchaseOrderLineId
    {
        return null === $id ? null : PurchaseOrderLineId::fromString($id, $this->uuids);
    }
    private function quantity(string $value): Quantity
    {
        return Quantity::fromString($value, $this->decimals);
    }
    private function money(string $amount, string $currency): Money
    {
        return Money::fromString($amount, Currency::fromCode($currency), $this->decimals);
    }
    private function idempotencyKey(): string
    {
        $value = $this->requests->getCurrentRequest()?->headers->get('Idempotency-Key');
        return null === $value ? throw new InvalidArgumentException('Idempotency-Key header is required.') : IdempotencyKey::fromString($value)->toString();
    }

    private function requiredSupplierId(GoodsReceiptCreateInput $input): string
    {
        return null === $input->supplierId ? throw new InvalidArgumentException('supplierId is required for a direct goods receipt.') : $input->supplierId;
    }
}
