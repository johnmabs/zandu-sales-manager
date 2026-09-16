<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\RequestStack;
use Zandu\Modules\Purchasing\Application\CreateGoodsReceiptCorrection\{CreateGoodsReceiptCorrection, CreateGoodsReceiptCorrectionHandler, GoodsReceiptCorrectionInput};
use Zandu\Modules\Purchasing\Application\GoodsReceiptCorrectionViewFactory;
use Zandu\Modules\Purchasing\Application\PostGoodsReceiptCorrection\{PostGoodsReceiptCorrection, PostGoodsReceiptCorrectionHandler};
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Idempotency\IdempotencyKey;
use Zandu\SharedKernel\Identity\{GoodsReceiptCorrectionId, GoodsReceiptId, ProductId, UuidFactory};
use Zandu\SharedKernel\Quantity\Quantity;

/** @implements ProcessorInterface<mixed, GoodsReceiptCorrectionResource> */
final readonly class GoodsReceiptCorrectionProcessor implements ProcessorInterface
{
    public function __construct(private CurrentActorProvider $actors, private UuidFactory $uuids, private DecimalFactory $decimals, private RequestStack $requests, private CreateGoodsReceiptCorrectionHandler $create, private PostGoodsReceiptCorrectionHandler $post, private GoodsReceiptCorrectionViewFactory $views, private GoodsReceiptCorrectionResourceMapper $mapper) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): GoodsReceiptCorrectionResource
    {
        $actor = $this->actors->resolve();
        $id = $uriVariables['id'] ?? null;
        if (!is_string($id)) {
            throw new InvalidArgumentException('Resource identifier is required.');
        }
        if ('goods_receipt_correction_create' === $operation->getName() && $data instanceof GoodsReceiptCorrectionCreateInput) {
            $correction = ($this->create)(new CreateGoodsReceiptCorrection(GoodsReceiptId::fromString($id, $this->uuids), $data->reason, array_map(fn(array $line): GoodsReceiptCorrectionInput => new GoodsReceiptCorrectionInput(ProductId::fromString($line['productId'], $this->uuids), Quantity::fromString($line['correctedReceivedQuantity'], $this->decimals)), $data->lines), $actor));
            return $this->mapper->map($this->views->create($correction));
        }
        if ('goods_receipt_correction_post' === $operation->getName()) {
            return $this->mapper->map($this->views->create(($this->post)(new PostGoodsReceiptCorrection(GoodsReceiptCorrectionId::fromString($id, $this->uuids), $actor, $this->idempotencyKey()))));
        }
        throw new InvalidArgumentException('Unsupported goods receipt correction operation or payload.');
    }

    private function idempotencyKey(): string
    {
        $value = $this->requests->getCurrentRequest()?->headers->get('Idempotency-Key');
        return null === $value ? throw new InvalidArgumentException('Idempotency-Key header is required.') : IdempotencyKey::fromString($value)->toString();
    }
}
