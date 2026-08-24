<?php

declare(strict_types=1);

namespace Zandu\Platform\Auth\EventSubscriber;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Zandu\Platform\Auth\Refresh\RefreshSessionManager;
use Zandu\Platform\Auth\Security\AuthenticatedUser;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\UuidFactory;

final readonly class LoginRefreshTokenSubscriber
{
    public function __construct(
        private RefreshSessionManager $refreshSessions,
        private UuidFactory $uuidFactory,
    ) {}

    #[AsEventListener(event: Events::AUTHENTICATION_SUCCESS)]
    public function __invoke(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof AuthenticatedUser) {
            return;
        }
        $refreshToken = $this->refreshSessions->issue(
            $user->getUserIdentifier(),
            OrganizationId::fromString($user->organizationId(), $this->uuidFactory),
            $user->authorizationVersion(),
        );

        $event->setData($event->getData() + [
            'refreshToken' => $refreshToken->token(),
            'refreshExpiresAt' => $refreshToken->expiresAt()->format(DATE_ATOM),
        ]);
    }
}
