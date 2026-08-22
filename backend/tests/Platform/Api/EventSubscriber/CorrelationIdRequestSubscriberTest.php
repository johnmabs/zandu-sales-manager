<?php

declare(strict_types=1);

namespace Zandu\Tests\Platform\Api\EventSubscriber;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Zandu\Platform\Api\EventSubscriber\CorrelationIdRequestSubscriber;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\Uuid;
use Zandu\SharedKernel\Messaging\CausationId;
use Zandu\SharedKernel\Messaging\CorrelationId;

final class CorrelationIdRequestSubscriberTest extends TestCase
{
    private const PROVIDED_ID = '0198c728-8f2d-7f43-92d8-3f0c75b80186';
    private const GENERATED_ID = '0198c728-a648-75b7-b7d7-c69d0bf84390';

    public function testItPropagatesAValidProvidedCorrelationId(): void
    {
        $request = new Request(server: ['HTTP_X_CORRELATION_ID' => self::PROVIDED_ID]);
        $subscriber = $this->subscriber();

        $subscriber->onKernelRequest($this->requestEvent($request));

        $correlationId = $request->attributes->get(CorrelationIdRequestSubscriber::REQUEST_ATTRIBUTE);
        self::assertInstanceOf(CorrelationId::class, $correlationId);
        self::assertSame(self::PROVIDED_ID, $correlationId->toString());
    }

    public function testItGeneratesAnIdWhenTheHeaderIsMissingOrInvalid(): void
    {
        foreach ([null, 'not-a-uuid'] as $header) {
            $request = new Request(server: null === $header ? [] : ['HTTP_X_CORRELATION_ID' => $header]);
            $subscriber = $this->subscriber();

            $subscriber->onKernelRequest($this->requestEvent($request));

            $correlationId = $request->attributes->get(CorrelationIdRequestSubscriber::REQUEST_ATTRIBUTE);
            self::assertInstanceOf(CorrelationId::class, $correlationId);
            self::assertSame(self::GENERATED_ID, $correlationId->toString());
        }
    }

    public function testItPropagatesAValidOptionalCausationId(): void
    {
        $request = new Request(server: ['HTTP_X_CAUSATION_ID' => self::PROVIDED_ID]);

        $this->subscriber()->onKernelRequest($this->requestEvent($request));

        $causationId = $request->attributes->get(CorrelationIdRequestSubscriber::CAUSATION_REQUEST_ATTRIBUTE);
        self::assertInstanceOf(CausationId::class, $causationId);
        self::assertSame(self::PROVIDED_ID, $causationId->toString());
    }

    public function testItAddsTheCorrelationIdToTheResponse(): void
    {
        $request = new Request();
        $request->attributes->set(
            CorrelationIdRequestSubscriber::REQUEST_ATTRIBUTE,
            CorrelationId::fromString(self::PROVIDED_ID, new SymfonyUuidFactory()),
        );
        $response = new Response();
        $subscriber = $this->subscriber();

        $subscriber->onKernelResponse(new ResponseEvent(
            $this->createStub(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        ));

        self::assertSame(self::PROVIDED_ID, $response->headers->get(CorrelationIdRequestSubscriber::HEADER_NAME));
    }

    private function subscriber(): CorrelationIdRequestSubscriber
    {
        return new CorrelationIdRequestSubscriber(
            new SymfonyUuidFactory(),
            new FixedIdGenerator(self::GENERATED_ID),
        );
    }

    private function requestEvent(Request $request): RequestEvent
    {
        return new RequestEvent(
            $this->createStub(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );
    }
}

final readonly class FixedIdGenerator implements IdGenerator
{
    public function __construct(private string $value) {}

    public function generate(): Uuid
    {
        return (new SymfonyUuidFactory())->fromString($this->value);
    }
}
