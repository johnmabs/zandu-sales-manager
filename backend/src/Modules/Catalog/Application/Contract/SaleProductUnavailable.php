<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application\Contract;

use RuntimeException;
use Zandu\SharedKernel\Error\CodedDomainException;

final class SaleProductUnavailable extends RuntimeException implements CodedDomainException
{
    private function __construct(private readonly string $errorCode, private readonly string $publicMessage)
    {
        parent::__construct($publicMessage);
    }

    public static function product(): self
    {
        return new self('PRODUCT_NOT_SELLABLE', 'The product is not available for sale.');
    }

    public static function packaging(): self
    {
        return new self('PACKAGING_NOT_SELLABLE', 'The product packaging is not available for sale.');
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function publicMessage(): string
    {
        return $this->publicMessage;
    }
}
