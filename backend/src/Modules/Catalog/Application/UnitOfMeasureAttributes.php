<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

use InvalidArgumentException;
use Zandu\Modules\Catalog\Domain\UnitOfMeasureDimension;
use Zandu\Modules\Catalog\Domain\UnitOfMeasureName;
use Zandu\Modules\Catalog\Domain\UnitOfMeasurePrecision;
use Zandu\SharedKernel\Decimal\RoundingMode;

final readonly class UnitOfMeasureAttributes
{
    private function __construct(
        public UnitOfMeasureName $name,
        public UnitOfMeasureDimension $dimension,
        public UnitOfMeasurePrecision $precision,
        public RoundingMode $roundingMode,
    ) {}

    public static function fromPrimitives(
        string $name,
        string $dimension,
        int $precision,
        string $roundingMode,
    ): self {
        return new self(
            UnitOfMeasureName::fromString($name),
            UnitOfMeasureDimension::from(strtoupper(trim($dimension))),
            UnitOfMeasurePrecision::fromInt($precision),
            self::parseRoundingMode($roundingMode),
        );
    }

    private static function parseRoundingMode(string $name): RoundingMode
    {
        return match (strtoupper(trim($name))) {
            'UNNECESSARY' => RoundingMode::Unnecessary,
            'UP' => RoundingMode::Up,
            'DOWN' => RoundingMode::Down,
            'CEILING' => RoundingMode::Ceiling,
            'FLOOR' => RoundingMode::Floor,
            'HALF_UP' => RoundingMode::HalfUp,
            'HALF_DOWN' => RoundingMode::HalfDown,
            'HALF_EVEN' => RoundingMode::HalfEven,
            default => throw new InvalidArgumentException(sprintf('Unsupported rounding mode "%s".', $name)),
        };
    }
}
