<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use DateTimeImmutable;
use InvalidArgumentException;
use Zandu\Modules\Pricing\Application\UpdateProductPrice\{UpdateProductPrice,UpdateProductPriceHandler};
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Identity\ProductPriceId;
use Zandu\SharedKernel\Identity\UuidFactory;
use Zandu\SharedKernel\Money\{Currency,Money};

/** @implements ProcessorInterface<mixed, ProductPriceResource> */
final readonly class ProductPriceProcessor implements ProcessorInterface
{
    public function __construct(private CurrentActorProvider $actors, private UuidFactory $uuids, private DecimalFactory $decimals, private UpdateProductPriceHandler $update) {} public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ProductPriceResource
    {
        $i = $data instanceof ProductPriceUpdateInput ? $data : throw new InvalidArgumentException('Product price input is required.');
        $id = ProductPriceId::fromString(is_string($uriVariables['id'] ?? null) ? $uriVariables['id'] : throw new InvalidArgumentException('Product price identifier is required.'), $this->uuids);
        $result = ($this->update)(new UpdateProductPrice($id, Money::fromString($i->amount, Currency::fromCode($i->currency), $this->decimals), null === $i->validFrom ? null : new DateTimeImmutable($i->validFrom), null === $i->validTo ? null : new DateTimeImmutable($i->validTo), $this->actors->resolve()));
        return new ProductPriceResource($result->id()->toString(), $result->organizationId()->toString(), $result->priceListId()->toString(), $result->productId()->toString(), $result->packagingId()->toString(), $result->amount()->amount()->toString(), $result->amount()->currency()->code(), $result->status()->value, $result->validFrom()?->format(DATE_ATOM), $result->validTo()?->format(DATE_ATOM), $result->createdAt()->format(DATE_ATOM), $result->version());
    }
}
