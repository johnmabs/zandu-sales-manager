<?php

declare(strict_types=1);

namespace Zandu\Platform\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Zandu\Platform\Api\Exception\ApplicationErrorException;
use Zandu\SharedKernel\Error\Result;

/**
 * @template TInput
 * @template TCommand of object
 * @template TResult
 * @template TOutput
 *
 * @implements ProcessorInterface<TInput, TOutput>
 */
abstract class ApplicationCommandProcessor implements ProcessorInterface
{
    final public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): mixed {
        $command = $this->createCommand($data, $uriVariables, $context);
        $result = $this->handle($command);

        if ($result->isFailure()) {
            throw new ApplicationErrorException($result->error());
        }

        return $this->createOutput($result->value());
    }

    /**
     * @param TInput              $data
     * @param array<string,mixed> $uriVariables
     * @param array<string,mixed> $context
     *
     * @return TCommand
     */
    abstract protected function createCommand(mixed $data, array $uriVariables, array $context): object;

    /**
     * @param TCommand $command
     *
     * @return Result<TResult>
     */
    abstract protected function handle(object $command): Result;

    /**
     * @param TResult $result
     *
     * @return TOutput
     */
    abstract protected function createOutput(mixed $result): mixed;
}
