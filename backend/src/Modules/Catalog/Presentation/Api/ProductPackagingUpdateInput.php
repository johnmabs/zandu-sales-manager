<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Presentation\Api;

final readonly class ProductPackagingUpdateInput
{
    public function __construct(
        public string $name,
        public string $minimumQuantity,
        public string $quantityIncrement,
        public bool $allowedForSale,
        public bool $allowedForPurchase,
        #[\ApiPlatform\Metadata\ApiProperty(required: true, description: 'Version read by the client before editing.')]
        #[\Symfony\Component\Validator\Constraints\Positive]
        public int $expectedVersion,
    ) {}
}
