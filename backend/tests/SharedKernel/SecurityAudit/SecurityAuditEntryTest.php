<?php

declare(strict_types=1);

namespace Zandu\Tests\SharedKernel\SecurityAudit;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Context\ActorType;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\SecurityAuditEntryId;
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\SecurityAudit\ActorReference;
use Zandu\SharedKernel\SecurityAudit\AuditOutcome;
use Zandu\SharedKernel\SecurityAudit\ResourceReference;
use Zandu\SharedKernel\SecurityAudit\SafeAuditMetadata;
use Zandu\SharedKernel\SecurityAudit\SecurityAction;
use Zandu\SharedKernel\SecurityAudit\SecurityAuditEntry;

final class SecurityAuditEntryTest extends TestCase
{
    private const ENTRY_ID = '0198e401-147c-72d5-b75a-a936797ff9c8';
    private const ORGANIZATION_ID = '0198e402-147c-72d5-b75a-a936797ff9c8';
    private const ACTOR_ID = '0198e403-147c-72d5-b75a-a936797ff9c8';
    private const CORRELATION_ID = '0198e404-147c-72d5-b75a-a936797ff9c8';

    public function testItCapturesACompleteImmutableAuditFact(): void
    {
        $entry = $this->entry(SafeAuditMetadata::fromArray(['scope' => 'ORGANIZATION', 'assignmentCount' => 2]));

        self::assertSame(SecurityAction::RoleAssigned, $entry->action);
        self::assertSame(AuditOutcome::Success, $entry->outcome);
        self::assertSame('ORGANIZATION_MEMBERSHIP', $entry->target->type);
        self::assertSame(['scope' => 'ORGANIZATION', 'assignmentCount' => 2], $entry->metadata->toArray());
        self::assertSame(self::CORRELATION_ID, $entry->correlationId->toString());
    }

    #[DataProvider('sensitiveMetadataKeys')]
    public function testItRejectsSensitiveMetadataKeys(string $key): void
    {
        $this->expectException(InvalidArgumentException::class);
        SafeAuditMetadata::fromArray([$key => 'must-not-leak']);
    }

    /** @return iterable<string, array{string}> */
    public static function sensitiveMetadataKeys(): iterable
    {
        yield 'password' => ['password'];
        yield 'access token' => ['accessToken'];
        yield 'refresh token' => ['refresh_token'];
        yield 'payment secret' => ['paymentSecret'];
        yield 'authorization header' => ['authorizationHeader'];
    }

    public function testItRejectsNonUtcOccurrenceTime(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->entry(SafeAuditMetadata::empty(), new DateTimeImmutable('2026-08-24T11:00:00+01:00'));
    }

    private function entry(SafeAuditMetadata $metadata, ?DateTimeImmutable $occurredAt = null): SecurityAuditEntry
    {
        $factory = new SymfonyUuidFactory();
        $organizationId = OrganizationId::fromString(self::ORGANIZATION_ID, $factory);

        return new SecurityAuditEntry(
            SecurityAuditEntryId::fromString(self::ENTRY_ID, $factory),
            $organizationId,
            new ActorReference(ActorId::fromString(self::ACTOR_ID, $factory), ActorType::User, null),
            SecurityAction::RoleAssigned,
            ResourceReference::for('organization_membership', $organizationId),
            AuditOutcome::Success,
            'Role granted by administrator.',
            $metadata,
            CorrelationId::fromString(self::CORRELATION_ID, $factory),
            null,
            null,
            '127.0.0.1',
            'PHPUnit',
            $occurredAt ?? new DateTimeImmutable('2026-08-24T10:00:00+00:00'),
        );
    }
}
