<?php

declare(strict_types=1);

namespace Zandu\Modules\Payments\Presentation\Api;

use ApiPlatform\Metadata\{ApiResource, Link, Post};

#[ApiResource(operations: [
    new Post(
        name: 'payment_refund_create',
        uriTemplate: '/payments/{paymentId}/refunds',
        uriVariables: ['paymentId' => new Link(fromClass: self::class, identifiers: ['id'])],
        read: false,
        input: PaymentRefundInput::class,
        processor: PaymentRefundProcessor::class,
    ),
])]
final readonly class PaymentRefundResource
{
    /** @param array{amount:string,currency:string} $amount */
    public function __construct(
        public string $id,
        public string $paymentId,
        public string $returnSaleId,
        public string $cashSessionId,
        public string $status,
        public array $amount,
        public ?string $reason,
        public string $confirmedAt,
    ) {}
}
