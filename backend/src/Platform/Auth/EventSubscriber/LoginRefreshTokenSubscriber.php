<?php

declare(strict_types=1);

namespace Zandu\Platform\Auth\EventSubscriber;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Zandu\Platform\Auth\Refresh\RefreshSessionManager;

final readonly class LoginRefreshTokenSubscriber
{
    public function __construct(private RefreshSessionManager $refreshSessions)
    {
    }

    #[AsEventListener(event: Events::AUTHENTICATION_SUCCESS)]
    public function __invoke(AuthenticationSuccessEvent $event): void
    {
        $refreshToken = $this->refreshSessions->issue($event->getUser()->getUserIdentifier());

        $event->setData($event->getData() + [
            'refreshToken' => $refreshToken->token(),
            'refreshExpiresAt' => $refreshToken->expiresAt()->format(DATE_ATOM),
        ]);
    }
}
