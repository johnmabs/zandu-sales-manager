<?php

declare(strict_types=1);

namespace Zandu\Platform\Auth\EventSubscriber;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Zandu\Platform\Auth\Security\AuthenticatedUser;

final class JwtClaimsSubscriber
{
    #[AsEventListener(event: Events::JWT_CREATED)]
    public function __invoke(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof AuthenticatedUser) {
            return;
        }

        $event->setData($event->getData() + [
            'actorId' => $user->actorId(),
            'organizationId' => $user->organizationId(),
            'userId' => $user->userId(),
        ]);
    }
}
