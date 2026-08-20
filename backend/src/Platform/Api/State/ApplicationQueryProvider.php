<?php

declare(strict_types=1);

namespace Zandu\Platform\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;

/**
 * @template TQuery of object
 * @template TReadModel of object
 *
 * @implements ProviderInterface<TReadModel>
 */
abstract class ApplicationQueryProvider implements ProviderInterface
{
    final public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): object|array|null {
        return $this->handle($this->createQuery($uriVariables, $context));
    }

    /**
     * @param array<string,mixed> $uriVariables
     * @param array<string,mixed> $context
     *
     * @return TQuery
     */
    abstract protected function createQuery(array $uriVariables, array $context): object;

    /**
     * @param TQuery $query
     *
     * @return TReadModel|list<TReadModel>|null
     */
    abstract protected function handle(object $query): object|array|null;
}
