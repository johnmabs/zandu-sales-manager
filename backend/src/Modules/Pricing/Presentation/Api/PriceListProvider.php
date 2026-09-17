<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use InvalidArgumentException;
use Zandu\Modules\Pricing\Application\PriceListQueryService;
use Zandu\SharedKernel\Context\CurrentActorProvider;

/** @implements ProviderInterface<PriceListResource> */
final readonly class PriceListProvider implements ProviderInterface
{
    public function __construct(private CurrentActorProvider $actors, private PriceListQueryService $queries) {} /** @return PriceListResource|list<PriceListResource> */ public function provide(Operation $operation, array $uriVariables = [], array $context = []): PriceListResource|array
    {
        $actor = $this->actors->resolve();
        if ('price_list_list' === $operation->getName()) {
            return array_map(self::resource(...), $this->queries->list($actor));
        }
        return self::resource($this->queries->get(is_string($uriVariables['id'] ?? null) ? $uriVariables['id'] : throw new InvalidArgumentException('Price list identifier is required.'), $actor));
    }

    private static function resource(\Zandu\Modules\Pricing\Application\PriceListView $view): PriceListResource
    {
        return new PriceListResource($view->id, $view->organizationId, $view->code, $view->name, $view->currency, $view->status, $view->scope, $view->validFrom, $view->validTo, $view->priority, $view->createdAt, $view->version);
    }
}
