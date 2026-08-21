<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Organization\Domain;

use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Organization\Domain\CountryCode;
use Zandu\Modules\Organization\Domain\Event\OrganizationClosed;
use Zandu\Modules\Organization\Domain\Event\OrganizationClosureRequested;
use Zandu\Modules\Organization\Domain\Event\OrganizationCreated;
use Zandu\Modules\Organization\Domain\Event\OrganizationReactivated;
use Zandu\Modules\Organization\Domain\Event\OrganizationSuspended;
use Zandu\Modules\Organization\Domain\Event\OrganizationUpdated;
use Zandu\Modules\Organization\Domain\Locale;
use Zandu\Modules\Organization\Domain\Organization;
use Zandu\Modules\Organization\Domain\OrganizationName;
use Zandu\Modules\Organization\Domain\OrganizationStatus;
use Zandu\Modules\Organization\Domain\TimeZone;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Money\Currency;

final class OrganizationTest extends TestCase
{
    private const ORGANIZATION_ID = '0198c728-a648-75b7-b7d7-c69d0bf84390';
    private const ACTOR_ID = '0198c728-8f2d-7f43-92d8-3f0c75b80186';

    public function testCreationProducesAnActiveOrganizationAndEvent(): void
    {
        $organization = $this->organization();

        self::assertSame(OrganizationStatus::Active, $organization->status());
        self::assertSame(1, $organization->version());
        self::assertInstanceOf(OrganizationCreated::class, $organization->releaseEvents()[0]);
        self::assertSame([], $organization->releaseEvents());
    }

    public function testProfileCanBeUpdatedWhileActive(): void
    {
        $organization = $this->organization();
        $organization->releaseEvents();
        $at = new DateTimeImmutable('2026-08-22T09:00:00+00:00');

        $organization->updateProfile(
            OrganizationName::fromString('Zandu Congo'),
            CountryCode::fromString('CG'),
            Currency::fromCode('XAF'),
            TimeZone::fromString('Africa/Brazzaville'),
            Locale::fromString('fr_CG'),
            $this->actorId(),
            $at,
        );

        self::assertSame('Zandu Congo', $organization->name()->value());
        self::assertSame(2, $organization->version());
        self::assertSame($at, $organization->updatedAt());
        self::assertInstanceOf(OrganizationUpdated::class, $organization->releaseEvents()[0]);
    }

    public function testSuspensionAndReactivationFollowTheLifecycle(): void
    {
        $organization = $this->organization();
        $organization->releaseEvents();
        $at = new DateTimeImmutable('2026-08-22T10:00:00+00:00');

        $organization->suspend($this->actorId(), $at);

        self::assertSame(OrganizationStatus::Suspended, $organization->status());
        self::assertSame($at, $organization->suspendedAt());
        self::assertInstanceOf(OrganizationSuspended::class, $organization->releaseEvents()[0]);

        $organization->reactivate($this->actorId(), $at->modify('+1 hour'));

        self::assertSame(OrganizationStatus::Active, $organization->status());
        self::assertNull($organization->suspendedAt());
        self::assertInstanceOf(OrganizationReactivated::class, $organization->releaseEvents()[0]);
    }

    public function testClosureIsTerminal(): void
    {
        $organization = $this->organization();
        $organization->releaseEvents();
        $at = new DateTimeImmutable('2026-08-22T11:00:00+00:00');

        $organization->requestClosure($this->actorId(), $at);
        self::assertSame(OrganizationStatus::ClosurePending, $organization->status());
        self::assertInstanceOf(OrganizationClosureRequested::class, $organization->releaseEvents()[0]);

        $organization->close($this->actorId(), $at->modify('+1 hour'));
        self::assertSame(OrganizationStatus::Closed, $organization->status());
        self::assertInstanceOf(OrganizationClosed::class, $organization->releaseEvents()[0]);

        $this->expectException(LogicException::class);
        $organization->reactivate($this->actorId(), $at->modify('+2 hours'));
    }

    public function testSuspendedOrganizationCannotBeUpdated(): void
    {
        $organization = $this->organization();
        $organization->suspend($this->actorId(), new DateTimeImmutable('2026-08-22T10:00:00+00:00'));

        $this->expectException(LogicException::class);
        $organization->updateProfile(
            OrganizationName::fromString('Blocked Update'),
            CountryCode::fromString('CG'),
            Currency::fromCode('XAF'),
            TimeZone::fromString('Africa/Brazzaville'),
            Locale::fromString('fr_CG'),
            $this->actorId(),
            new DateTimeImmutable('2026-08-22T11:00:00+00:00'),
        );
    }

    private function organization(): Organization
    {
        return Organization::create(
            OrganizationId::fromString(self::ORGANIZATION_ID, new SymfonyUuidFactory()),
            OrganizationName::fromString('Zandu'),
            CountryCode::fromString('CG'),
            Currency::fromCode('XAF'),
            TimeZone::fromString('Africa/Brazzaville'),
            Locale::fromString('fr_CG'),
            $this->actorId(),
            new DateTimeImmutable('2026-08-22T08:00:00+00:00'),
        );
    }

    private function actorId(): ActorId
    {
        return ActorId::fromString(self::ACTOR_ID, new SymfonyUuidFactory());
    }
}
