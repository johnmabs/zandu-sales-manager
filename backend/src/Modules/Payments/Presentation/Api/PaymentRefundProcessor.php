<?php

declare(strict_types=1);

namespace Zandu\Modules\Payments\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\RequestStack;
use Zandu\Modules\Payments\Application\{CreateCashPaymentRefund, CreateCashPaymentRefundService};
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Idempotency\IdempotencyKey;
use Zandu\SharedKernel\Identity\{CashSessionId, PaymentId, ReturnSaleId, UuidFactory};
use Zandu\SharedKernel\Money\{Currency, Money};

/** @implements ProcessorInterface<mixed, PaymentRefundResource> */
final readonly class PaymentRefundProcessor implements ProcessorInterface
{
    public function __construct(
        private CurrentActorProvider $actors,
        private UuidFactory $uuids,
        private DecimalFactory $decimals,
        private RequestStack $requests,
        private CreateCashPaymentRefundService $create,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PaymentRefundResource
    {
        if ('payment_refund_create' !== $operation->getName() || !$data instanceof PaymentRefundInput) {
            throw new InvalidArgumentException('Payment refund input is required.');
        }
        $keyValue = $this->requests->getCurrentRequest()?->headers->get('Idempotency-Key');
        if (null === $keyValue) {
            throw new InvalidArgumentException('Idempotency-Key header is required.');
        }
        $refund = ($this->create)(new CreateCashPaymentRefund(
            PaymentId::fromString((string) ($uriVariables['paymentId'] ?? throw new InvalidArgumentException('Payment identifier is required.')), $this->uuids),
            ReturnSaleId::fromString($data->returnSaleId, $this->uuids),
            CashSessionId::fromString($data->cashSessionId, $this->uuids),
            Money::fromString($data->amount, Currency::fromCode($data->currency), $this->decimals),
            $data->reason,
            IdempotencyKey::fromString($keyValue),
            $this->actors->resolve(),
        ));

        return new PaymentRefundResource(
            $refund->id()->toString(),
            $refund->paymentId()->toString(),
            $refund->returnSaleId()->toString(),
            $refund->cashSessionId()->toString(),
            $refund->status()->value,
            ['amount' => $refund->amount()->amount()->toString(), 'currency' => $refund->amount()->currency()->code()],
            $refund->reason(),
            $refund->confirmedAt()?->format(DATE_ATOM) ?? throw new \LogicException('Confirmed refund timestamp is missing.'),
        );
    }
}
