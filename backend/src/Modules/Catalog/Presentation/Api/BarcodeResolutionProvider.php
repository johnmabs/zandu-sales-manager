<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use InvalidArgumentException;
use Zandu\Modules\Catalog\Application\BarcodeQueryService;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Tenancy\TenantTransaction;

/** @implements ProviderInterface<BarcodeResolutionResource> */
final readonly class BarcodeResolutionProvider implements ProviderInterface
{
    public function __construct(private CurrentActorProvider $actors, private TenantTransaction $transaction, private BarcodeQueryService $resolver) {} public function provide(Operation $operation, array $uriVariables = [], array $context = []): BarcodeResolutionResource
    {
        $actor = $this->actors->resolve();
        $raw = $uriVariables['barcode'] ?? null;
        if (!is_string($raw)) {
            throw new InvalidArgumentException('Barcode is required.');
        }$result = $this->transaction->transactional($actor->organizationId(), fn() => $this->resolver->resolve($raw, $actor));
        return new BarcodeResolutionResource($result->productId()->toString(), $result->packagingId()->toString());
    }
}
