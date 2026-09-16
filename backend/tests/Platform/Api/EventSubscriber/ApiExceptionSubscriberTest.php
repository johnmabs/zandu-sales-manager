<?php

declare(strict_types=1);

namespace Zandu\Tests\Platform\Api\EventSubscriber;

use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\OptimisticLockException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Zandu\Platform\Api\EventSubscriber\ApiExceptionSubscriber;
use Zandu\Platform\Api\EventSubscriber\CorrelationIdRequestSubscriber;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Error\ResourceConflict;
use Zandu\SharedKernel\Error\ResourceNotFound;
use Zandu\SharedKernel\Messaging\CorrelationId;

final class ApiExceptionSubscriberTest extends TestCase
{
    #[DataProvider('mappedExceptions')]
    public function testItReturnsAStableSafeError(\Throwable $exception, int $status, string $code): void
    {
        $request = new Request();
        $request->attributes->set(
            CorrelationIdRequestSubscriber::REQUEST_ATTRIBUTE,
            CorrelationId::fromString('0198e463-147c-72d5-b75a-a936797ff9c8', new SymfonyUuidFactory()),
        );
        $event = new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception,
        );

        (new ApiExceptionSubscriber())->onKernelException($event);

        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertSame($status, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame($code, $payload['code']);
        self::assertSame('0198e463-147c-72d5-b75a-a936797ff9c8', $payload['correlationId']);
        self::assertStringNotContainsString('sensitive detail', $payload['message']);
    }

    /** @return iterable<string, array{\Throwable, int, string}> */
    public static function mappedExceptions(): iterable
    {
        yield 'validation' => [new \InvalidArgumentException('sensitive detail'), 400, 'VALIDATION_ERROR'];
        yield 'authentication' => [new AuthenticationException('sensitive detail'), 401, 'UNAUTHENTICATED'];
        yield 'authorization' => [new AccessDeniedException('sensitive detail'), 403, 'FORBIDDEN'];
        yield 'not found' => [new TestResourceNotFound('sensitive detail'), 404, 'NOT_FOUND'];
        yield 'conflict' => [new TestResourceConflict('sensitive detail'), 409, 'CONFLICT'];
        yield 'optimistic lock conflict' => [OptimisticLockException::lockFailed(new \stdClass()), 409, 'CONFLICT'];
        yield 'unique constraint conflict' => [new UniqueConstraintViolationException(new TestDriverException('sensitive detail'), null), 409, 'CONFLICT'];
        yield 'foreign key conflict' => [new ForeignKeyConstraintViolationException(new TestDriverException('sensitive detail'), null), 409, 'CONFLICT'];
        yield 'retryable database conflict' => [new DeadlockException(new TestDriverException('sensitive detail'), null), 409, 'CONFLICT'];
        yield 'domain rule' => [new \LogicException('sensitive detail'), 422, 'DOMAIN_RULE_VIOLATION'];
    }

    public function testItIgnoresUnknownExceptions(): void
    {
        $event = new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            new RuntimeException(),
        );

        (new ApiExceptionSubscriber())->onKernelException($event);

        self::assertFalse($event->hasResponse());
    }
}

final class TestResourceNotFound extends RuntimeException implements ResourceNotFound {}

final class TestResourceConflict extends RuntimeException implements ResourceConflict {}

final class TestDriverException extends RuntimeException implements DriverException
{
    public function getSQLState(): ?string
    {
        return null;
    }
}
