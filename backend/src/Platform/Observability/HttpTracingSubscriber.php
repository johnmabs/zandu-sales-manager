<?php

declare(strict_types=1);

namespace Zandu\Platform\Observability;

use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\TracerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class HttpTracingSubscriber implements EventSubscriberInterface
{
    private const SPAN_ATTRIBUTE = '_zandu_http_span';
    private const SCOPE_ATTRIBUTE = '_zandu_http_span_scope';

    public function __construct(private TracerInterface $tracer) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 110],
            KernelEvents::RESPONSE => ['onResponse', -110],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $span = $this->tracer->spanBuilder($request->getMethod() . ' ' . $request->getPathInfo())
            ->setAttribute('http.request.method', $request->getMethod())
            ->setAttribute('url.path', $request->getPathInfo())
            ->startSpan();
        $request->attributes->set(self::SPAN_ATTRIBUTE, $span);
        $request->attributes->set(self::SCOPE_ATTRIBUTE, $span->activate());
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $span = $event->getRequest()->attributes->get(self::SPAN_ATTRIBUTE);

        if ($span instanceof SpanInterface) {
            $span->setAttribute('http.response.status_code', $event->getResponse()->getStatusCode());
            $event->getRequest()->attributes->get(self::SCOPE_ATTRIBUTE)?->detach();
            $span->end();
        }
    }
}
