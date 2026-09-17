<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use InvalidArgumentException;
use Zandu\Modules\Catalog\Application\ProductBarcodeQueryService;
use Zandu\Modules\Catalog\Application\ProductBarcodeView;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Identity\UuidFactory;
use Zandu\SharedKernel\Tenancy\TenantTransaction;

/** @implements ProviderInterface<ProductBarcodeResource> */
final readonly class ProductBarcodeProvider implements ProviderInterface
{
    public function __construct(private CurrentActorProvider $actors, private UuidFactory $uuids, private TenantTransaction $transaction, private ProductBarcodeQueryService $queries) {}

    /** @return list<ProductBarcodeResource> */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $actor = $this->actors->resolve();
        $rawPackagingId = $uriVariables['packagingId'] ?? null;
        $rawProductId = $uriVariables['productId'] ?? null;
        if (!is_string($rawPackagingId)) {
            throw new InvalidArgumentException('Packaging identifier is required.');
        }
        if (!is_string($rawProductId)) {
            throw new InvalidArgumentException('Product identifier is required.');
        }
        return $this->transaction->transactional($actor->organizationId(), fn(): array => array_map(
            self::resource(...),
            $this->queries->list(ProductId::fromString($rawProductId, $this->uuids), ProductPackagingId::fromString($rawPackagingId, $this->uuids), $actor),
        ));
    }

    private static function resource(ProductBarcodeView $barcode): ProductBarcodeResource
    {
        return new ProductBarcodeResource($barcode->id, $barcode->productId, $barcode->packagingId, $barcode->barcode, $barcode->status, $barcode->version);
    }
}
