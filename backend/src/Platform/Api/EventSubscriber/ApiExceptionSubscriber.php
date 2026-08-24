<?php

declare(strict_types=1);

namespace Zandu\Platform\Api\EventSubscriber;

use ApiPlatform\Validator\Exception\ValidationException;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Zandu\SharedKernel\Error\ResourceConflict;
use Zandu\SharedKernel\Error\ResourceNotFound;
use Zandu\SharedKernel\Messaging\CorrelationId;

final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['onKernelException', 0]];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if ($event->hasResponse()) {
            return;
        }

        $mappedError = $this->map($event->getThrowable());
        if (null === $mappedError) {
            return;
        }

        $correlationId = $event->getRequest()->attributes->get(CorrelationIdRequestSubscriber::REQUEST_ATTRIBUTE);
        $event->setResponse(new JsonResponse([
            'code' => $mappedError['code'],
            'message' => $mappedError['message'],
            'correlationId' => $correlationId instanceof CorrelationId ? $correlationId->toString() : null,
        ], $mappedError['status']));
    }

    /** @return array{code: string, message: string, status: int}|null */
    private function map(\Throwable $exception): ?array
    {
        return match (true) {
            $exception instanceof AuthenticationException => [
                'code' => 'UNAUTHENTICATED',
                'message' => 'Authentication is required.',
                'status' => Response::HTTP_UNAUTHORIZED,
            ],
            $exception instanceof AccessDeniedException => [
                'code' => 'FORBIDDEN',
                'message' => 'You are not authorized to perform this operation.',
                'status' => Response::HTTP_FORBIDDEN,
            ],
            $exception instanceof ResourceNotFound => [
                'code' => 'NOT_FOUND',
                'message' => 'The requested resource was not found.',
                'status' => Response::HTTP_NOT_FOUND,
            ],
            $exception instanceof ResourceConflict => [
                'code' => 'CONFLICT',
                'message' => 'The request conflicts with the current resource state.',
                'status' => Response::HTTP_CONFLICT,
            ],
            $exception instanceof ValidationException, $exception instanceof NotEncodableValueException => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'The request is invalid.',
                'status' => Response::HTTP_BAD_REQUEST,
            ],
            $exception instanceof InvalidArgumentException => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'The request is invalid.',
                'status' => Response::HTTP_BAD_REQUEST,
            ],
            $exception instanceof LogicException => [
                'code' => 'DOMAIN_RULE_VIOLATION',
                'message' => 'The operation violates a domain rule.',
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
            ],
            default => null,
        };
    }
}
