<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use Zandu\Modules\Catalog\Application\AddProductBarcode;
use Zandu\Modules\Catalog\Application\AddProductBarcodeHandler;
use Zandu\Modules\Catalog\Application\RemoveProductBarcode;
use Zandu\Modules\Catalog\Application\RemoveProductBarcodeHandler;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Identity\ProductBarcodeId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Identity\UuidFactory;

/** @implements ProcessorInterface<mixed, ProductBarcodeResource> */
final readonly class ProductBarcodeProcessor implements ProcessorInterface
{
    public function __construct(private CurrentActorProvider $actors, private UuidFactory $uuids, private AddProductBarcodeHandler $add, private RemoveProductBarcodeHandler $remove) {}
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ProductBarcodeResource
    {
        $actor = $this->actors->resolve();
        if ('barcode_add' === $operation->getName()) {
            $input = $data instanceof ProductBarcodeCreateInput ? $data : throw new InvalidArgumentException('Barcode input is required.');
            $b = ($this->add)(new AddProductBarcode(ProductPackagingId::fromString($this->id($uriVariables, 'packagingId'), $this->uuids), $input->barcode, $actor));
        } else {
            $b = ($this->remove)(new RemoveProductBarcode(ProductBarcodeId::fromString($this->id($uriVariables, 'id'), $this->uuids), $actor));
        } return new ProductBarcodeResource($b->id()->toString(), $b->productId()->toString(), $b->packagingId()->toString(), $b->barcode()->raw(), $b->status()->value, $b->version());
    }
    /** @param array<string, mixed> $vars */
    private function id(array $vars, string $name): string
    {
        $v = $vars[$name] ?? null;
        return is_string($v) ? $v : throw new InvalidArgumentException(sprintf('%s is required.', $name));
    }
}
