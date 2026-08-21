<?php

declare(strict_types=1);

namespace Zandu\Platform\Observability;

use Monolog\Attribute\AsMonologProcessor;
use Monolog\LogRecord;
use OpenTelemetry\API\Trace\Span;
use Symfony\Component\HttpFoundation\RequestStack;
use Zandu\Platform\Api\EventSubscriber\CorrelationIdRequestSubscriber;
use Zandu\SharedKernel\Messaging\CorrelationId;

#[AsMonologProcessor]
final readonly class CorrelationLogProcessor
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $extra = $record->extra;
        $correlationId = $this->requestStack->getCurrentRequest()?->attributes->get(
            CorrelationIdRequestSubscriber::REQUEST_ATTRIBUTE,
        );
        $spanContext = Span::getCurrent()->getContext();

        if ($correlationId instanceof CorrelationId) {
            $extra['correlationId'] = $correlationId->toString();
        }

        if ($spanContext->isValid()) {
            $extra['traceId'] = $spanContext->getTraceId();
            $extra['spanId'] = $spanContext->getSpanId();
        }

        return $record->with(extra: $extra);
    }
}
