<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use DateTimeImmutable;
use InvalidArgumentException;
use Zandu\Modules\Pricing\Application\{CreateProductPrice,CreateProductPriceHandler};
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Identity\{PriceListId,ProductId,ProductPackagingId,UuidFactory};
use Zandu\SharedKernel\Money\{Currency,Money};

/** @implements ProcessorInterface<mixed, ProductPriceResource> */
final readonly class ProductPriceCreateProcessor implements ProcessorInterface
{
    public function __construct(private CurrentActorProvider $actors, private UuidFactory $uuids, private DecimalFactory $decimals, private CreateProductPriceHandler $create) {} public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ProductPriceResource
    {
        $i = $data instanceof ProductPriceCreateInput ? $data : throw new InvalidArgumentException('Product price input is required.');
        $p = ($this->create)(new CreateProductPrice(PriceListId::fromString($i->priceListId, $this->uuids), ProductId::fromString($i->productId, $this->uuids), ProductPackagingId::fromString($i->packagingId, $this->uuids), Money::fromString($i->amount, Currency::fromCode($i->currency), $this->decimals), null === $i->validFrom ? null : new DateTimeImmutable($i->validFrom), null === $i->validTo ? null : new DateTimeImmutable($i->validTo), $this->actors->resolve()));
        return new ProductPriceResource($p->id()->toString(), $p->organizationId()->toString(), $p->priceListId()->toString(), $p->productId()->toString(), $p->packagingId()->toString(), $p->amount()->amount()->toString(), $p->amount()->currency()->code(), $p->status()->value, $p->validFrom()?->format(DATE_ATOM), $p->validTo()?->format(DATE_ATOM), $p->createdAt()->format(DATE_ATOM), $p->version());
    }
}
