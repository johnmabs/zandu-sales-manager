<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use Zandu\Modules\Purchasing\Application\CancelPurchaseReturn\CancelPurchaseReturn;
use Zandu\Modules\Purchasing\Application\CancelPurchaseReturn\CancelPurchaseReturnHandler;
use Zandu\Modules\Purchasing\Application\CreatePurchaseReturn\CreatePurchaseReturn;
use Zandu\Modules\Purchasing\Application\CreatePurchaseReturn\CreatePurchaseReturnHandler;
use Zandu\Modules\Purchasing\Application\CreatePurchaseReturn\PurchaseReturnInput;
use Zandu\Modules\Purchasing\Application\PurchaseReturnViewFactory;
use Zandu\Modules\Purchasing\Application\ShipPurchaseReturn\ShipPurchaseReturn;
use Zandu\Modules\Purchasing\Application\ShipPurchaseReturn\ShipPurchaseReturnHandler;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Identity\GoodsReceiptId;
use Zandu\SharedKernel\Identity\GoodsReceiptLineId;
use Zandu\SharedKernel\Identity\PurchaseReturnId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\UuidFactory;
use Zandu\SharedKernel\Quantity\Quantity;

/** @implements ProcessorInterface<mixed, PurchaseReturnResource> */
final readonly class PurchaseReturnProcessor implements ProcessorInterface
{
    public function __construct(
        private CurrentActorProvider $actors,
        private UuidFactory $uuids,
        private DecimalFactory $decimals,
        private CreatePurchaseReturnHandler $create,
        private ShipPurchaseReturnHandler $ship,
        private CancelPurchaseReturnHandler $cancel,
        private PurchaseReturnViewFactory $views,
        private PurchaseReturnResourceMapper $mapper,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PurchaseReturnResource
    {
        $actor = $this->actors->resolve();
        if ('purchase_return_create' === $operation->getName() && $data instanceof PurchaseReturnCreateInput) {
            $return = ($this->create)(new CreatePurchaseReturn(
                StoreId::fromString((string) ($uriVariables['storeId'] ?? throw new InvalidArgumentException('Store identifier is required.')), $this->uuids),
                GoodsReceiptId::fromString($data->goodsReceiptId, $this->uuids),
                $data->reason,
                $this->lines($data),
                $actor,
            ));

            return $this->mapper->map($this->views->create($return));
        }

        $returnId = PurchaseReturnId::fromString((string) ($uriVariables['id'] ?? throw new InvalidArgumentException('Purchase return identifier is required.')), $this->uuids);
        if ('purchase_return_ship' === $operation->getName()) {
            return $this->mapper->map($this->views->create(($this->ship)(new ShipPurchaseReturn($returnId, $actor))));
        }
        if ('purchase_return_cancel' === $operation->getName()) {
            return $this->mapper->map($this->views->create(($this->cancel)(new CancelPurchaseReturn($returnId, $actor))));
        }

        throw new InvalidArgumentException('Unsupported purchase return operation.');
    }

    /** @return list<PurchaseReturnInput> */
    private function lines(PurchaseReturnCreateInput $input): array
    {
        return array_map(function (mixed $line): PurchaseReturnInput {
            if (!isset($line['goodsReceiptLineId'], $line['baseQuantity']) || !is_string($line['goodsReceiptLineId']) || !is_string($line['baseQuantity'])) {
                throw new InvalidArgumentException('Purchase return lines require goodsReceiptLineId and baseQuantity strings.');
            }

            return new PurchaseReturnInput(
                GoodsReceiptLineId::fromString($line['goodsReceiptLineId'], $this->uuids),
                Quantity::fromString($line['baseQuantity'], $this->decimals),
            );
        }, $input->lines);
    }
}
