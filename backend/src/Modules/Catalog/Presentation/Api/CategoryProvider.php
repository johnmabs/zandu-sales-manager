<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use InvalidArgumentException;
use Zandu\Modules\Catalog\Application\CategoryQueryService;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Identity\CategoryId;
use Zandu\SharedKernel\Identity\UuidFactory;
use Zandu\SharedKernel\Tenancy\TenantTransaction;

/** @implements ProviderInterface<CategoryResource> */
final readonly class CategoryProvider implements ProviderInterface
{
    public function __construct(
        private CurrentActorProvider $actors,
        private UuidFactory $uuidFactory,
        private TenantTransaction $transaction,
        private CategoryQueryService $queries,
        private CategoryResourceFactory $resources,
    ) {}

    /** @return CategoryResource|list<CategoryResource> */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CategoryResource|array
    {
        $actor = $this->actors->resolve();

        return $this->transaction->transactional($actor->organizationId(), function () use ($actor, $operation, $uriVariables): CategoryResource|array {
            if ('category_list' === $operation->getName()) {
                return array_map($this->resources->fromView(...), $this->queries->list($actor));
            }

            $rawId = $uriVariables['id'] ?? null;
            if (!is_string($rawId)) {
                throw new InvalidArgumentException('Category identifier is required.');
            }

            return $this->resources->fromView($this->queries->get(CategoryId::fromString($rawId, $this->uuidFactory), $actor));
        });
    }
}
