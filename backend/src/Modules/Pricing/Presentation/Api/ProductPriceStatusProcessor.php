<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use Zandu\Modules\Pricing\Application\{ActivateProductPrice,ActivateProductPriceHandler,ArchiveProductPrice,ArchiveProductPriceHandler,DeactivateProductPrice,DeactivateProductPriceHandler};
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Identity\{ProductPriceId,UuidFactory};

/** @implements ProcessorInterface<mixed, ProductPriceResource> */
final readonly class ProductPriceStatusProcessor implements ProcessorInterface
{
    public function __construct(private CurrentActorProvider $actors, private UuidFactory $uuids, private ActivateProductPriceHandler $activate, private DeactivateProductPriceHandler $deactivate, private ArchiveProductPriceHandler $archive) {} public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ProductPriceResource
    {
        $id = ProductPriceId::fromString(is_string($uriVariables['id'] ?? null) ? $uriVariables['id'] : throw new InvalidArgumentException('Product price identifier is required.'), $this->uuids);
        $a = $this->actors->resolve();
        $p = match ($operation->getName()) {
            'product_price_activate' => ($this->activate)(new ActivateProductPrice($id, $a)), 'product_price_deactivate' => ($this->deactivate)(new DeactivateProductPrice($id, $a)), 'product_price_archive' => ($this->archive)(new ArchiveProductPrice($id, $a)), default => throw new InvalidArgumentException('Unsupported product price operation.')
        };
        return new ProductPriceResource($p->id()->toString(), $p->organizationId()->toString(), $p->priceListId()->toString(), $p->productId()->toString(), $p->packagingId()->toString(), $p->amount()->amount()->toString(), $p->amount()->currency()->code(), $p->status()->value, $p->validFrom()?->format(DATE_ATOM), $p->validTo()?->format(DATE_ATOM), $p->createdAt()->format(DATE_ATOM), $p->version());
    }
}
