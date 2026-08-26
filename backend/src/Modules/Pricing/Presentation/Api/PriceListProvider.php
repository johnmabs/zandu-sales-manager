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
    public function __construct(private CurrentActorProvider $actors, private PriceListQueryService $queries) {} /** @return list<PriceListResource> */ public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $actor = $this->actors->resolve();
        $views = 'price_list_list' === $operation->getName() ? $this->queries->list($actor) : [$this->queries->get(is_string($uriVariables['id'] ?? null) ? $uriVariables['id'] : throw new InvalidArgumentException('Price list identifier is required.'), $actor)];
        return array_map(static fn($v) => new PriceListResource($v->id, $v->organizationId, $v->code, $v->name, $v->currency, $v->status, $v->scope, $v->validFrom, $v->validTo, $v->priority, $v->createdAt, $v->version), $views);
    }
}
