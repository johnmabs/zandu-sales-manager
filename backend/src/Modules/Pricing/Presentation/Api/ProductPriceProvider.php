<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use InvalidArgumentException;
use Zandu\Modules\Pricing\Application\ProductPriceQueryService;
use Zandu\SharedKernel\Context\CurrentActorProvider;

/** @implements ProviderInterface<ProductPriceResource> */
final readonly class ProductPriceProvider implements ProviderInterface
{
    public function __construct(private CurrentActorProvider $actors, private ProductPriceQueryService $queries) {} /** @return list<ProductPriceResource> */ public function provide(Operation $op, array $vars = [], array $context = []): array
    {
        $a = $this->actors->resolve();
        $views = 'product_price_list' === $op->getName() ? $this->queries->list($a) : [$this->queries->get(is_string($vars['id'] ?? null) ? $vars['id'] : throw new InvalidArgumentException('Product price identifier is required.'), $a)];
        return array_map(static fn($v) => new ProductPriceResource($v->id, $v->organizationId, $v->priceListId, $v->productId, $v->packagingId, $v->amount, $v->currency, $v->status, $v->validFrom, $v->validTo, $v->createdAt, $v->version), $views);
    }
}
