<?php

declare(strict_types=1);

namespace Zandu\Tests\Platform\Api\EventSubscriber;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Zandu\Platform\Api\EventSubscriber\ApplicationErrorExceptionSubscriber;
use Zandu\Platform\Api\EventSubscriber\CorrelationIdRequestSubscriber;
use Zandu\Platform\Api\Exception\ApplicationErrorException;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Error\DomainError;
use Zandu\SharedKernel\Messaging\CorrelationId;

final class ApplicationErrorExceptionSubscriberTest extends TestCase
{
    private const CORRELATION_ID = '0198c728-8f2d-7f43-92d8-3f0c75b80186';

    public function testItNormalizesAnApplicationErrorWithItsCorrelationId(): void
    {
        $request = new Request();
        $request->attributes->set(
            CorrelationIdRequestSubscriber::REQUEST_ATTRIBUTE,
            CorrelationId::fromString(self::CORRELATION_ID, new SymfonyUuidFactory()),
        );
        $event = new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new ApplicationErrorException(DomainError::create(
                'SALE_ALREADY_COMPLETED',
                'The sale is already completed.',
            )),
        );

        (new ApplicationErrorExceptionSubscriber())->onKernelException($event);

        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        self::assertSame([
            'code' => 'SALE_ALREADY_COMPLETED',
            'message' => 'The sale is already completed.',
            'correlationId' => self::CORRELATION_ID,
        ], json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR));
    }

    public function testItIgnoresUnexpectedTechnicalExceptions(): void
    {
        $event = new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            new \RuntimeException('Technical failure.'),
        );

        (new ApplicationErrorExceptionSubscriber())->onKernelException($event);

        self::assertNull($event->getResponse());
    }
}
