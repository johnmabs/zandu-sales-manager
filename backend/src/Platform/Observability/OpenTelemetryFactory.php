<?php

declare(strict_types=1);

namespace Zandu\Platform\Observability;

use OpenTelemetry\API\Trace\NoopTracerProvider;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;

final class OpenTelemetryFactory
{
    public static function createTracer(bool $enabled, string $endpoint): TracerInterface
    {
        if (!$enabled) {
            return (new NoopTracerProvider())->getTracer('zandu.backend');
        }

        $transport = (new OtlpHttpTransportFactory())->create(
            rtrim($endpoint, '/').'/v1/traces',
            'application/x-protobuf',
        );
        $provider = new TracerProvider(new SimpleSpanProcessor(new SpanExporter($transport)));

        return $provider->getTracer('zandu.backend', '0.1.0');
    }
}
