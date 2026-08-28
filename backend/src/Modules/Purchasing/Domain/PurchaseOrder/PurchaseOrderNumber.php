<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Domain\PurchaseOrder;

use InvalidArgumentException;

final readonly class PurchaseOrderNumber
{
    private function __construct(private string $value) {}

    public static function fromString(string $value): self
    {
        $value = strtoupper(trim($value));
        if ('' === $value || mb_strlen($value) > 64) {
            throw new InvalidArgumentException('Purchase order number must contain between 1 and 64 characters.');
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
