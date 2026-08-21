<?php

declare(strict_types=1);

namespace Zandu\Tests\Platform\Observability;

use DateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Zandu\Platform\Api\EventSubscriber\CorrelationIdRequestSubscriber;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Observability\CorrelationLogProcessor;
use Zandu\Platform\Observability\HttpTracingSubscriber;
use Zandu\SharedKernel\Messaging\CorrelationId;

final class ObservabilityTest extends TestCase
{
    public function testHttpSpanAndStructuredLogCarryTechnicalContext(): void
    {
        $exporter = new InMemoryExporter();
        $provider = new TracerProvider(new SimpleSpanProcessor($exporter));
        $subscriber = new HttpTracingSubscriber($provider->getTracer('test'));
        $request = Request::create('/health/live', 'GET');
        $request->attributes->set(
            CorrelationIdRequestSubscriber::REQUEST_ATTRIBUTE,
            CorrelationId::fromString('0198c728-8f2d-7f43-92d8-3f0c75b80186', new SymfonyUuidFactory()),
        );
        $kernel = $this->createStub(HttpKernelInterface::class);
        $subscriber->onRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));
        $stack = new RequestStack();
        $stack->push($request);
        $record = (new CorrelationLogProcessor($stack))(new LogRecord(
            new DateTimeImmutable(),
            'app',
            Level::Info,
            'request handled',
        ));
        $subscriber->onResponse(new ResponseEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new Response(status: 200),
        ));

        self::assertSame('0198c728-8f2d-7f43-92d8-3f0c75b80186', $record->extra['correlationId']);
        self::assertNotEmpty($record->extra['traceId']);
        self::assertNotEmpty($record->extra['spanId']);
        self::assertCount(1, $exporter->getSpans());
        self::assertSame('GET /health/live', $exporter->getSpans()[0]->getName());
    }
}
