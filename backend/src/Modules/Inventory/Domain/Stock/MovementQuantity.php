<?php
declare(strict_types=1);
namespace Zandu\Modules\Inventory\Domain\Stock;
use InvalidArgumentException;
use Zandu\SharedKernel\Quantity\Quantity;
final readonly class MovementQuantity
{
    public function __construct(private Quantity $value) { if ($value->isNegative() || $value->isZero()) throw new InvalidArgumentException('Movement quantity must be positive.'); }
    public function value(): Quantity { return $this->value; }
    public function toString(): string { return $this->value->toString(); }
}
