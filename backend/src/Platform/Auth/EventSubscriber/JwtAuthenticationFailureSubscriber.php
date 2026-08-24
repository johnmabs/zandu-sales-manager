<?php

declare(strict_types=1);

namespace Zandu\Platform\Auth\EventSubscriber;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Zandu\Platform\Api\EventSubscriber\CorrelationIdRequestSubscriber;
use Zandu\SharedKernel\Messaging\CorrelationId;

final class JwtAuthenticationFailureSubscriber
{
    #[AsEventListener(event: Events::JWT_INVALID)]
    #[AsEventListener(event: Events::JWT_EXPIRED)]
    public function __invoke(AuthenticationFailureEvent $event): void
    {
        $correlationId = $event->getRequest()?->attributes->get(CorrelationIdRequestSubscriber::REQUEST_ATTRIBUTE);

        $event->setResponse(new JsonResponse([
            'code' => 'UNAUTHENTICATED',
            'message' => 'Authentication is required.',
            'correlationId' => $correlationId instanceof CorrelationId ? $correlationId->toString() : null,
        ], Response::HTTP_UNAUTHORIZED));
    }
}
