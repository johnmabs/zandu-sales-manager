<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Domain\Stock;

use InvalidArgumentException;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class StockQuantity
{
    public function __construct(private Quantity $value)
    {
        if ($value->isNegative()) {
            throw new InvalidArgumentException('Stock quantity cannot be negative.');
        }
    }
    public function value(): Quantity
    {
        return $this->value;
    }
    public function toString(): string
    {
        return $this->value->toString();
    }
    public function add(MovementQuantity $quantity): self
    {
        return new self($this->value->add($quantity->value()));
    }
    public function subtract(MovementQuantity $quantity): self
    {
        return new self($this->value->subtract($quantity->value()));
    }
}
