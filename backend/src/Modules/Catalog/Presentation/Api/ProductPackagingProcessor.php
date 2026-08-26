<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use Zandu\Modules\Catalog\Application\ArchiveProductPackaging;
use Zandu\Modules\Catalog\Application\ArchiveProductPackagingHandler;
use Zandu\Modules\Catalog\Application\CreateProductPackaging;
use Zandu\Modules\Catalog\Application\CreateProductPackagingHandler;
use Zandu\Modules\Catalog\Application\DeactivateProductPackaging;
use Zandu\Modules\Catalog\Application\DeactivateProductPackagingHandler;
use Zandu\Modules\Catalog\Application\ProductPackagingViewFactory;
use Zandu\Modules\Catalog\Application\UpdateProductPackaging;
use Zandu\Modules\Catalog\Application\UpdateProductPackagingHandler;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Identity\UnitOfMeasureId;
use Zandu\SharedKernel\Identity\UuidFactory;

/** @implements ProcessorInterface<mixed, ProductPackagingResource> */
final readonly class ProductPackagingProcessor implements ProcessorInterface
{
    public function __construct(
        private CurrentActorProvider $actors,
        private UuidFactory $uuidFactory,
        private ProductPackagingViewFactory $views,
        private ProductPackagingResourceFactory $resources,
        private CreateProductPackagingHandler $create,
        private UpdateProductPackagingHandler $update,
        private DeactivateProductPackagingHandler $deactivate,
        private ArchiveProductPackagingHandler $archive,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ProductPackagingResource
    {
        $actor = $this->actors->resolve();
        $name = $operation->getName();
        if ('packaging_create' === $name) {
            $input = $data instanceof ProductPackagingCreateInput ? $data : throw new InvalidArgumentException('Packaging creation input is required.');
            $productId = ProductId::fromString($this->variable($uriVariables, 'productId'), $this->uuidFactory);
            $packaging = ($this->create)(new CreateProductPackaging($productId, $input->code, $input->name, UnitOfMeasureId::fromString($input->unitId, $this->uuidFactory), $input->conversionFactor, $input->precision, $input->minimumQuantity, $input->quantityIncrement, $input->allowedForSale, $input->allowedForPurchase, $actor));
        } else {
            $id = ProductPackagingId::fromString($this->variable($uriVariables, 'id'), $this->uuidFactory);
            if ('packaging_update' === $name) {
                $input = $data instanceof ProductPackagingUpdateInput ? $data : throw new InvalidArgumentException('Packaging update input is required.');
                $packaging = ($this->update)(new UpdateProductPackaging($id, $input->name, $input->minimumQuantity, $input->quantityIncrement, $input->allowedForSale, $input->allowedForPurchase, $actor));
            } else {
                $packaging = match ($name) {
                    'packaging_deactivate' => ($this->deactivate)(new DeactivateProductPackaging($id, $actor)),
                    'packaging_archive' => ($this->archive)(new ArchiveProductPackaging($id, $actor)),
                    default => throw new InvalidArgumentException('Unsupported packaging operation.'),
                };
            }
        }

        return $this->resources->fromView($this->views->fromAggregate($packaging));
    }

    /** @param array<string,mixed> $variables */
    private function variable(array $variables, string $name): string
    {
        $value = $variables[$name] ?? null;

        return is_string($value) ? $value : throw new InvalidArgumentException(sprintf('Packaging %s is required.', $name));
    }
}
