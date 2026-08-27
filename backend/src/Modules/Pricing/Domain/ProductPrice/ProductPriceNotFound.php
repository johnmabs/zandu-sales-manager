<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Domain\ProductPrice;

use RuntimeException;
use Zandu\SharedKernel\Error\{CodedDomainException,ResourceNotFound};
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Identity\ProductPriceId;

final class ProductPriceNotFound extends RuntimeException implements ResourceNotFound, CodedDomainException
{
    public static function withId(ProductPriceId $id): self
    {
        return new self(sprintf('Product price "%s" was not found.', $id->toString()));
    }

    public static function forTarget(
        OrganizationId $organizationId,
        ProductId $productId,
        ProductPackagingId $packagingId,
    ): self {
        return new self(sprintf(
            'No product price was found for organization "%s", product "%s" and packaging "%s".',
            $organizationId->toString(),
            $productId->toString(),
            $packagingId->toString(),
        ));
    }

    public function errorCode(): string
    {
        return 'PRODUCT_PRICE_NOT_FOUND';
    }

    public function publicMessage(): string
    {
        return 'No applicable product price was found.';
    }
}
