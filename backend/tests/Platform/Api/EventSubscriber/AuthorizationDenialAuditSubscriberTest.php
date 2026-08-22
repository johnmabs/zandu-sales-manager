<?php

declare(strict_types=1);

namespace Zandu\Tests\Platform\Api\EventSubscriber;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationDenied;
use Zandu\Modules\IdentityAccess\Application\Contract\ResourceScope;
use Zandu\Platform\Api\EventSubscriber\AuthorizationDenialAuditSubscriber;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Context\ActorType;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\SecurityAudit\ResourceReference;
use Zandu\SharedKernel\SecurityAudit\SafeAuditMetadata;
use Zandu\SharedKernel\SecurityAudit\SecurityAction;
use Zandu\SharedKernel\SecurityAudit\SecurityAuditTrail;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class AuthorizationDenialAuditSubscriberTest extends TestCase
{
    public function testItPersistsASafeDenialAfterTheBusinessTransactionAndReturnsForbidden(): void
    {
        $factory = new SymfonyUuidFactory();
        $organizationId = OrganizationId::fromString('0198e461-147c-72d5-b75a-a936797ff9c8', $factory);
        $context = new ActorContext(
            ActorId::fromString('0198e462-147c-72d5-b75a-a936797ff9c8', $factory),
            $organizationId,
            ActorType::User,
            CorrelationId::fromString('0198e463-147c-72d5-b75a-a936797ff9c8', $factory),
            new DateTimeImmutable('2026-08-24T09:00:00+00:00'),
        );
        $audit = new RecordingDeniedAuditTrail();
        $transaction = new RecordingTenantTransaction();
        $event = new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            AuthorizationDenied::forPermission($context, PermissionCode::RoleAssign, ResourceScope::organization($organizationId)),
        );

        (new AuthorizationDenialAuditSubscriber(
            $transaction,
            $audit,
            new FrozenClock(new DateTimeImmutable('2026-08-24T10:00:00+00:00')),
        ))->onKernelException($event);

        self::assertSame($organizationId, $transaction->organizationId);
        self::assertSame(['permission' => 'ROLE_ASSIGN'], $audit->metadata?->toArray());
        self::assertSame('ORGANIZATION', $audit->target?->type);
        self::assertSame(Response::HTTP_FORBIDDEN, $event->getResponse()?->getStatusCode());
        self::assertStringNotContainsString('token', (string) $event->getResponse()?->getContent());
    }
}

final class RecordingDeniedAuditTrail implements SecurityAuditTrail
{
    public ?SafeAuditMetadata $metadata = null;
    public ?ResourceReference $target = null;

    public function recordSuccess(ActorContext $actorContext, SecurityAction $action, ResourceReference $target, SafeAuditMetadata $metadata, DateTimeImmutable $occurredAt, ?OrganizationId $organizationId = null): void {}

    public function recordDenied(ActorContext $actorContext, ResourceReference $target, string $reason, SafeAuditMetadata $metadata, DateTimeImmutable $occurredAt): void
    {
        $this->target = $target;
        $this->metadata = $metadata;
    }
}

final class RecordingTenantTransaction implements TenantTransaction
{
    public ?OrganizationId $organizationId = null;

    public function transactional(OrganizationId $organizationId, callable $operation): mixed
    {
        $this->organizationId = $organizationId;

        return $operation();
    }
}
