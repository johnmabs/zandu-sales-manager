<?php

declare(strict_types=1);

namespace Zandu\Tests\Platform\Auth\Context;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Zandu\Platform\Api\EventSubscriber\CorrelationIdRequestSubscriber;
use Zandu\Platform\Auth\Context\ActorContextResolver;
use Zandu\Platform\Auth\EventSubscriber\JwtAuthenticatedSubscriber;
use Zandu\Platform\Auth\Security\AuthenticatedUser;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Context\ActorType;
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class ActorContextResolverTest extends TestCase
{
    public function testItDerivesTheContextFromTheAuthenticatedIdentityAndRequest(): void
    {
        $user = new AuthenticatedUser(
            'admin@zandu.test',
            'hash',
            '0198c728-8f2d-7f43-92d8-3f0c75b80186',
            '0198c728-a648-75b7-b7d7-c69d0bf84390',
            '0198c729-51d8-7c2d-aadd-03429295336d',
        );
        $token = new UsernamePasswordToken($user, 'api', $user->getRoles());
        $token->setAttribute(JwtAuthenticatedSubscriber::AUTHENTICATED_AT_ATTRIBUTE, 1787256000);
        $token->setAttribute(
            JwtAuthenticatedSubscriber::SESSION_ID_ATTRIBUTE,
            '0198c729-8428-73d7-9e51-34cd5517c927',
        );
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);
        $factory = new SymfonyUuidFactory();
        $request = new Request();
        $request->attributes->set(
            CorrelationIdRequestSubscriber::REQUEST_ATTRIBUTE,
            CorrelationId::fromString('0198c729-19da-75be-b508-1a4b36cf8d7a', $factory),
        );
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $resolver = new ActorContextResolver(
            $tokenStorage,
            $requestStack,
            $factory,
            new FrozenClock(new DateTimeImmutable('2030-01-01T00:00:00+00:00')),
        );

        $context = $resolver->resolve();

        self::assertSame('0198c728-8f2d-7f43-92d8-3f0c75b80186', $context->actorId()->toString());
        self::assertSame('0198c728-a648-75b7-b7d7-c69d0bf84390', $context->organizationId()->toString());
        self::assertSame('0198c729-51d8-7c2d-aadd-03429295336d', $context->userId()?->toString());
        self::assertSame('0198c729-8428-73d7-9e51-34cd5517c927', $context->sessionId()?->toString());
        self::assertSame('0198c729-19da-75be-b508-1a4b36cf8d7a', $context->correlationId()->toString());
        self::assertSame(ActorType::User, $context->actorType());
        self::assertSame('2026-08-20T20:00:00+00:00', $context->authenticatedAt()->format(DATE_ATOM));
    }
}
