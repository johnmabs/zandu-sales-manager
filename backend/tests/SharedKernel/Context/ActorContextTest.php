<?php

declare(strict_types=1);

namespace Zandu\Tests\SharedKernel\Context;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Context\ActorType;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\SessionId;
use Zandu\SharedKernel\Identity\UserId;
use Zandu\SharedKernel\Messaging\CorrelationId;

final class ActorContextTest extends TestCase
{
    private const ACTOR_ID = '0198c728-8f2d-7f43-92d8-3f0c75b80186';
    private const ORGANIZATION_ID = '0198c728-a648-75b7-b7d7-c69d0bf84390';
    private const CORRELATION_ID = '0198c729-19da-75be-b508-1a4b36cf8d7a';
    private const USER_ID = '0198c729-51d8-7c2d-aadd-03429295336d';
    private const SESSION_ID = '0198c729-8428-73d7-9e51-34cd5517c927';

    public function testUserContextCarriesTrustedExecutionScope(): void
    {
        $factory = new SymfonyUuidFactory();
        $authenticatedAt = new DateTimeImmutable('2026-08-20T16:00:00+00:00');
        $context = new ActorContext(
            ActorId::fromString(self::ACTOR_ID, $factory),
            OrganizationId::fromString(self::ORGANIZATION_ID, $factory),
            ActorType::User,
            CorrelationId::fromString(self::CORRELATION_ID, $factory),
            $authenticatedAt,
            UserId::fromString(self::USER_ID, $factory),
            SessionId::fromString(self::SESSION_ID, $factory),
        );

        self::assertSame(self::ACTOR_ID, $context->actorId()->toString());
        self::assertSame(self::ORGANIZATION_ID, $context->organizationId()->toString());
        self::assertSame(ActorType::User, $context->actorType());
        self::assertSame(self::CORRELATION_ID, $context->correlationId()->toString());
        self::assertSame($authenticatedAt, $context->authenticatedAt());
        self::assertSame(self::USER_ID, $context->userId()?->toString());
        self::assertSame(self::SESSION_ID, $context->sessionId()?->toString());
    }

    #[DataProvider('nonUserActorTypes')]
    public function testNonUserContextDoesNotRequireHumanIdentity(ActorType $actorType): void
    {
        $factory = new SymfonyUuidFactory();
        $context = new ActorContext(
            ActorId::fromString(self::ACTOR_ID, $factory),
            OrganizationId::fromString(self::ORGANIZATION_ID, $factory),
            $actorType,
            CorrelationId::fromString(self::CORRELATION_ID, $factory),
            new DateTimeImmutable('2026-08-20T16:00:00+00:00'),
        );

        self::assertNull($context->userId());
        self::assertNull($context->sessionId());
    }

    public function testActorContextIsImmutable(): void
    {
        self::assertTrue((new ReflectionClass(ActorContext::class))->isReadOnly());
    }

    /**
     * @return iterable<string, array{ActorType}>
     */
    public static function nonUserActorTypes(): iterable
    {
        yield 'service account' => [ActorType::ServiceAccount];
        yield 'system' => [ActorType::System];
    }
}
