<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\RequestStack;
use Zandu\Modules\Sales\Application\{AddSaleLine,AddSaleLineHandler,CancelSale,CancelSaleHandler,CompleteSale,CompleteSaleService,CreateSale,CreateSaleHandler,RemoveSaleLine,RemoveSaleLineHandler,SaleViewFactory,UpdateSaleLine,UpdateSaleLineHandler};
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Idempotency\IdempotencyKey;
use Zandu\SharedKernel\Identity\{CashSessionId,ProductId,ProductPackagingId,SaleId,SaleLineId,StoreId,UuidFactory};
use Zandu\SharedKernel\Money\{Currency,Money};
use Zandu\SharedKernel\Quantity\Quantity;

/** @implements ProcessorInterface<mixed,SaleResource|CompleteSaleResultResource> */
final readonly class SaleProcessor implements ProcessorInterface
{
    public function __construct(private CurrentActorProvider $actors, private UuidFactory $uuids, private DecimalFactory $decimals, private RequestStack $requests, private CreateSaleHandler $create, private AddSaleLineHandler $addLine, private UpdateSaleLineHandler $updateLine, private RemoveSaleLineHandler $removeLine, private CancelSaleHandler $cancel, private CompleteSaleService $complete, private SaleViewFactory $views, private SaleResourceMapper $mapper) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SaleResource|CompleteSaleResultResource
    {
        $actor = $this->actors->resolve();
        $name = $operation->getName();
        if ('sale_create' === $name) {
            $sale = ($this->create)(new CreateSale(StoreId::fromString((string) ($uriVariables['storeId'] ?? throw new InvalidArgumentException('Store identifier is required.')), $this->uuids), $actor));

            return $this->mapper->map($this->views->create($sale));
        }
        $saleId = SaleId::fromString((string) ($uriVariables['id'] ?? throw new InvalidArgumentException('Sale identifier is required.')), $this->uuids);
        if ('sale_line_add' === $name) {
            if (!$data instanceof SaleLineInput) {
                throw new InvalidArgumentException('Sale line input is required.');
            }
            $sale = ($this->addLine)(new AddSaleLine($saleId, ProductId::fromString($data->productId, $this->uuids), ProductPackagingId::fromString($data->productPackagingId, $this->uuids), Quantity::fromString($data->quantity, $this->decimals), $actor));

            return $this->mapper->map($this->views->create($sale));
        }
        $lineId = isset($uriVariables['lineId']) ? SaleLineId::fromString((string) $uriVariables['lineId'], $this->uuids) : null;
        if ('sale_line_update' === $name) {
            if (!$data instanceof UpdateSaleLineInput || null === $lineId) {
                throw new InvalidArgumentException('Sale line update input is required.');
            }
            return $this->mapper->map($this->views->create(($this->updateLine)(new UpdateSaleLine($saleId, $lineId, Quantity::fromString($data->quantity, $this->decimals), $actor))));
        }
        if ('sale_line_remove' === $name) {
            return $this->mapper->map($this->views->create(($this->removeLine)(new RemoveSaleLine($saleId, $lineId ?? throw new InvalidArgumentException('Sale line identifier is required.'), $actor))));
        }
        if ('sale_cancel' === $name) {
            return $this->mapper->map($this->views->create(($this->cancel)(new CancelSale($saleId, $actor))));
        }
        if ('sale_complete' !== $name || !$data instanceof CompleteSaleInput) {
            throw new InvalidArgumentException('Unsupported sale operation.');
        }
        if ('CASH' !== strtoupper(trim($data->payment->method))) {
            throw new InvalidArgumentException('Only CASH payment is supported.');
        }
        $keyValue = $this->requests->getCurrentRequest()?->headers->get('Idempotency-Key');
        if (null === $keyValue) {
            throw new InvalidArgumentException('Idempotency-Key header is required.');
        }
        $key = IdempotencyKey::fromString($keyValue);
        $result = ($this->complete)(new CompleteSale(
            $saleId,
            $this->money($data->payment->amount),
            CashSessionId::fromString($data->cashSessionId, $this->uuids),
            $actor,
            $key->toString(),
            null === $data->tenderedAmount ? null : $this->money($data->tenderedAmount),
        ));

        return new CompleteSaleResultResource(
            $result->saleId->toString(),
            'COMPLETED',
            ['amount' => $result->total->amount()->toString(), 'currency' => $result->total->currency()->code()],
            $result->paymentId->toString(),
            ['amount' => $result->changeAmount->amount()->toString(), 'currency' => $result->changeAmount->currency()->code()],
        );
    }

    private function money(MoneyInput $input): Money
    {
        return Money::fromString($input->amount, Currency::fromCode($input->currency), $this->decimals);
    }
}
