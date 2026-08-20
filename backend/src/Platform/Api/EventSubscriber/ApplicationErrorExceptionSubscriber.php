<?php

declare(strict_types=1);

namespace Zandu\Platform\Api\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Zandu\Platform\Api\Exception\ApplicationErrorException;
use Zandu\SharedKernel\Messaging\CorrelationId;

final class ApplicationErrorExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['onKernelException', 10]];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof ApplicationErrorException) {
            return;
        }

        $error = $exception->error();
        $correlationId = $event->getRequest()->attributes->get(
            CorrelationIdRequestSubscriber::REQUEST_ATTRIBUTE,
        );

        $event->setResponse(new JsonResponse([
            'code' => $error->code(),
            'message' => $error->message(),
            'correlationId' => $correlationId instanceof CorrelationId
                ? $correlationId->toString()
                : null,
        ], $exception->statusCode()));
    }
}
