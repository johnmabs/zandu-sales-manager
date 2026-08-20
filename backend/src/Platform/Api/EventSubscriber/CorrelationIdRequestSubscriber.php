<?php

declare(strict_types=1);

namespace Zandu\Platform\Api\EventSubscriber;

use InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\UuidFactory;
use Zandu\SharedKernel\Messaging\CorrelationId;

final readonly class CorrelationIdRequestSubscriber implements EventSubscriberInterface
{
    public const HEADER_NAME = 'X-Correlation-ID';
    public const REQUEST_ATTRIBUTE = '_zandu_correlation_id';

    public function __construct(
        private UuidFactory $uuidFactory,
        private IdGenerator $idGenerator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 100],
            KernelEvents::RESPONSE => ['onKernelResponse', -100],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $header = $request->headers->get(self::HEADER_NAME);

        try {
            $correlationId = null !== $header
                ? CorrelationId::fromString($header, $this->uuidFactory)
                : CorrelationId::generate($this->idGenerator);
        } catch (InvalidArgumentException) {
            $correlationId = CorrelationId::generate($this->idGenerator);
        }

        $request->attributes->set(self::REQUEST_ATTRIBUTE, $correlationId);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $correlationId = $event->getRequest()->attributes->get(self::REQUEST_ATTRIBUTE);

        if ($correlationId instanceof CorrelationId) {
            $event->getResponse()->headers->set(self::HEADER_NAME, $correlationId->toString());
        }
    }
}
