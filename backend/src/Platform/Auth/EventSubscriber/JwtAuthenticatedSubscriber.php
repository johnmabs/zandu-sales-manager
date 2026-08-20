<?php

declare(strict_types=1);

namespace Zandu\Platform\Auth\EventSubscriber;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTAuthenticatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class JwtAuthenticatedSubscriber
{
    public const AUTHENTICATED_AT_ATTRIBUTE = '_zandu_authenticated_at';
    public const SESSION_ID_ATTRIBUTE = '_zandu_session_id';

    #[AsEventListener(event: Events::JWT_AUTHENTICATED)]
    public function __invoke(JWTAuthenticatedEvent $event): void
    {
        $payload = $event->getPayload();
        $token = $event->getToken();

        if (isset($payload['iat']) && is_int($payload['iat'])) {
            $token->setAttribute(self::AUTHENTICATED_AT_ATTRIBUTE, $payload['iat']);
        }

        if (isset($payload['sessionId']) && is_string($payload['sessionId'])) {
            $token->setAttribute(self::SESSION_ID_ATTRIBUTE, $payload['sessionId']);
        }
    }
}
