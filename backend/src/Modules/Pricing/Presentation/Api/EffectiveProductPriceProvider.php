<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use DateTimeImmutable;
use InvalidArgumentException;
use Zandu\Modules\Pricing\Application\Contract\ProductPriceResolver;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Identity\{ProductId,ProductPackagingId,UuidFactory};
use Zandu\SharedKernel\Tenancy\TenantTransaction;

/** @implements ProviderInterface<EffectiveProductPriceResource> */
final readonly class EffectiveProductPriceProvider implements ProviderInterface
{
    public function __construct(private CurrentActorProvider $actors, private UuidFactory $uuids, private TenantTransaction $tx, private ProductPriceResolver $resolver) {} public function provide(Operation $operation, array $uriVariables = [], array $context = []): EffectiveProductPriceResource
    {
        $a = $this->actors->resolve();
        $product = ProductId::fromString($this->value($uriVariables, 'productId'), $this->uuids);
        $pack = ProductPackagingId::fromString($this->value($uriVariables, 'packagingId'), $this->uuids);
        $at = $context['filters']['at'] ?? null;
        $when = is_string($at) ? new DateTimeImmutable($at) : new DateTimeImmutable();
        $r = $this->tx->transactional($a->organizationId(), fn() => $this->resolver->resolve($a->organizationId(), $product, $pack, $when));
        return new EffectiveProductPriceResource($r->priceListId()->toString(), $r->productPriceId()->toString(), $r->amount()->toString(), $r->currency()->code(), $r->sourceVersion());
    } private function value(array $v, string $k): string
    {
        $x = $v[$k] ?? null;
        return is_string($x) ? $x : throw new InvalidArgumentException(sprintf('%s is required.',$k));
    }
}
