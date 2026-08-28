<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use Zandu\Modules\Sales\Application\{AddReturnSaleLine, AddReturnSaleLineHandler, CancelReturnSale, CancelReturnSaleHandler, CompleteReturnSale, CompleteReturnSaleService, CreateReturnSale, CreateReturnSaleHandler, ReturnSaleViewFactory};
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Identity\{ReturnSaleId, SaleId, SaleLineId, UuidFactory};
use Zandu\SharedKernel\Quantity\Quantity;

/** @implements ProcessorInterface<mixed, ReturnSaleResource> */
final readonly class ReturnSaleProcessor implements ProcessorInterface
{
    public function __construct(private CurrentActorProvider $actors, private UuidFactory $uuids, private DecimalFactory $decimals, private CreateReturnSaleHandler $create, private AddReturnSaleLineHandler $addLine, private CompleteReturnSaleService $complete, private CancelReturnSaleHandler $cancel, private ReturnSaleViewFactory $views, private ReturnSaleResourceMapper $mapper) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ReturnSaleResource
    {
        $actor = $this->actors->resolve();
        $name = $operation->getName();
        if ('return_sale_create' === $name && $data instanceof ReturnSaleCreateInput) {
            $return = ($this->create)(new CreateReturnSale(SaleId::fromString((string) ($uriVariables['saleId'] ?? throw new InvalidArgumentException('Sale identifier is required.')), $this->uuids), $data->reason, $actor));

            return $this->mapper->map($this->views->create($return));
        }
        $returnId = ReturnSaleId::fromString((string) ($uriVariables['id'] ?? throw new InvalidArgumentException('Return identifier is required.')), $this->uuids);
        if ('return_sale_line_add' === $name && $data instanceof ReturnSaleLineInput) {
            $return = ($this->addLine)(new AddReturnSaleLine($returnId, SaleLineId::fromString($data->saleLineId, $this->uuids), Quantity::fromString($data->quantity, $this->decimals), $data->restock, $data->reason, $actor));

            return $this->mapper->map($this->views->create($return));
        }
        if ('return_sale_complete' === $name) {
            return $this->mapper->map($this->views->create(($this->complete)(new CompleteReturnSale($returnId, $actor))));
        }
        if ('return_sale_cancel' === $name) {
            return $this->mapper->map($this->views->create(($this->cancel)(new CancelReturnSale($returnId, $actor))));
        }

        throw new InvalidArgumentException('Unsupported return operation.');
    }
}
