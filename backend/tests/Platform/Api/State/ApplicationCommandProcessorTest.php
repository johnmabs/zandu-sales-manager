<?php

declare(strict_types=1);

namespace Zandu\Tests\Platform\Api\State;

use ApiPlatform\Metadata\Post;
use LogicException;
use PHPUnit\Framework\TestCase;
use Zandu\Platform\Api\Exception\ApplicationErrorException;
use Zandu\Platform\Api\State\ApplicationCommandProcessor;
use Zandu\SharedKernel\Error\DomainError;
use Zandu\SharedKernel\Error\Result;

final class ApplicationCommandProcessorTest extends TestCase
{
    public function testItMapsInputExecutesACommandAndMapsTheResult(): void
    {
        $processor = new TestCommandProcessor();

        $output = $processor->process(
            new TestCommandInput('sale-42'),
            new Post(),
            ['organizationId' => 'organization-7'],
        );

        self::assertInstanceOf(TestCommandOutput::class, $output);
        self::assertSame('sale-42@organization-7', $output->reference);
    }

    public function testItTranslatesAnExpectedFailureAtTheBoundary(): void
    {
        $processor = new TestCommandProcessor();

        try {
            $processor->process(new TestCommandInput('invalid'), new Post());
            self::fail('An application failure should stop HTTP output mapping.');
        } catch (ApplicationErrorException $exception) {
            self::assertSame('SALE_INVALID', $exception->error()->code());
            self::assertSame('The sale is invalid.', $exception->getMessage());
        }
    }
}

final readonly class TestCommandInput
{
    public function __construct(public string $reference)
    {
    }
}

final readonly class TestCommand
{
    public function __construct(public string $reference)
    {
    }
}

final readonly class TestCommandOutput
{
    public function __construct(public string $reference)
    {
    }
}

/**
 * @extends ApplicationCommandProcessor<TestCommandInput, TestCommand, string, TestCommandOutput>
 */
final class TestCommandProcessor extends ApplicationCommandProcessor
{
    protected function createCommand(mixed $data, array $uriVariables, array $context): object
    {
        if (!$data instanceof TestCommandInput) {
            throw new LogicException('Unexpected command input.');
        }

        return new TestCommand($data->reference.'@'.($uriVariables['organizationId'] ?? 'none'));
    }

    protected function handle(object $command): Result
    {
        if (!$command instanceof TestCommand) {
            throw new LogicException('Unexpected command.');
        }

        if ('invalid@none' === $command->reference) {
            return Result::failure(DomainError::create('SALE_INVALID', 'The sale is invalid.'));
        }

        return Result::success($command->reference);
    }

    protected function createOutput(mixed $result): mixed
    {
        if (!is_string($result)) {
            throw new LogicException('Unexpected command result.');
        }

        return new TestCommandOutput($result);
    }
}
