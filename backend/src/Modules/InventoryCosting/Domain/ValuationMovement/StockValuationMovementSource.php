<?php

declare(strict_types=1);

namespace Zandu\Modules\InventoryCosting\Domain\ValuationMovement;

use InvalidArgumentException;

final readonly class StockValuationMovementSource
{
    private function __construct(
        private string $type,
        private ?string $referenceId,
    ) {}

    public static function from(string $type, ?string $referenceId = null): self
    {
        $type = strtoupper(trim($type));
        if (1 !== preg_match('/^[A-Z][A-Z0-9_]{0,63}$/', $type)) {
            throw new InvalidArgumentException('Valuation movement source type is invalid.');
        }
        if (null !== $referenceId && '' === trim($referenceId)) {
            throw new InvalidArgumentException('Valuation movement source reference cannot be empty.');
        }

        return new self($type, $referenceId);
    }

    public function type(): string
    {
        return $this->type;
    }

    public function referenceId(): ?string
    {
        return $this->referenceId;
    }
}
