<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain\ProductBarcode;

use InvalidArgumentException;

final readonly class Barcode
{
    private function __construct(private string $raw, private string $normalized) {}
    public static function fromString(string $value): self
    {
        $raw = trim($value);
        if ('' === $raw || mb_strlen($raw) > 128 || 1 === preg_match('/[\x00-\x1F\x7F]/', $raw)) {
            throw new InvalidArgumentException('Barcode must contain between 1 and 128 printable characters.');
        }
        $normalized = preg_replace('/\s+/u', '', mb_strtoupper($raw, 'UTF-8'));
        if (null === $normalized || '' === $normalized) {
            throw new InvalidArgumentException('Barcode cannot be empty after normalization.');
        }
        return new self($raw, $normalized);
    }
    public function raw(): string
    {
        return $this->raw;
    }
    public function normalized(): string
    {
        return $this->normalized;
    }
}
