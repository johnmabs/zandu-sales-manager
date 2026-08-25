<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Pricing\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Pricing\Domain\PriceList\Event\PriceListActivated;
use Zandu\Modules\Pricing\Domain\PriceList\Event\PriceListArchived;
use Zandu\Modules\Pricing\Domain\PriceList\Event\PriceListCreated;
use Zandu\Modules\Pricing\Domain\PriceList\Event\PriceListDeactivated;
use Zandu\Modules\Pricing\Domain\PriceList\Event\PriceListUpdated;
use Zandu\Modules\Pricing\Domain\PriceList\PriceList;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListCode;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListName;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListPriority;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListScope;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListStatus;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\PriceListId;
use Zandu\SharedKernel\Money\Currency;

final class PriceListTest extends TestCase
{
    private const ORGANIZATION_ID = '0198d1b1-b2a4-7b6e-8e0e-608484906502';
    private const PRICE_LIST_ID = '0198e2b1-147c-72d5-b75a-a936797ff9c8';
    private const ACTOR_ID = '0198c728-8f2d-7f43-92d8-3f0c75b80186';

    public function testValueObjectsNormalizeAndValidateTheirValues(): void
    {
        self::assertSame('RETAIL-2026', PriceListCode::fromString(' retail-2026 ')->value());
        self::assertSame('Tarif standard', PriceListName::fromString(' Tarif   standard ')->value());
        self::assertSame(0, PriceListPriority::fromInt(0)->value());

        try {
            PriceListCode::fromString(' ');
            self::fail('An empty code must be rejected.');
        } catch (InvalidArgumentException) {
        }

        $this->expectException(InvalidArgumentException::class);
        PriceListPriority::fromInt(-1);
    }

    public function testCreationProducesATenantOwnedOrganizationDraft(): void
    {
        $priceList = $this->priceList();

        self::assertSame(self::ORGANIZATION_ID, $priceList->organizationId()->toString());
        self::assertSame(PriceListStatus::Draft, $priceList->status());
        self::assertSame(PriceListScope::Organization, $priceList->scope());
        self::assertSame('XAF', $priceList->currency()->code());
        self::assertSame('UTC', $priceList->createdAt()->getTimezone()->getName());
        self::assertSame('UTC', $priceList->validFrom()?->getTimezone()->getName());
        self::assertSame(1, $priceList->version());
        self::assertInstanceOf(PriceListCreated::class, $priceList->releaseEvents()[0]);
        self::assertSame([], $priceList->releaseEvents());
    }

    public function testValidityEndCannotPrecedeStart(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->priceList(
            new DateTimeImmutable('2026-08-26T12:00:00Z'),
            new DateTimeImmutable('2026-08-26T11:59:59Z'),
        );
    }

    public function testProfileCanBeUpdatedWithoutChangingTenantOrScope(): void
    {
        $priceList = $this->priceList();
        $priceList->releaseEvents();

        $priceList->update(
            PriceListCode::fromString('wholesale'),
            PriceListName::fromString('Tarif grossiste'),
            Currency::fromCode('eur'),
            null,
            null,
            PriceListPriority::fromInt(10),
            $this->actorId(),
            new DateTimeImmutable('2026-08-26T13:00:00+01:00'),
        );

        self::assertSame('WHOLESALE', $priceList->code()->value());
        self::assertSame('EUR', $priceList->currency()->code());
        self::assertSame(10, $priceList->priority()->value());
        self::assertSame(self::ORGANIZATION_ID, $priceList->organizationId()->toString());
        self::assertSame(PriceListScope::Organization, $priceList->scope());
        self::assertSame(2, $priceList->version());
        self::assertInstanceOf(PriceListUpdated::class, $priceList->releaseEvents()[0]);
    }

    public function testLifecycleAndSelectionAreExplicit(): void
    {
        $priceList = $this->priceList(
            new DateTimeImmutable('2026-08-26T10:00:00Z'),
            new DateTimeImmutable('2026-08-26T12:00:00Z'),
        );
        $priceList->releaseEvents();

        self::assertFalse($priceList->isSelectableAt(new DateTimeImmutable('2026-08-26T11:00:00Z')));
        $priceList->activate($this->actorId(), new DateTimeImmutable('2026-08-26T09:00:00Z'));
        self::assertInstanceOf(PriceListActivated::class, $priceList->releaseEvents()[0]);
        self::assertTrue($priceList->isSelectableAt(new DateTimeImmutable('2026-08-26T10:00:00Z')));
        self::assertTrue($priceList->isSelectableAt(new DateTimeImmutable('2026-08-26T12:00:00Z')));
        self::assertFalse($priceList->isSelectableAt(new DateTimeImmutable('2026-08-26T12:00:01Z')));

        $priceList->deactivate($this->actorId(), new DateTimeImmutable('2026-08-26T13:00:00Z'));
        self::assertInstanceOf(PriceListDeactivated::class, $priceList->releaseEvents()[0]);
        self::assertFalse($priceList->isSelectableAt(new DateTimeImmutable('2026-08-26T11:00:00Z')));

        $priceList->activate($this->actorId(), new DateTimeImmutable('2026-08-26T14:00:00Z'));
        $priceList->releaseEvents();
        $priceList->archive($this->actorId(), new DateTimeImmutable('2026-08-26T15:00:00Z'));
        self::assertSame(PriceListStatus::Archived, $priceList->status());
        self::assertSame(5, $priceList->version());
        self::assertInstanceOf(PriceListArchived::class, $priceList->releaseEvents()[0]);
        self::assertFalse($priceList->isSelectableAt(new DateTimeImmutable('2026-08-26T11:00:00Z')));

        $this->expectException(LogicException::class);
        $priceList->activate($this->actorId(), new DateTimeImmutable());
    }

    public function testArchivedPriceListCannotBeUpdated(): void
    {
        $priceList = $this->priceList();
        $priceList->archive($this->actorId(), new DateTimeImmutable('2026-08-26T15:00:00Z'));

        $this->expectException(LogicException::class);
        $priceList->update(
            $priceList->code(),
            $priceList->name(),
            $priceList->currency(),
            $priceList->validFrom(),
            $priceList->validTo(),
            $priceList->priority(),
            $this->actorId(),
            new DateTimeImmutable(),
        );
    }

    private function priceList(
        ?DateTimeImmutable $validFrom = new DateTimeImmutable('2026-08-26T11:00:00+01:00'),
        ?DateTimeImmutable $validTo = null,
    ): PriceList {
        $ids = new SymfonyUuidFactory();

        return PriceList::createDraft(
            PriceListId::fromString(self::PRICE_LIST_ID, $ids),
            OrganizationId::fromString(self::ORGANIZATION_ID, $ids),
            PriceListCode::fromString(' retail-2026 '),
            PriceListName::fromString(' Tarif   standard '),
            Currency::fromCode('xaf'),
            $validFrom,
            $validTo,
            PriceListPriority::fromInt(0),
            $this->actorId(),
            new DateTimeImmutable('2026-08-26T09:00:00+01:00'),
        );
    }

    private function actorId(): ActorId
    {
        return ActorId::fromString(self::ACTOR_ID, new SymfonyUuidFactory());
    }
}
