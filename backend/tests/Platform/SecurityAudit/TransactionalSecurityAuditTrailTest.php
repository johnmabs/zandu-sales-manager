<?php

declare(strict_types=1);

namespace Zandu\Tests\Platform\SecurityAudit;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\SecurityAudit\TransactionalSecurityAuditTrail;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Context\ActorType;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\Uuid;
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Messaging\OutboxMessage;
use Zandu\SharedKernel\Messaging\OutboxRepository;
use Zandu\SharedKernel\SecurityAudit\ResourceReference;
use Zandu\SharedKernel\SecurityAudit\SafeAuditMetadata;
use Zandu\SharedKernel\SecurityAudit\SecurityAction;
use Zandu\SharedKernel\SecurityAudit\SecurityAuditEntry;
use Zandu\SharedKernel\SecurityAudit\SecurityAuditRepository;

final class TransactionalSecurityAuditTrailTest extends TestCase
{
    public function testItAppendsTheAuditAndItsSafeOutboxEnvelope(): void
    {
        $audits = new InMemorySecurityAudits();
        $outbox = new InMemoryOutbox();
        $factory = new SymfonyUuidFactory();
        $organizationId = OrganizationId::fromString('0198e441-147c-72d5-b75a-a936797ff9c8', $factory);
        $context = new ActorContext(
            ActorId::fromString('0198e442-147c-72d5-b75a-a936797ff9c8', $factory),
            $organizationId,
            ActorType::User,
            CorrelationId::fromString('0198e443-147c-72d5-b75a-a936797ff9c8', $factory),
            new DateTimeImmutable('2026-08-24T09:00:00+00:00'),
        );
        $ids = [
            $factory->fromString('0198e444-147c-72d5-b75a-a936797ff9c8'),
            $factory->fromString('0198e445-147c-72d5-b75a-a936797ff9c8'),
        ];
        $trail = new TransactionalSecurityAuditTrail($audits, $outbox, new SequentialIdGenerator($ids));

        $trail->recordSuccess(
            $context,
            SecurityAction::OrganizationSuspended,
            ResourceReference::for('organization', $organizationId),
            SafeAuditMetadata::empty(),
            new DateTimeImmutable('2026-08-24T10:00:00+00:00'),
        );

        self::assertCount(1, $audits->entries);
        self::assertCount(1, $outbox->messages);
        self::assertSame('security.audit.recorded.v1', $outbox->messages[0]->type);
        self::assertSame('ORGANIZATION_SUSPENDED', $outbox->messages[0]->payload['action']);
        self::assertSame($audits->entries[0]->id->toString(), $outbox->messages[0]->payload['auditEntryId']);
    }
}

final class InMemorySecurityAudits implements SecurityAuditRepository
{
    /** @var list<SecurityAuditEntry> */
    public array $entries = [];
    public function append(SecurityAuditEntry $entry): void
    {
        $this->entries[] = $entry;
    }
}

final class InMemoryOutbox implements OutboxRepository
{
    /** @var list<OutboxMessage> */
    public array $messages = [];
    public function append(OutboxMessage $message): void
    {
        $this->messages[] = $message;
    }
}

final class SequentialIdGenerator implements IdGenerator
{
    /** @param list<Uuid> $ids */
    public function __construct(private array $ids) {}
    public function generate(): Uuid
    {
        return array_shift($this->ids) ?? throw new \LogicException('No UUID left.');
    }
}
