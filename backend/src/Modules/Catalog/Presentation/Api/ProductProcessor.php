<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use Zandu\Modules\Catalog\Application\ActivateProduct\ActivateProduct;
use Zandu\Modules\Catalog\Application\ActivateProduct\ActivateProductHandler;
use Zandu\Modules\Catalog\Application\ArchiveProduct\ArchiveProduct;
use Zandu\Modules\Catalog\Application\ArchiveProduct\ArchiveProductHandler;
use Zandu\Modules\Catalog\Application\CreateProduct\CreateProduct;
use Zandu\Modules\Catalog\Application\CreateProduct\CreateProductHandler;
use Zandu\Modules\Catalog\Application\DeactivateProduct\DeactivateProduct;
use Zandu\Modules\Catalog\Application\DeactivateProduct\DeactivateProductHandler;
use Zandu\Modules\Catalog\Application\ProductViewFactory;
use Zandu\Modules\Catalog\Application\ReactivateProduct\ReactivateProduct;
use Zandu\Modules\Catalog\Application\ReactivateProduct\ReactivateProductHandler;
use Zandu\Modules\Catalog\Application\UpdateProduct\UpdateProduct;
use Zandu\Modules\Catalog\Application\UpdateProduct\UpdateProductHandler;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Identity\CategoryId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\TaxCategoryId;
use Zandu\SharedKernel\Identity\UnitOfMeasureId;
use Zandu\SharedKernel\Identity\UuidFactory;
use Zandu\SharedKernel\Versioning\ExpectedVersion;

/** @implements ProcessorInterface<mixed, ProductResource> */
final readonly class ProductProcessor implements ProcessorInterface
{
    public function __construct(
        private CurrentActorProvider $actors,
        private UuidFactory $uuidFactory,
        private ProductViewFactory $views,
        private ProductResourceFactory $resources,
        private CreateProductHandler $create,
        private UpdateProductHandler $update,
        private ActivateProductHandler $activate,
        private DeactivateProductHandler $deactivate,
        private ReactivateProductHandler $reactivate,
        private ArchiveProductHandler $archive,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ProductResource
    {
        $actor = $this->actors->resolve();
        $name = $operation->getName();
        if ('product_create' === $name) {
            $input = $data instanceof ProductCreateInput ? $data : throw new InvalidArgumentException('Product creation input is required.');
            $product = ($this->create)(new CreateProduct($input->productCode, $input->name, $input->description, $input->type, $this->unitId($input->baseUnitId), $input->inventoryTracked, $this->nullableCategory($input->categoryId), $this->nullableTax($input->taxCategoryId), $actor));
            return $this->resources->fromView($this->views->fromAggregate($product));
        }

        $id = ProductId::fromString($this->id($uriVariables), $this->uuidFactory);
        if ('product_update' === $name) {
            $input = $data instanceof ProductUpdateInput ? $data : throw new InvalidArgumentException('Product update input is required.');
            $product = ($this->update)(new UpdateProduct($id, $input->productCode, $input->name, $input->description, $input->type, $this->unitId($input->baseUnitId), $input->inventoryTracked, $this->nullableCategory($input->categoryId), $this->nullableTax($input->taxCategoryId), ExpectedVersion::fromInt($input->expectedVersion), $actor));
            return $this->resources->fromView($this->views->fromAggregate($product));
        }

        $product = match ($name) {
            'product_activate' => ($this->activate)(new ActivateProduct($id, $actor)),
            'product_deactivate' => ($this->deactivate)(new DeactivateProduct($id, $actor)),
            'product_reactivate' => ($this->reactivate)(new ReactivateProduct($id, $actor)),
            'product_archive' => ($this->archive)(new ArchiveProduct($id, $actor)),
            default => throw new InvalidArgumentException('Unsupported product operation.'),
        };

        return $this->resources->fromView($this->views->fromAggregate($product));
    }

    /** @param array<string,mixed> $uriVariables */
    private function id(array $uriVariables): string
    {
        $id = $uriVariables['id'] ?? null;
        return is_string($id) ? $id : throw new InvalidArgumentException('Product identifier is required.');
    }

    private function unitId(string $id): UnitOfMeasureId
    {
        return UnitOfMeasureId::fromString($id, $this->uuidFactory);
    }
    private function nullableCategory(?string $id): ?CategoryId
    {
        return null !== $id ? CategoryId::fromString($id, $this->uuidFactory) : null;
    }
    private function nullableTax(?string $id): ?TaxCategoryId
    {
        return null !== $id ? TaxCategoryId::fromString($id, $this->uuidFactory) : null;
    }
}
