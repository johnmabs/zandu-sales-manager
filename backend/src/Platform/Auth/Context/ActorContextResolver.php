<?php

declare(strict_types=1);

namespace Zandu\Platform\Auth\Context;

use DateTimeImmutable;
use DateTimeZone;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Zandu\Platform\Api\EventSubscriber\CorrelationIdRequestSubscriber;
use Zandu\Platform\Auth\EventSubscriber\JwtAuthenticatedSubscriber;
use Zandu\Platform\Auth\Security\AuthenticatedUser;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Context\ActorType;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\SessionId;
use Zandu\SharedKernel\Identity\UserId;
use Zandu\SharedKernel\Identity\UuidFactory;
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Time\Clock;

final readonly class ActorContextResolver
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private RequestStack $requestStack,
        private UuidFactory $uuidFactory,
        private Clock $clock,
    ) {
    }

    public function resolve(): ActorContext
    {
        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();
        $correlationId = $this->requestStack->getCurrentRequest()?->attributes->get(
            CorrelationIdRequestSubscriber::REQUEST_ATTRIBUTE,
        );

        if (!$user instanceof AuthenticatedUser || !$correlationId instanceof CorrelationId) {
            throw new AccessDeniedException('An authenticated actor context is required.');
        }

        $authenticatedAt = $token->hasAttribute(JwtAuthenticatedSubscriber::AUTHENTICATED_AT_ATTRIBUTE)
            ? (new DateTimeImmutable('@'.(string) $token->getAttribute(
                JwtAuthenticatedSubscriber::AUTHENTICATED_AT_ATTRIBUTE,
            )))->setTimezone(new DateTimeZone('UTC'))
            : $this->clock->now();
        $sessionId = $token->hasAttribute(JwtAuthenticatedSubscriber::SESSION_ID_ATTRIBUTE)
            ? SessionId::fromString(
                (string) $token->getAttribute(JwtAuthenticatedSubscriber::SESSION_ID_ATTRIBUTE),
                $this->uuidFactory,
            )
            : null;

        return new ActorContext(
            ActorId::fromString($user->actorId(), $this->uuidFactory),
            OrganizationId::fromString($user->organizationId(), $this->uuidFactory),
            ActorType::User,
            $correlationId,
            $authenticatedAt,
            UserId::fromString($user->userId(), $this->uuidFactory),
            $sessionId,
        );
    }
}
