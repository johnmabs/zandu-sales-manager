<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

use Zandu\Modules\Catalog\Domain\ProductBarcode\Barcode;
use Zandu\Modules\Catalog\Domain\ProductBarcode\ProductBarcodeNotFound;
use Zandu\SharedKernel\Context\ActorContext;

final readonly class BarcodeQueryService
{
    public function __construct(private BarcodeResolver $resolver) {} public function resolve(string $raw, ActorContext $actor): BarcodeResolution
    {
        $result = $this->resolver->resolve($actor->organizationId(), Barcode::fromString($raw));
        if (null === $result) {
            throw new ProductBarcodeNotFound('Barcode was not found.');
        } return $result;
    }
}
