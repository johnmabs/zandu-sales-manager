<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Presentation\Api;

final readonly class CompleteSaleResultResource
{
    /**
     * @param array{amount:string,currency:string} $total
     * @param array{amount:string,currency:string} $changeAmount
     */
    public function __construct(public string $saleId, public string $status, public array $total, public string $paymentId, public array $changeAmount) {}
}
