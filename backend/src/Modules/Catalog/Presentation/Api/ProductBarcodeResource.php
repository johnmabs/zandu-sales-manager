<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Presentation\Api;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;

#[ApiResource(operations: [new GetCollection(name: 'barcode_list', uriTemplate: '/products/{productId}/packagings/{packagingId}/barcodes', provider: ProductBarcodeProvider::class), new Post(name: 'barcode_add', uriTemplate: '/products/{productId}/packagings/{packagingId}/barcodes', input: ProductBarcodeCreateInput::class, processor: ProductBarcodeProcessor::class),new Delete(name: 'barcode_remove', uriTemplate: '/products/{productId}/packagings/{packagingId}/barcodes/{id}', read: false, processor: ProductBarcodeProcessor::class)])]
final readonly class ProductBarcodeResource
{
    public function __construct(public string $id, public string $productId, public string $packagingId, public string $barcode, public string $status, public int $version) {}
}
