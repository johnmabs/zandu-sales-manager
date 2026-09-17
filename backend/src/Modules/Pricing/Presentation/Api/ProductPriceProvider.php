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
    public function __construct(private CurrentActorProvider $actors, private ProductPriceQueryService $queries) {} /** @return ProductPriceResource|list<ProductPriceResource> */ public function provide(Operation $op, array $vars = [], array $context = []): ProductPriceResource|array
    {
        $a = $this->actors->resolve();
        if ('product_price_list' === $op->getName()) {
            return array_map(self::resource(...), $this->queries->list($a));
        }
        return self::resource($this->queries->get(is_string($vars['id'] ?? null) ? $vars['id'] : throw new InvalidArgumentException('Product price identifier is required.'), $a));
    }

    private static function resource(\Zandu\Modules\Pricing\Application\ProductPriceView $view): ProductPriceResource
    {
        return new ProductPriceResource($view->id, $view->organizationId, $view->priceListId, $view->productId, $view->packagingId, $view->amount, $view->currency, $view->status, $view->validFrom, $view->validTo, $view->createdAt, $view->version);
    }
}
